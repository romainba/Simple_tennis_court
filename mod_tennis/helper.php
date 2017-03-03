<?php

function debug($msg) {
    echo "<script>console.log('".$msg."');</script>";
}

class ModTennisHelper
{
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

                $id = $table[$d];
                if (is_null($id)) {
                    $v = '';
                } else {
                    $user = JFactory::getUser($id);
                    $v = $user->name;
                }
                $str .= '<td class="day" id="cell_'.$i.'_'.$h.'" '.
                    'onclick="reserve_day(\''.$date->format('Y-m-d').'\','.
                    $i.','.$h.')">'.$v.'</td>';
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
            return "errInval";

        $user = JFactory::getUser();
        if ($user->guest)
            return "errGuest";

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        if ($cmd == 'reserve') {
            # input parameters: date, hour

            $date = new DateTime($input->get('date'));
            $date->setTime($input->get('hour'), 0, 0);
            $d = $date->format('Y-m-d H:i:s');

            # check day/hour status
            $query->select($db->quoteName('user'))
                  ->from($db->quoteName('reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();
            $query->clear();

            if (is_null($result)) {
                # check if max number of reservation is reached
                $query->select($db->quoteName('date'))
                      ->from($db->quoteName('reservation'))
                      ->where($db->quoteName('user') . '=' . $db->quote($user->id));
                $db->setQuery($query);
                $result = $db->query();
                $query->clear();

                if ($result->num_rows >= $params->get('max_reserv', 1))
                    return "errMaxReserv";

                # add reservation
                $value = implode(',', array($db->quote($user->id), $db->quote($d)));

                $query->insert($db->quoteName('reservation'))
                      ->columns($db->quoteName(array('user', 'date')))
                      ->values($value);

                $ret = $user->name;
                
            } else {
                /* rejected if already reserved by another user */
                if ($result[0] != $user->id)
                    return "errBusy";
                
                # remove reservation
                $query->delete($db->quoteName('reservation'))
                      ->where($db->quoteName('user') . '=' . $db->quote($user->id) .
                      ' and ' . $db->quoteName('date') . '=' . $db->quote($d));

                $ret = '';
            }

            try {
                $db->setQuery($query);
                $result = $db->query();
            } catch(Exception $e) {
                $ret = "errInternal";
            }
            return $ret;

        } else {
            # update calendar
            
            $year = $session->get('year');
            $week = $session->get('week');

            if ($cmd == 'prev_week') {

                if ($week == 1) {
                    $week = 52;
                    $year--;
                } else
                    $week--;
            }
            else if ($cmd == 'next_week') {
                if ($week == 52) {
                    $week = 1;
                    $year++;
                } else
                    $week++;
            }
                
            $session->set('week', $week);
            $session->set('year', $year);

            return ModTennisHelper::buildCalendar($year, $week);
        }
    }
}

?>