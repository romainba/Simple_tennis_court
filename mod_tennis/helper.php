<?php

JHTML::_('behavior.modal', 'a.modal');

const hourWidth = 60;
const cellWidth = 100;

const RES_TYPE_NONE = 0;
const RES_TYPE_NORMAL = 1;
const RES_TYPE_COURS = 2;
const RES_TYPE_MANIF = 3;

const RES_TYPE = array("", "normal", "cours de tennis", "manifestation");
const RES_TYPE_CLASS = array("day", "day-busy", "day-cours", "day-manif");

const GRP_MANAGER = 6; /* from table */

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;
const ERR_BUSY = 4;
const ERR_MAXRESERV = 5;
const ERR_DUALGUEST = 6;
const ERR_INVALUSER1 = 7;
const ERR_INVALUSER2 = 8;

const ERR_NAMES = array("", "Requête invalide", "Veuillez vous identifier.",
"Erreur interne", "Horaire déjà occupé.", "Nombre de réservation max atteint.",
"Deux invités non permis", "Utilisateur 1 invalide", "Utilisateur 2 invalide");

const GUEST_NAME = "invité";

function console_log( $data ) {
  echo '<script>';
  echo 'console.log('. json_encode( $data ) .')';
  echo '</script>';
}

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

        $query->select($db->quoteName(array('id', 'group_id' ,'name')))
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
            if ($d['group_id'] == $g and $d->id != $user->id)
                array_push($a, $u);
        return $a;
    }

    public static function getUserId($details, $name)
    {
        return array_search($name, array_column($details, 'name'));
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

    public static function getUsersName()
    {
        $db = JFactory::getDbo();
        $query= $db->getQuery(true);

        $db->setQuery($query);
        $query->select($db->quoteName(array('name')))
              ->from($db->quoteName('#__users'))
            ->order($db->quoteName('name') . ' ASC');

        $db->setQuery($query);
        $ret = $db->loadColumn();
        array_push($ret, GUEST_NAME);
        return $ret;
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
            $inc -= $num;
        else if ($cmd == 'nextCal')
            $inc += $num;

        if ($cmd == 'refreshCal')
            $session->set('width', $width);
        else
            $session->set('date', $inc);

        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $date->modify($inc . ' days');

        $user = JFactory::getUser();
        $groups = $user->get('groups');

        $str = "<p>Bonjour ".$user->name.", il est ".$today->format('G:i').". " .
            "Si vous souhaitez réserver une plage horaire, veuillez simplement " .
            "sélectionner la case correspondante à l'heure et la date souhaitées.</p>";

        $str .= '<table class="calendar_header">';
        $str .= '<tr><td style="width: 10%"><input type="submit" class="weekBtn" ' .
            'value="< avant" id="prevCal"/></td>';
        $str .= '<td style="width: 80%;text-align:center">';

        # comitee members
        if (in_array(GRP_MANAGER, $groups)) {
            $str .= 'type de réservation <select id="resTypeList">';
            for ($i = 1; $i < sizeof(RES_TYPE); $i++)
                $str .= "<option value=".$i.">".RES_TYPE[$i]."</option>";
            $str .= '</select>';
        }
        $str .= '</td>';

        $str .= '<td style="width: 10%;"><input type="submit" class="weekBtn" ' .
            'value="apres >" id="nextCal"/></td>';

	    $str .= '</tr></table>';

        $module = JModuleHelper::getModule('mod_tennis');
        $params = new JRegistry($module->params);
        $begin = $params->get('start_hour', 8);
        $end = $params->get('end_hour', 20);

        $str .= '<style>.calendar td { width:' . ($w * 100 / $num) / $width . '%; }</style>' .
            '<table class="calendar">' .
            '<tr class="weekdays"><td class="day-hour first-column"></td>';

        $d = [];
        for ($i = 0; $i < $num; $i++) {
            $d[$i] = clone $date;
            $d[$i]->modify($i . "day");
            $str .= '<td class="day-head">' . $d[$i]->format("l") .
                '<br>'.$d[$i]->format('j M') . '</td>';
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

        return $str;
    }

    public static function showSelectPlayers($weekday, $hour)
    {
        $session = & JFactory::getSession();
        $user = JFactory::getUser();

        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $inc = $weekday + $session->get('date');
        $date->modify($inc . ' days');
        
        $str = '<div class="resRequest">Veuillez sélectionner les deux joueurs pour la réservation du ' .
            $date->format('d-m-Y') . ' à ' . $hour . ' heure. ' .
            'Si vous jouez avec un invité vous pouvez selectionner "invité".' .
            '<div id="SPmsg"></div>' .
            '<div>joueur 1 <input type="text" class="player" id="player1" list="userlist"' .
            'required value ="' . $user->name . '" /></div>' .
            '<div>joueur 2 <input type="text" class="player" id="player2" list="userlist"' .
            'required value ="" /></div>' .
            '<input type="button" id="reserveBtn" class="btn btn-default" value="Réserver" align="left"/>' .
            '<input type="button" id="cancelBtn" class="btn btn-default" value="Annuler" align="right"/>' .
            '</div>';
        
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

                /* check both player */
                $p1 = str_replace("_", " ", $input->get('player1'));
                $p2 = str_replace("_", " ", $input->get('player2'));
                
                if ($p1 == '' || $p2 == '')
                    return ERR_INVAL;

                return $p1 . "__" . GUEST_NAME;

                if ($p1 == GUEST_NAME && $p2 == GUEST_NAME)
                    return ERR_DUALGUEST;

                if ($p1 == GUEST_NAME || $p2 == GUEST_NAME)
                    $resType = RES_TYPE_GUEST;
              
                $user1 = ModTennisHelper::getUserId($details, $p1);
                if ($user1 == '')
                    return ERR_INVALUSER1;
                    
                $user2 = ModTennisHelper::getUserId($details, $p2);
                if ($user2 == '')
                    return ERR_INVALUSER2;
                
                if (ModTennisHelper::resAdd($db, $query, $user, $d, $resType))
                    return ERR_INTERNAL;

                if ($resType < RES_TYPE_COURS)
                    $ret = $user->name.'<br>+';

                $ret .= RES_TYPE[$resType];

            } else {
                /* rejected if already reserved by another user */
                if (!$user->authorise('core.admin') && !in_array($result[0], $usersSameGroup) &&
                ($resType == RES_TYPE_NORMAL))
                    return ERR_BUSY;

                /* normal reservation can't override cours/manif reservation */
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
            return ModTennisHelper::buildCalendar($cmd, $input->get('width'));

        case 'getStrings':
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS, GUEST_NAME);

        case 'getUsersName':
            return ModTennisHelper::getUsersName();

        case 'showSelectPlayers':
            return ModTennisHelper::showSelectPlayers($input->get('date'), $input->get('hour'));
        }


        return $ret;
    }
}

?>