<?php

JHTML::_('behavior.modal', 'a.modal');

const RES_TYPE_NONE = 0;
const RES_TYPE_MEMBER = 1;
const RES_TYPE_GUEST = 2;
const RES_TYPE_COURS = 3;
const RES_TYPE_MANIF = 4;

const RES_TYPE = array("", "membre", "invite", "cours de tennis", "manifestation");
const RES_TYPE_CLASS = array("day", "day-busy", "day-busy", "day-cours", "day-manif");

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
              ->from($db->quoteName('#__reservation'))
              ->order($db->quoteName('date') . ' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
              	$db->quoteName('date').' <= '.$db->quote($end));

        $db->setQuery($query);
        return $db->loadAssocList('date');
    }

    public static function loadUserDetails()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('user', 'group_id', 'abonnement', 'naissance')))
              ->from($db->quoteName('#__user_details'));
        $db->setQuery($query);

        return $db->loadAssocList('user');
    }

    public static function usersSameGroup($details, $user)
    {
        if ($details == NULL)
            return NULL;

        $g = $details[$user->id]['group'];
        $a = array();
        foreach ($details as $u => $d)
            if ($d['group'] == $g)
                array_push($a, $u);
        return $a;
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

        $str .= '<div style="width:100%;">';
        $str .= '<div style="float:left;">';
        $str .= '<input type="submit" class="weekBtn" value="<<" id="prevCal"/>';
        $str .= $date->format('d M');
        $str .= '<input type="submit" class="weekBtn" value=">>" id="nextCal"/>';
        $str .= "</div>";

        $user = JFactory::getUser();
        $groups = $user->get('groups');

        /* manager is group 6 */
        $str .= '<div style="float:right;">';
        if (in_array(6, $groups)) {
            $str .= '  type de reservation:<select id="resTypeList" style="width:120px;">';
            for ($i = 1; $i < sizeof(RES_TYPE); $i++)
                $str .= "<option value=".$i.">".RES_TYPE[$i]."</option>";
            $str .= '</select>';
        }
        $str .= "</div></div>";

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

                $v = '';
                $res_type = RES_TYPE_NONE;
                if ($item) {
                    $user = JFactory::getUser($item['user']);
                    $res_type = $item["partner"];
                    if ($res_type < RES_TYPE_COURS)
                        $v = $user->name.'<br>+';
                    $v .= RES_TYPE[$res_type];
                }

                if ($d[$i] <= $today)
                    $str .= '<td class="day-past">';
                else {
                    $class = RES_TYPE_CLASS[$res_type];
                    $str .= '<td class="'.$class.'" id="cell_'.$i.'_'.$h.'" '.
                        'onclick="reserveDay('.$i.','.$h.')">';
                }
                $str .= $v.'</td>';
            }
            $str .= '</tr>';
        }
        $str .= '</table>';

        $str .= '<div class="modal hide" id="resType">' .
            '<div class="modal-content"><p>Selectionnez le type de reservation</p>' .
            '<p><input type="button" class="resType" id="1">'.RES_TYPE[RES_TYPE_MEMBER] .
            '</input></p>' .
            '<p><input type="button" class="resType" id="2">'.RES_TYPE[RES_TYPE_GUEST] .
            '</input></p></div></div>';

        $str .= '<div class="modal hide" id="message"></div>';

        return $str;
    }

    public static function getAjax()
    {
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

            $session = JFactory::getSession();

            $details = $session->get('userDetails');
            $usersSameGroup = $session->get('usersSameGroup');

            if ($session->get('userId') != $user->id) {
                # change user
                $details = NULL;
                $usersSameGroup = NULL;
                $session->set('userId', $user->id);
            }

            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            if ($details == NULL) {
                $details = ModTennisHelper::loadUserDetails();
                $session->set('userDetails', $details);
            }

            if ($usersSameGroup == NULL) {
                $usersSameGroup = ModTennisHelper::usersSameGroup($details, $user);
                $session->set('usersSameGroup', $usersSameGroup);
            }

            $inc = $session->get('date') + $input->get('date');

            $date = new DateTime();
            $date->modify($inc . "day");
            $date->setTime($input->get('hour'), 0, 0);
            $d = $date->format('Y-m-d H:i:s');

            $today = new DateTime();
            $t = $today->format('Y-m-d H:00:00');

            if ($cmd == 'isUserBusy' and $user->authorise('core.admin') == false) {
                // check if max number of reservation is reached
                $u = "'" . implode("','", $usersSameGroup) . "'";
                $query->select($db->quoteName('date'))
                      ->from($db->quoteName('#__reservation'))
                      ->where($db->quoteName('user')." in ($u) and " .
                      $db->quoteName('date')." >= ".$db->quote($t) ." and ".
                      $db->quoteName('partner')." <= ".RES_TYPE_GUEST);

                $db->setQuery($query);
                $result = $db->query();
                $query->clear();

                if ($result->num_rows >= $params->get('max_reserv', 1))
                    return "errMaxReserv";
                else
                    return "";
            }

            $resType = $input->get('resType');

            /* check day/hour status */
            $query->select($db->quoteName('user'))
                  ->from($db->quoteName('#__reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();
            $query->clear();
            $ret = "";

            if (is_null($result)) {

                /* add reservation */
                $value = implode(',', array($db->quote($user->id), $db->quote($d),
                	$db->quote($resType)));

                $query->insert($db->quoteName('#__reservation'))
                      ->columns($db->quoteName(array('user', 'date', 'partner')))
                      ->values($value);

                if ($resType < RES_TYPE_COURS)
                    $ret = $user->name.'<br>+';

                $ret .= RES_TYPE[$resType];

            } else {
                /* rejected if already reserved by another user */
                if (!$user->authorise('core.admin') && !in_array($result[0], $usersSameGroup))
                    return "errBusy";

                /* remove reservation */
                $query->delete($db->quoteName('#__reservation'))
                      ->where($db->quoteName('date') . '=' . $db->quote($d));
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