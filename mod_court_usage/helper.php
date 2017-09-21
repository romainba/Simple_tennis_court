<?php

const ERR_INVAL = 1;
const ERR_INTERNAL = 2;
const ERR_BD = 3;

const ERR_NAMES = array("",
"La requête est invalide",
"Une erreur interne s'est produite",
"Erreur avec database");

class ModCourtUsageHelper
{
    public static function chart($type, $begin, $end)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select(array('a.user1', 'a.user2', 'a.date', 'b.name'))
              ->from($db->quoteName('#__reservation', 'a'))
              ->join('INNER', $db->quoteName('#__res_type', 'b') . ' on (' .
              $db->quoteName('a.type') . ' = ' . $db->quoteName('b.id') . ')')
              ->order($db->quoteName('date').' ASC')
              ->where($db->quoteName('date') . '>=' . $db->quote($begin) . ' and ' .
              $db->quoteName('date') . '<=' . $db->quote($end));
        
        $db->setQuery($query);
        $res = $db->loadAssocList();

        $b = new DateTime($begin);
        $e = new DateTime($end);

        switch ($type) {
        case 'court-usage':
        
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($b, $interval, $e);
        
            $usage['date'] = array('normal', 'cours', 'manif');
            foreach($period as $dt)
                $usage[$dt->format("M Y")] = array(0, 0, 0);
            
            foreach($res as $r) {
                $d = new DateTime($r['date']);
                $k = $d->format("M Y");
                
                for ($i = 1; $i < 4; $i++)
                    if (RES_TYPE[$i] == $r['name']) {
                        $usage[$k][$i - 1]++;
                        break;
                    }
            }

            $data = array();
            foreach($usage as $key => $u)
                $data[] = array($key, $u[0], $u[1], $u[2]);

            break;

        case 'player-histo':

            $query = $db->getQuery(true);

            $query->select(array($db->quoteName('id'), '0 as '.$db->quoteName('count')))
                  ->from($db->quoteName('#__users'));

            $db->setQuery($query);
            $users = $db->loadAssocList('id', 'count');

            foreach($res as $r) {
                $p = intval($r['user1']);
                $users[$p]++;
                
                $p = intval($r['user2']);
                $users[$p]++;
            }

            $widths = range(0, 30, 5);
            $bins = array();
            for($i=0, $j=count($widths)-1; $i<$j;++$i)
                $bins[] = array('min' => $width[$i], 'max' => $widths[$i+1]);

            $hist = array();
            foreach($bins as $bin) {
                $hist[$bin['min']."-".$bin['max']] = array_filter($users,
                function($e) use ($bin) {
                    return ( ($e > $bin['min']) && ($e <= $bin['max']) );
                });
            }

            $data = array();
            $data[] = array('#res', '#joueur');
            foreach($bins as $bin) {
                $c = $bin['min']."-".$bin['max'];
                $data[] = array($c, count($hist[$c]));
            }
            break;
            
        case 'group-histo':

            break;
        }
        return $data;
    }

    public static function getAjax()
    {
 		$input  = &JFactory::getApplication()->input;
        $cmd = $input->get('cmd');

        if (is_null($cmd))
            return ERR_INVAL;

        switch ($cmd) {
        case 'chart':
            return ModCourtUsageHelper::chart($input->get('type'),
            	$input->get('begin'), $input->get('end'));

        case 'exportDb':
            require_once dirname(__FILE__) . '/export.php';
            return ModTennisExporter::exportDb($input->get('begin'), $input->get('end'));

        default:
            return ERR_INVAL;
        }
    }
}

?>