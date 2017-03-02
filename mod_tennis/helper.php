<?php

function debug($msg) {
    echo "<script>console.log('".$msg."');</script>";
}

class ModTennisHelper
{
    public static function getUsers($params)
    {
        $db = JFactory::getDbo();
        $query= $db->getQuery(true);
        $query->select($db->quoteName(array('id', 'username', 'name')))
              ->from($db->quoteName('c8iu9_users'));
        
        $db->setQuery($query);
        $results = $db->loadObjectList();

        return (array) $results;
    }

    public static function getWeekReservation($year, $week)
    {
        $db = JFactory::getDbo();
        $query= $db->getQuery(true);

        $date = new DateTime();
        $date->setISODate($year, $week, 1);
        $start = $date->format('Y-m-d');
        $date->setISODate($year, $week + 1, 1);
        $end = $date->format('Y-m-d');

        $query->select($db->quoteName(array('id', 'user', 'date')))
              ->from($db->quoteName('reservation'))
              ->order($db->quoteName('date') . ' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
              	$db->quoteName('date').' <= '.$db->quote($end));

        $db->setQuery($query);
        $res = $db->loadObjectList();

        $res_table = array();
        foreach ($res as $r)
            $res_table[$r->date] = $r->user;

        return $res_table;
    }
    
    public static function buildCalendar($year, $week)
    {
        $str = '<input type="submit" class="prev" value="Prev" style="width:50px"/>'.
            'week '.$week.
            '<input type="submit" class="next" value="Next" style="width:50px"/>';

        $date = new DateTime();

        $module = JModuleHelper::getModule('mod_tennis');
        $params = new JRegistry($module->params);
        $begin = $params->get('start_hour', 8);
        $end = $params->get('end_hour', 20);
        
        $str .= '<table class="table">';

        $headings = array('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche');
        $str .= '<tr class="row"><td></td><td class="day-head">'
            .implode('</td><td class="day-head">',$headings).'</td></tr>';

        $str .= '<tr class="row"><td></td>';
        for ($i = 1; $i <= 7; $i++) {
            $date->setISODate($year, $week, $i);
            $str .= '<td class=day-np>'.$date->format('d/m/y').'</td>';
        }
        $str .= '</tr>';

        $table = ModTennisHelper::getWeekReservation($year, $week);
        
        for ($h = $begin; $h <= $end; $h++) {
    
            $str .= '<tr class="row">';
            $str .= '<td class="day-hour">'.$h.':00</td>';
    
            for ($i = 1; $i <= 7; $i++) {
                $date->setISODate($year, $week, $i);
                $date->setTime($h, 0, 0);
                $d = $date->format('Y-m-d H:i:s');

                if (is_null($table[$d])) {
                    $action = 'add';
                    $v = '';
                } else {
                    $action = 'del';
                    $v = 'reserved';
                }
                $str .= '<td class="day" id="cell_'.$i.'_'.$h.'" '.
                    'onclick="reserve_day(\''.$action.'\','.
                    '\''.$date->format('Y-m-d').'\','.$i.','.$h.
                    ')">'.$v.'</td>';
            }
            $str .= '</tr>';
        } 
        return $str . '</table>';
    }

    public static function getAjax() {

 		$input  = JFactory::getApplication()->input;

		$module = JModuleHelper::getModule('tennis');
		$params = new JRegistry();
		$params->loadString($module->params);

		$session = JFactory::getSession();

        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return 0;

        $calendar_year = $session->get('year');
        $calendar_week = $session->get('week');

        $date = new DateTime($input->get('date'));
        $date->setTime($input->get('hour'), 0, 0);
        $d = $date->format('Y-m-d H:i:s');
        
        $user = JFactory::getUser();
        $user = (string) $user->id;

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        switch ($cmd) {
        case 'add':

            $value = implode(',', array($db->quote($user), $db->quote($d)));

            $query->insert($db->quoteName('reservation'))
                  ->columns($db->quoteName(array('user', 'date')))
                  ->values($value);
            break;
            
        case 'del':

            $query->delete($db->quoteName('reservation'))
                  ->where($db->quoteName('user') . '=' . $db->quote($user) .
                  ' and ' . $db->quoteName('date') . '=' . $db->quote($d));
            break;
            
        case 'prev_week':

            if ($calendar_week == 1) {
                $calendar_week = 52;
                $calendar_year--;
            } else
                $calendar_week--;
            
            $session->set('week', $calendar_week);
            $session->set('year', $calendar_year);

            return ModTennisHelper::buildCalendar($calendar_year, $calendar_week);
            
        case 'next_week':

            if ($calendar_week == 52) {
                $calendar_week = 1;
                $calendar_year++;
            } else
                $calendar_week++;
            
            $session->set('week', $calendar_week);
            $session->set('year', $calendar_year);
   
            return ModTennisHelper::buildCalendar($calendar_year, $calendar_week);
        }
        
        $db->setQuery($query);
        try {
            $result = $db->query();
        } catch(Exception $e) {
            echo 'exception: ', $e->getMessage();
            return 1;
        }
        
        return 0;
    }
}

?>