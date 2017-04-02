<?php

JHTML::_('behavior.modal', 'a.modal');

const hourWidth = 60;
const cellWidth = 100;

const RES_TYPE_NONE = 0;
const RES_TYPE_MEMBER = 1;
const RES_TYPE_GUEST = 2;
const RES_TYPE_COURS = 3;
const RES_TYPE_MANIF = 4;

const RES_TYPE = array("", "membre", "invité", "cours de tennis", "manifestation");
const RES_TYPE_CLASS = array("day", "day-busy", "day-busy", "day-cours", "day-manif");

const GRP_MANAGER = 6; /* from table */

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;
const ERR_BUSY = 4;
const ERR_MAXRESERV = 5;

const ERR_NAMES = array("", "Requête invalide", "Veuillez vous identifier.",
    "Erreur interne", "Horaire déjà occupé.", "Nombre de réservation max atteint.");

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

        $query->select($db->quoteName(array('id', 'group_id')))
              ->from($db->quoteName('#__users'));
        $db->setQuery($query);

        return $db->loadAssocList('id');
    }

    public static function usersSameGroup($details, $user)
    {
        if ($details == NULL)
            return NULL;

        $g = $details[$user->id]['group_id'];
        $a = array();
        foreach ($details as $u => $d)
            if ($d['group_id'] == $g)
                array_push($a, $u);
        return $a;
    }

    public static function showUsersList()
    {
        /* show users per group_id */
        $db = JFactory::getDbo();
        $query= $db->getQuery(true);

        $db->setQuery($query);
        $query->select($db->quoteName(array('id', 'name', 'username', 'email',
        'group_id', 'abonnement', 'naissance')))
              ->from($db->quoteName('#__users'))
              ->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $u = $db->loadAssocList('id');

        $s = '<table class="usersList" style="width:100%;">';
        $s .= '<tr>';
        foreach ($u as $id => $d) {
            $s .= '<tr user_id="'.$id.'">';
            $s .= '<td id="name">' . $d['name'] . '</td>';
            $s .= '<td id="username">' . $d['username'] . '</td>';
            $s .= '<td id="email">' . $d['email'] . '</td>';
            $s .= '<td id="group_id">' . $d['group_id'] . '</td>';
            $s .= '<td id="abo">' . $d['abonnement'] . '</td>';
            $s .= '<td id="birth">' . $d['naissance'] . '</td>';
            $s .= '</tr>';
        }
        $s .= '</table>';
        return $s;
    }

    public static function buildCalendar($cmd, $width)
    {
        $w = $width - hourWidth;
        $num = ($w / cellWidth) >> 0;
        if ($num > 7)
            $num = 7;

        $session = & JFactory::getSession();
        $inc = $session->get('date');

        if ($cmd == 'prevCal')
            $inc--;
        else if ($cmd == 'nextCal')
            $inc++;

        if ($cmd == 'refreshCal')
            $session->set('width', $width);
        else
            $session->set('date', $inc);

        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $date->modify($inc . ' days');

        $user = JFactory::getUser();
        $groups = $user->get('groups');

        $str = '<table class="calendar_header">';

        $str .= '<tr><td>Utilisateur: '.$user->name.'</td>' .
            '<td style="text-align:right">Il est '.$today->format('G:i').'</td></tr>';

        $str .= '<tr><td>' .
            '<input type="submit" class="weekBtn" value="<<" id="prevCal"/>' .
            $date->format('d M') .
            '<input type="submit" class="weekBtn" value=">>" id="nextCal"/>' .
            '</td>';

        $str .= '<td style="text-align:right">';
        if (in_array(GRP_MANAGER, $groups)) {
            $str .= '  type de réservation:<select id="resTypeList">';
            for ($i = 1; $i < sizeof(RES_TYPE); $i++)
                $str .= "<option value=".$i.">".RES_TYPE[$i]."</option>";
            $str .= '</select>';
        }
        $str .= "</td></tr></table>";

        $module = JModuleHelper::getModule('mod_tennis');
        $params = new JRegistry($module->params);
        $begin = $params->get('start_hour', 8);
        $end = $params->get('end_hour', 20);

        $str .= '<style>.calendar td { width:' . ($w * 100 / $num) / $width . '%; }</style>' .
            '<table class="calendar">' .
            '<tr class="weekdays"><td class="first-column"></td>';

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
            '<div class="modal-content">Selectionnez le type de réservation:<br>' .
            '<input type="button" class="resType" id="'.RES_TYPE_MEMBER.'">'.
            RES_TYPE[RES_TYPE_MEMBER].'</input><br>' .
            '<input type="button" class="resType" id="'.RES_TYPE_GUEST.'">'.
            RES_TYPE[RES_TYPE_GUEST].'</input></div></div>';

        $str .= '<div class="modal hide" id="message"></div>';

        return $str;
    }

    public static function resAdd($db, $query, $user, $date, $resType)
    {
        $value = implode(',', array($db->quote($user->id), $db->quote($date),
             $db->quote($resType)));

        $query->insert($db->quoteName('#__reservation'))
              ->columns($db->quoteName(array('user', 'date', 'partner')))
              ->values($value);
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return 1;
        }
        $query->clear();
        return 0;
    }

    public static function resDel($db, $query, $date)
    {
        $query->delete($db->quoteName('#__reservation'))
              ->where($db->quoteName('date') . '=' . $db->quote($date));
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return 1;
        }
        $query->clear();
        return 0;
    }

    public static function getAjax()
    {
 		$input  = JFactory::getApplication()->input;

		$module = JModuleHelper::getModule('tennis');
		$params = new JRegistry();
		$params->loadString($module->params);

        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return ERR_INVAL;

        $width = $input->get('width');

        switch ($cmd) {
        case 'reserve':
        case 'isUserBusy':
            $user = JFactory::getUser();
            if ($user->guest)
                return ERR_GUEST;

            $session = JFactory::getSession();

            $details = $session->get('userDetails');
            $usersSameGroup = $session->get('usersSameGroup');

            if ($session->get('userId') != $user->id) {
                # if user change then reload details and usersSameGroup */
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

            $date = new DateTime('now', timezone_open('Europe/Zurich'));
            $date->modify($inc . "day");
            $date->setTime($input->get('hour'), 0, 0);
            $d = $date->format('Y-m-d H:i:s');

            if ($cmd == 'isUserBusy' and $user->authorise('core.admin') == false) {
                /*
                 * check if max number of reservation is reached
                 */
                $u = "'" . implode("','", $usersSameGroup) . "'"; /* users in the same group */
                $today = new DateTime('now', timezone_open('Europe/Zurich'));
                $query->select($db->quoteName('date'))
                      ->from($db->quoteName('#__reservation'))
                      ->where($db->quoteName('user') . " in ($u) and " .
                      $db->quoteName('date') . ">=" . $db->quote($today->format('Y-m-d H:00:00')) .
                      " and " . $db->quoteName('partner') . "<=" . $db->quote(RES_TYPE_GUEST));

                $db->setQuery($query);
                $result = $db->query();
                $query->clear();

                if ($result->num_rows >= $params->get('max_reserv', 1))
                    return ERR_MAXRESERV;
                else
                    return "";
            }

            $resType = $input->get('resType');

            /* check day/hour status */
            $query->select($db->quoteName(array('user','partner')))
                  ->from($db->quoteName('#__reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();
            $query->clear();
            $ret = "";

            if (is_null($result)) {

                if (ModTennisHelper::resAdd($db, $query, $user, $d, $resType))
                    return ERR_INTERNAL;

                if ($resType < RES_TYPE_COURS)
                    $ret = $user->name.'<br>+';

                $ret .= RES_TYPE[$resType];

            } else {
                /* rejected if already reserved by another user */
                if (!$user->authorise('core.admin') && !in_array($result[0], $usersSameGroup) &&
                ($resType == RES_TYPE_MEMBER || $resType == RES_TYPE_GUEST))
                    return ERR_BUSY;

                /* member/guest reservation can't override cours/manif reservation */
                if ($result[1] >= RES_TYPE_COURS && $resType < RES_TYPE_COURS)
                    return ERR_BUSY;

                if (ModTennisHelper::resDel($db, $query, $d))
                    return ERR_INTERNAL;

                if ($resType >= RES_TYPE_COURS && $result[1] != $resType) {
                    if (ModTennisHelper::resAdd($db, $query, $user, $d, $resType))
                        return ERR_INTERNAL;
                    $ret .= RES_TYPE[$resType];
                }
            }
            return $ret;

        case 'prevCal':
        case 'nextCal':
        case 'refreshCal':
            return ModTennisHelper::buildCalendar($cmd, $width);

        case 'getStrings':
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS);
        }
        
        
        return $ret;
    }
}

?>