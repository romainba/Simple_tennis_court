<?php

function debug($msg) {
    echo "<script>console.log('".$msg."');</script>";
}

JHTML::_('behavior.modal', 'a.modal');

class ModTennisHelper
{
    public static function getWeekReservation($date)
    {
        $db = JFactory::getDbo();
        $query= $db->getQuery(true);

        $start = $date->format('Y-m-d');
        $date->modify("+7 days");
        $end = $date->format('Y-m-d');

        $query->select($db->quoteName(array('id', 'user', 'date', 'partner')))
              ->from($db->quoteName('reservation'))
              ->order($db->quoteName('date') . ' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
              	$db->quoteName('date').' <= '.$db->quote($end));

        $db->setQuery($query);
        $res = $db->loadObjectList();

        $res_table = array();
        foreach ($res as $r)
            $res_table[$r->date] = array("user" => $r->user, "partner" => $r->partner);

        return $res_table;
    }

    public static function buildCalendar($cmd, $width)
    {
        $w = $width - 50 /* hour width */;
        $num = ($w / 90 /* cell width */) >> 0;
        
        $session = & JFactory::getSession();
        $inc = $session->get('date');

        if ($cmd == 'prevCal')
            $inc--;
        else if ($cmd == 'nextCal')
            $inc++;
        
        if ($cmd == 'refreshCal') {
            $session->set('width', $width);
        } else
            $session->set('date', $inc);

        $today = new DateTime();
        $date = new DateTime();        
        $date->modify($inc . ' days');
        
        $str = '<style>#calendar td { width:' . ($w * 100 / $num) / $width . '%; }</style>';
        
        $str .= '<input type="submit" class="weekBtn" value="<<" id="prevCal"/>';
        $str .= $date->format('d M');
        $str .= '<input type="submit" class="weekBtn" value=">>" id="nextCal"/>';

        $module = JModuleHelper::getModule('mod_tennis');
        $params = new JRegistry($module->params);
        $begin = $params->get('start_hour', 8);
        $end = $params->get('end_hour', 20);
        
        $str .= '<table id="calendar">';
        $str .= '<tr class="weekdays"><td class="first-column"></td>';

        $d = [];
        for ($i = 0; $i < $num; $i++) {
            $d[$i] = clone $date;
            $d[$i]->modify($i . "day");
            $str .= '<td class="day-head">' . $d[$i]->format("l") . '</td>';
        }
        $str .= '</tr>';

        $table = ModTennisHelper::getWeekReservation($date);

        for ($h = $begin; $h <= $end; $h++) {
    
            $str .= '<tr class="days">';
            $str .= '<td class="day-hour first-column">'.$h.':00</td>';

            for ($i = 0; $i < $num; $i++) {
                $d[$i]->setTime($h, 0, 0);
                $item = $table[$d[$i]->format('Y-m-d H:i:s')];

                if (is_null($item)) {
                    $v = '';
                } else {
                    $user = JFactory::getUser($item['user']);
                    $v = $user->name.'<br>+'.$item["partner"];
                }

                if ($d[$i] <= $today)
                    $str .= '<td class="day-past">';
                else
                    $str .= '<td class="day" id="cell_'.$i.'_'.$h.'" '.
                        'onclick="reserveDay('.$i.','.$h.')">';
                
                $str .= $v.'</td>';
            }
            $str .= '</tr>';
        } 
        $str .= '</table>';

        $str .= '<style>' .
            '#message, #partner { top:20%; left:30%; background-color:lightyellow; ' .
            'margin:auto; text-align:left; padding:5px; ' .
            'border-radius:10px; border:solid 3px solid #000; opacity:10; }' .
            '</style>';
        
        $str .= '<div class="modal hide" id="partner">' .
            '<div class="modal-content"><p>Please select partner type</p>' .
            '<p><input type="button" class="partner" id="member">Member</input></p>' .
            '<p><input type="button" class="partner" id="guest">Guest</input></p>'.
            '</div></div>';

        $str .= '<div class="modal hide" id="message"></div>';

        return $str;
    }

    public static function getAjax() {

 		$input  = JFactory::getApplication()->input;

		$module = JModuleHelper::getModule('tennis');
		$params = new JRegistry();
		$params->loadString($module->params);

        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return "errInval";
        
        $width = $input->get('width');
        
        switch ($cmd) {
        case 'reserve':
        case 'isUserBusy':
            $user = JFactory::getUser();
            if ($user->guest)
                return "errGuest";

            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $session = & JFactory::getSession();
            $inc = $session->get('date') + $input->get('date');

            $date = new DateTime();
            $date->modify($inc . "day");
            $date->setTime($input->get('hour'), 0, 0);
            $d = $date->format('Y-m-d H:i:s');

            if ($cmd == 'isUserBusy' and $user->authorise('core.admin') == false) {
                // check if max number of reservation is reached
                $query->select($db->quoteName('date'))
                      ->from($db->quoteName('reservation'))
                      ->where($db->quoteName('user') . '=' . $db->quote($user->id));
                $db->setQuery($query);
                $result = $db->query();
                $query->clear();

                if ($result->num_rows >= $params->get('max_reserv', 1))
                    return "errMaxReserv";
                else
                    return "";
            }
            
            $partner = $input->get('partner');
            
            /* check day/hour status */
            $query->select($db->quoteName('user'))
                  ->from($db->quoteName('reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();
            $query->clear();
            
            if (is_null($result)) {
            
                /* add reservation */
                $value = implode(',', array($db->quote($user->id), $db->quote($d),
                	$db->quote($partner)));

                $query->insert($db->quoteName('reservation'))
                      ->columns($db->quoteName(array('user', 'date', 'partner')))
                      ->values($value);

                $ret = $user->name.'<br>+'.$partner;
                
            } else {
                /* rejected if already reserved by another user */
                $id = ($user->authorise('core.admin')) ? $result[0] : $user->id;
                if ($result[0] != $id)
                    return "errBusy";
                
                /* remove reservation */
                $query->delete($db->quoteName('reservation'))
                      ->where($db->quoteName('user') . '=' . $db->quote($id) .
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
            
      case 'prevCal':
      case 'nextCal':
      case 'refreshCal':
          return ModTennisHelper::buildCalendar($cmd, $width);
      }
        
        return $ret;
    }
}

?>