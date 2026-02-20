<?php
namespace Joomla\Module\CourtUsage\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use DateInterval;
use DatePeriod;
use Joomla\CMS\Log\Log;

const ERR_INVAL = 1;
const ERR_INTERNAL = 2;
const ERR_BD = 3;

require_once JPATH_SITE . '/modules/mod_tennis/const.php';

class CourtUsageHelper
{
    public function chart($type, $begin, $end)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
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

        $b = new Date($begin);
        $e = new Date($end);
	
        switch ($type) {
        case 'court-usage':

            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($b, $interval, $e);

            $usage['date'] = array('normal', 'cours', 'manif');
            foreach($period as $dt) {
	        // Log::add($dt->format("M Y"), Log::DEBUG, 'mod_court_usage');
                $usage[$dt->format("M Y")] = array(0, 0, 0);
	    }
            foreach($res as $r) {
                $d = new Date($r['date']);
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

            $w = range(5, 30, 5);
            $bins = array();
            $bins[] = array('min' => 0, 'max' => 0);
            $bins[] = array('min' => 1, 'max' => 1);
            $bins[] = array('min' => 2, 'max' => 5);
            for($i=0; $i < count($w)-1; $i++)
                $bins[] = array('min' => $w[$i], 'max' => $w[$i+1]);

            $hist = array();
            foreach($bins as $bin) {
                $a = $bin['min'];
                $b = $bin['max'];
                $hist[$a ."-" .$b] = array_filter($users,
                function($e) use ($a, $b) {
                    $v = intval($e);
                    return ( ($v >= $a) && ($v <= $b) );
                });
            }

            $data = array();
            $data[] = array('#res', '#joueur');
            foreach($bins as $bin) {
                $a = $bin['min'];
                $b = $bin['max'];
                $l = $a == $b ? $a : $a ."-" .$b;
                $data[] = array(strval($l), count($hist[$a . "-" . $b]));
            }
            break;

        case 'group-histo':

            break;
        }
        return $data;
    }

    function getCount($type, $begin, $end)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true);
        $query->select(array('user1', 'user2', 'date'))
              ->from($db->quoteName('#__reservation'))
              ->order($db->quoteName('date').' ASC')
              ->where($db->quoteName('date') . '>=' . $db->quote($begin) . ' and ' .
              $db->quoteName('date') . '<=' . $db->quote($end) . ' and ' .
              $db->quoteName('type') . '=' . $db->quote($type));
        $db->setQuery($query);
        $res = $db->loadAssocList();
        return sizeof($res);
    }

    public function usersYearStatus($begin, $end, $showNewUsers)
    {
        $s = '<p style="margin-left:50px" >';
        $s .= "Reservations normal " . $this->getCount(RES_TYPE_NORMAL, $begin, $end) .
            ", cours " . $this->getCount(RES_TYPE_COURS, $begin, $end) .
           ", manif " . $this->getCount(RES_TYPE_MANIF, $begin, $end) . '</br>';

        if ($showNewUsers == 1) {
           $db = Factory::getContainer()->get(DatabaseInterface::class);

           /* Nombre de nouveau joueurs */
           $query = $db->getQuery(true);
           $query->select(array('username'))
               ->from($db->quoteName('#__users'))
               ->where($db->quoteName('block') . '= 0 and ' .
                       $db->quoteName('registerDate') . '>=' . $db->quote($begin) . ' and ' .
                       $db->quoteName('registerDate') . '<=' . $db->quote($end));

           $db->setQuery($query);
           $res = $db->loadAssocList();

           $s .= "nouveaux membres " . sizeof($res) . ': ';
           foreach ($res as $r)
                $s .= $r['username'] . ', ';
        }
	$s .= '</p>';
        return $s;
    }

    public function usersStatus()
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        /* Nombre de groupes (famille, couple, adulte) */
        $query = $db->getQuery(true);
        $query->select(array('group_id', 'block'))
              ->from($db->quoteName('#__users'))
              ->where($db->quoteName('block') . '= 0')
              ->group($db->quoteName('group_id'));
        $db->setQuery($query);
        $res = $db->loadAssocList();

        $s = '<p style="margin-left:50px" >';
        $s .= 'Nombre de groupes (famille, couple, adulte, ...): ' . sizeof($res) . '</br>';

        /* Nombre de joueurs */
        $query = $db->getQuery(true);
        $query->select(array('id', 'block'))
              ->from($db->quoteName('#__users'))
              ->where($db->quoteName('block') . '= 0');
        $db->setQuery($query);
        $res = $db->loadAssocList();

        $s .= 'Nombre de joueurs: ' . sizeof($res) . '</br>';

        /* Nombre de groupes sans password (jamais reserve) */
        $query = $db->getQuery(true);
        $query->select(array('group_id', 'block'))
              ->from($db->quoteName('#__users'))
              ->group($db->quoteName('group_id'))
              ->where($db->quoteName('block') . '='. $db->quote(0) . ' and ' .
                      $db->quoteName('requireReset') . '=' . $db->quote(1));
        $db->setQuery($query);
        $res = $db->loadAssocList();

        $s .= "Nombre de groupes n'ayant jamais reserve le court: " . sizeof($res) . '</br>';

        /* Nombre de groupes desinscrits */
        $query = $db->getQuery(true);
        $query->select(array('group_id', 'block'))
              ->from($db->quoteName('#__users'))
              ->group($db->quoteName('group_id'))
              ->where($db->quoteName('block') . '='. $db->quote(1));
        $db->setQuery($query);
        $res = $db->loadAssocList();

        $s .= "Nombre de groupes desinscrit: " . sizeof($res) . '</br>';

        return $s . '</p>';
    }

    public function getAjax()
    {
        $input  = Factory::getApplication()->getInput();
        $cmd = $input->get('cmd');

	$post = $input->post->getArray();
	// Log::add('getAjax ' . print_r($post, true), Log::DEBUG, 'mod_court_usage');

        if (is_null($cmd))
            return ERR_INVAL;

        switch ($cmd) {
        case 'chart':
            return $this->chart($input->get('type'),
                    $input->get('begin'), $input->get('end'));

        case 'usersStatus':
            return $this->usersStatus();

        case 'usersYearStatus':
            return $this->usersYearStatus($input->get('begin'), $input->get('end'),
                       $input->get('showNewUsers'));

        default:
            return ERR_INVAL;
        }
    }
}

?>