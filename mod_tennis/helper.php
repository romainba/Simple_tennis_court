<?php

JHTML::_('behavior.modal', 'a.modal');

const hourWidth = 60;
const cellWidth = 100;

const RES_TYPE_NONE = 0;
const RES_TYPE_NORMAL = 1;
const RES_TYPE_COURS = 2; /* cours de tennis */
const RES_TYPE_MANIF = 3; /* manifestation */
const RES_TYPE_OPENED = 4; /* reservation on-going */

const RES_TYPE = array("", "normal", "cours de tennis", "manifestation", "réservation<br>en cours");
const RES_TYPE_CLASS = array("day", "day-busy", "day-cours", "day-manif", "day-past");

const GRP_MANAGER = 6; /* from table */

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;
const ERR_BUSY = 4;
const ERR_SAMEUSER = 5;
const ERR_USER1_INVAL = 6;
const ERR_USER2_INVAL = 7;
const ERR_USER1_BUSY = 8;
const ERR_USER2_BUSY = 9;
const ERR_NOT_ALLOWED = 10;

const ERR_NAMES = array("",
"La requête est invalide",
"Veuillez vous identifier",
"Une erreur interne s'est produite",
"Horaire déjà occupé",
"Il n'est pas permis de mettre deux fois le m&ecirc;me joueur",
"Le premier joueur est invalide",
"Le deuxi&egrave;me joueur est invalide",
"Le premier joueur a déjà une réservation",
"Le deuxi&egrave;me joueur a déjà une réservation",
"Vous n'avez pas la permissions de réserver pour les deux joueurs");

class ModTennisHelper
{
    public static function getWeekReservation($date)
    {
        $db = &JFactory::getDbo();
        $query= $db->getQuery(true);

        $start = $date->format('Y-m-d');
        $date->modify("+7 days");
        $end = $date->format('Y-m-d');

        $query->select($db->quoteName(array('id', 'user1', 'user2', 'date', 'type')))
              ->from($db->quoteName('#__reservation'))
              ->order($db->quoteName('date') . ' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
              	$db->quoteName('date').' <= '.$db->quote($end));

        $db->setQuery($query);
        return $db->loadAssocList('date');
    }

    public static function loadUsersGroup()
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'group_id')))
              ->from($db->quoteName('#__users'));
        $db->setQuery($query);

        return $db->loadAssocList('id', 'group_id');
    }
    
    public static function loadUsersName()
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'username')))
              ->from($db->quoteName('#__users'))
              ->order($db->quoteName('name') . ' ASC');
        $db->setQuery($query);

        return $db->loadAssocList('id', 'username');
    }

    public static function showUsersList()
    {
        /* show users per group_id */
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

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

    public static function buildCalHeader()
    {
        $user = &JFactory::getUser();
        $groups = $user->get('groups');
        $today = new DateTime('now', timezone_open('Europe/Zurich'));

        # to fill cal-header division
        $str = "<p>Bonjour ".$user->name.", il est ".$today->format('G:i').". " .
            "Si vous souhaitez réserver une plage horaire, veuillez simplement " .
            "sélectionner la case correspondante à l'heure et la date souhaitées.</p>".
            "<p>Pour annuler une réservation, il suffit de resélectionner votre case.</p>";

        $str .= '<table class="calendar_header">';
        $str .= '<tr><td style="width: 10%"><input type="submit" class="weekBtn" ' .
            'value="< avant" id="prevCal"/></td>';
        $str .= '<td style="width: 80%;text-align:center">';

        # comitee members
        if (in_array(GRP_MANAGER, $groups)) {
            $str .= 'type de réservation <select id="resTypeList">';
            for ($i = 1; $i <= RES_TYPE_MANIF; $i++)
                $str .= "<option value=".$i.">".RES_TYPE[$i]."</option>";
            $str .= '</select>';
        }
        $str .= '</td>';

        $str .= '<td style="width: 10%;"><input type="submit" class="weekBtn" ' .
            'value="apres >" id="nextCal"/></td>';

	    $str .= '</tr></table>';

        return $str;
    }

    public static function fillCalCell($id1, $id2, $type)
    {
        $user1 = JFactory::getUser($id1);
        $user2 = JFactory::getUser($id2);
        if ($type < RES_TYPE_COURS) {
            $v = $user1->name .'<br>' . $user2->name;
            //$v .= '<span class="tooltiptext">Reservation pour '.$user1->name.
            //   ' et '.$user2->name.'</span>';
        } else
            $v = RES_TYPE[$type];
        return $v;
    }

    public static function buildCalendar($cmd, $width)
    {
        # to fill calendar division
        $w = $width - hourWidth;
        $num = ($w / cellWidth) >> 0;
        if ($num > 7)
            $num = 7;

        $session = &JFactory::getSession();
        $inc = $session->get('date');

        if ($cmd == 'prevCal')
            $inc -= $num;
        else if ($cmd == 'nextCal')
            $inc += $num;

        if ($cmd == 'currCal')
            $session->set('width', $width);
        else
            $session->set('date', $inc);

        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $date->modify($inc . ' days');

        $user = &JFactory::getUser();
        $groups = $user->get('groups');

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

                if ($item) {
                    $resType = $item["type"];
                    $v = ModTennisHelper::fillCalCell($item['user1'], $item['user2'], $resType);
                } else {
                    $resType = RES_TYPE_NONE;
                    $v = '';
                }
                if ($d[$i] <= $today || $resType == RES_TYPE_OPENED)
                    $str .= '<td class="day-past">';
                else {
                    $str .= '<td class="'.RES_TYPE_CLASS[$resType].'" id="cell_'.
                        $i.'_'.$h.'" onclick="reserveDay('.$i.','.$h.')">';
                }
                $str .= $v.'</td>';
            }
            $str .= '</tr>';
        }
        $str .= '</table>';

        return $str;
    }

    public static function showSelPlayer($weekday, $hour)
    {
        $session = &JFactory::getSession();
        $user = &JFactory::getUser();
        
        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $inc = $weekday + $session->get('date');
        $date->modify($inc . ' days');
        $date->setTime($hour, 0, 0);

        /* save userGroup into user1 field */
        $ret = ModTennisHelper::resInsert($user->id, NULL, $date->format('Y-m-d H:i:s'),
        	RES_TYPE_OPENED);
        if ($ret)
            return $ret;

        /* to fill resRequest div */
        $str = '<p>Veuillez sélectionner les deux joueurs '.
            'pour la réservation du ' . $date->format('d M') . ' à ' . $hour . ' heure. '.
            'Si vous jouez avec un invité, veuillez selectionner "invite".</p>'.
            '<p>Le joueur est identifié avec le prénom.nom et sans accent.</p>'.
            '<p>Dans le cas o&ugrave; vous avez un emp&ecirc;chement pour honnorer votre réservation, '.
            "vous &ecirc;tes cordialement invit&eacute; &agrave; l'annuler afin que le court soit ".
            ' libre pour un autre membre.</p>'.
            
            '<div id="SPmsg"></div>'.

            '<div style="clear:both;padding:5px;">'.
            '<div style="float:left;margin-right:3px;margin-top:5px;">Joueur 1</div>'.
            '<input list="userlist" class="player" id="player1"'.
            'value ="' . $user->username . '"/>'.
            '</div>'.

            '<div style="clear:both;padding:5px;">'.
            '<div style="float:left;margin-right:3px;margin-top:5px;">Joueur 2</div>'.
            '<input list="userlist" class="player" id="player2"/>'.
            '</div>'.
            
            '<div style="clear:both;padding:5px;">'.
            '<input type="button" id="reserveBtn" class="btn btn-default" value="Réserver"/>'.
            '<input type="button" id="cancelBtn" class="btn btn-default" value="Annuler" '.
            'style="float: right"/>'.
            '</div>';
        
        return $str;
    }

    public static function checkUserBusy(&$db, &$query, $user)
    {
        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $query->select($db->quoteName('date'))
              ->from($db->quoteName('#__reservation'))
              ->where("(" . $db->quoteName('user1') . "=" . $db->quote($user) . " or " .
              $db->quoteName('user2') . "=" . $db->quote($user) .") and " .
              $db->quoteName('date') . ">=" . $db->quote($today->format('Y-m-d H:00:00')) .
              " and " . $db->quoteName('type') . "<" . $db->quote(RES_TYPE_COURS));

        try {
            $db->setQuery($query);
            $result = $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }

        $query->clear();
        return $result->num_rows >= 1; //$params->get('max_reserv', 1);
    }
    
    public static function resInsert($user1, $user2, $date, $type)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $value = implode(',', array($db->quote($user1), $db->quote($user2),
        	$db->quote($date), $db->quote($type)));

        $query->insert($db->quoteName('#__reservation'))
              ->columns($db->quoteName(array('user1', 'user2', 'date', 'type')))
              ->values($value);
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    public static function resUpdate($user1, $user2, $date, $type)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        if ($type < RES_TYPE_COURS) {
            if (ModTennisHelper::checkUserBusy($db, $query, $user1))
                return ERR_USER1_BUSY;
            if (ModTennisHelper::checkUserBusy($db, $query, $user2))
                return ERR_USER2_BUSY;
        }
 
        $values = array(
            $db->quoteName('user1') . '=' . $db->quote($user1),
            $db->quoteName('user2') . '=' . $db->quote($user2),
            $db->quoteName('type') . '=' . $db->quote($type));
 
        $query->update($db->quoteName('#__reservation'))
              ->set($values)
              ->where($db->quoteName('date') . '=' . $db->quote($date));
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    public static function resDelete($userId, $date)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->delete($db->quoteName('#__reservation'));

        if (is_null($date)) {
            if (is_null($userId))
                return ERR_INVAL;
            $query->where(array(
                $db->quoteName('user1') . '=' . $db->quote($userId),
                $db->quoteName('type') . '=' . $db->quote(RES_TYPE_OPENED)
            ));
        } else
            $query->where($db->quoteName('date') . '=' . $db->quote($date));
        
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    /* List of Ajax commands:
     *
     * 'getStrings' provides message strings to client
     *    param: none
     *
     * 'reserve' reserves a given date/hour
     *    param: user1, user2, date, hour, type
     *
     * 'free' frees a given date/hour
     *    param: date, hour
     *
     * 'currCal' draw current calendar
     *    param: none
     *
     * 'prevCal' shift the date earlier and draw the calendar
     *    param: none
     *
     * 'nextCal' shift the date later and draw the calendar
     *    param: none
     *
     * 'calHeader' provides calendar header
     *    param: none
     *
     * 'getUsersName' provide all usernames
     *    param: none
     *
     * 'selPlayer' show players selection form
     *    param: date, hour
     */
    public static function getAjax()
    {
 		$input  = JFactory::getApplication()->input;

		$module = JModuleHelper::getModule('tennis');
		$params = new JRegistry();
		$params->loadString($module->params);

        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return ERR_INVAL;

        if ($cmd == 'getStrings')
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS);
            
        $user = &JFactory::getUser();
        if ($user->guest)
            return ERR_GUEST;

        $session = &JFactory::getSession();
        $usersGroup = $session->get('usersGroup');
        $usersName = $session->get('usersName');

        if ($session->get('userId') != $user->id) {
            # if user change then reload details */
            $usersGroup = NULL;
            $usersName = NULL;
            $session->set('userId', $user->id);
        }

        if ($usersGroup == NULL) {
            $usersGroup = ModTennisHelper::loadUsersGroup();
            $session->set('usersGroup', $usersGroup);
        }
           
        if ($usersName == NULL) {
            $usersName = ModTennisHelper::loadUsersName();
            $session->set('usersName', $usersName);
        }

        if ($cmd == 'reserve' or $cmd == 'free') {

            if (is_null($input->get('date')) or is_null($input->get('hour'))) {
                $d = NULL;
            } else {
                $inc = $session->get('date') + $input->get('date');

                $date = new DateTime('now', timezone_open('Europe/Zurich'));
                $date->modify($inc . "day");
                $date->setTime($input->get('hour'), 0, 0);
                $d = $date->format('Y-m-d H:i:s');
            }
        }
        
        switch ($cmd) {
        case 'reserve':
           
            $manager = in_array(GRP_MANAGER, $user->get('groups'));
            $resType = $input->get('resType');
            $grp = $usersGroup[$user->id];

            /* check day/hour status */
            $db = &JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('user1','user2','type')))
                  ->from($db->quoteName('#__reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();

            if (is_null($result) && $resType < RES_TYPE_COURS)
                return ERR_INTERNAL;
            
            if ($result[2] == RES_TYPE_OPENED) {

                if ($result[0] != $user->id)
                    return ERR_BUSY;
                
                if ($resType < RES_TYPE_COURS) {
                    /* check both players */
                    $p = strtolower(str_replace("_", " ", $input->get('player1')));
                    $user1 = array_search($p, $usersName);
                    if ($user1 == false)
                        return ERR_USER1_INVAL;

                    $p = strtolower(str_replace("_", " ", $input->get('player2')));
                    $user2 = array_search($p, $usersName);
                    if ($user2 == false)
                        return ERR_USER2_INVAL;

                    if ($user1 == $user2)
                        return ERR_SAMEUSER;

                    if ($usersGroup[$user1] != $grp && $usersGroup[$user2] != $grp)
                        return ERR_NOT_ALLOWED;
                    
                    $v = ModTennisHelper::fillCalCell($user1, $user2, $resType);

                } else {
                    $user1 = $user->id;
                    $user2 = NULL;
                    $v = RES_TYPE[$resType];
                }
                $ret = ModTennisHelper::resUpdate($user1, $user2, $d, $resType);
                if ($ret)
                    return $ret;

            } else {
                /* rejected if already reserved by another user */

                if ($resType < RES_TYPE_COURS) {

					/* normal reservation can't override cours/manif reservation */
                    if ($result[2] >= RES_TYPE_COURS)
                        return ERR_BUSY;

                    if (!$manager &&
                    ($usersGroup[$result[0]] != $grp) && ($usersGroup[$result[1]] != $grp))
                        return ERR_BUSY;

                } else {
                    /* only admin can set cours and manif */
                    if (!$manager)
                        return ERR_BUSY;
                }

                /* send an email if normal reservation replaced by cours ? */
                
                $ret = ModTennisHelper::resDelete(NULL, $d);
                if ($ret)
                    return $ret;
                $v = '';

                if ($resType >= RES_TYPE_COURS && $result[2] != $resType) {
                    $ret = ModTennisHelper::resInsert($user->id, NULL, $d, $resType);
                    if ($ret)
                        return $ret;
                    $v = RES_TYPE[$resType];
                }
            }
            
            return $v; /* cell content */

        case 'free':
            return ModTennisHelper::resDelete($user->id, NULL);

        case 'prevCal':
        case 'nextCal':
        case 'currCal':
            return ModTennisHelper::buildCalendar($cmd, $input->get('width'));

        case 'calHeader':
            return ModTennisHelper::buildCalHeader();
            
        case 'getUsersName':
            $a = array();
            foreach ($usersName as $id => $d)
                array_push($a, $d);
            return $a;

        case 'selPlayer':
            return ModTennisHelper::showSelPlayer($input->get('date'), $input->get('hour'));

        default:
            return ERR_INTERNAL;
        }
    }
}

?>