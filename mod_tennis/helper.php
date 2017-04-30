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
"Il n'est pas permis de mettre deux fois le meme joueur",
"Le joueur 1 est invalide",
"Le joueur 2 est invalide",
"Le Joueur 1 a déjà une réservation",
"Le joueur 2 a déjà une réservation",
"Vous n'avez pas la permissions de réserver pour les deux joueurs");

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
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'group_id')))
              ->from($db->quoteName('#__users'));
        $db->setQuery($query);

        return $db->loadAssocList('id', 'group_id');
    }
    
    public static function loadUsersName()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'name')))
              ->from($db->quoteName('#__users'));
        $db->setQuery($query);

        return $db->loadAssocList('id', 'name');
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
        return $db->loadColumn();
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
                    $user1 = JFactory::getUser($item['user1']);
                    $user2 = JFactory::getUser($item['user2']);
                    $res_type = $item["type"];
                    if ($res_type < RES_TYPE_COURS)
                        $v = $user1->name.'<br>+' . $user2->name;
                    else
                        $v = RES_TYPE[$res_type];
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
            'Si vous jouez avec un invité vous pouvez selectionner "invité". ' .
            '<p>Le joueur est identifié avec le prénom suivit du nom sans accent.</p>' .
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

    public static function checkUserBusy($db, $query, $user)
    {
        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $query->select($db->quoteName('date'))
              ->from($db->quoteName('#__reservation'))
              ->where("(" . $db->quoteName('user1') . "=" . $db->quote($user) . " or " .
              $db->quoteName('user2') . "=" . $db->quote($user) .") and " .
              $db->quoteName('date') . ">=" . $db->quote($today->format('Y-m-d H:00:00')) .
              " and " . $db->quoteName('type') . "<" . $db->quote(RES_TYPE_COURS));

        $db->setQuery($query);
        $result = $db->query();
        $query->clear();
        return $result->num_rows >= 1; //$params->get('max_reserv', 1);
    }
    
    public static function resAdd($db, $query, $user1, $user2, $date, $type)
    {
        if ($type < RES_TYPE_COURS) {
            if (ModTennisHelper::checkUserBusy($db, $query, $user1))
                return ERR_USER1_BUSY;

            if (ModTennisHelper::checkUserBusy($db, $query, $user2))
                return ERR_USER2_BUSY;
        }
        
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
            $user = JFactory::getUser();
            if ($user->guest)
                return ERR_GUEST;

            $session = JFactory::getSession();

            $usersGroup = $session->get('usersGroup');
            $usersName = $session->get('usersName');

            if ($session->get('userId') != $user->id) {
                # if user change then reload details */
                $usersGroup = NULL;
                $usersName = NULL;
                $session->set('userId', $user->id);
            }

            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            if ($usersGroup == NULL) {
                $usersGroup = ModTennisHelper::loadUsersGroup();
                $session->set('usersGroup', $usersGroup);
            }
           
            if ($usersName == NULL) {
                $usersName = ModTennisHelper::loadUsersName();
                $session->set('usersName', $usersName);
            }

            $inc = $session->get('date') + $input->get('date');

            $date = new DateTime('now', timezone_open('Europe/Zurich'));
            $date->modify($inc . "day");
            $date->setTime($input->get('hour'), 0, 0);
            $d = $date->format('Y-m-d H:i:s');

            $resType = $input->get('resType');

            /* check day/hour status */
            $query->select($db->quoteName(array('user1','user2','type')))
                  ->from($db->quoteName('#__reservation'))
                  ->where($db->quoteName('date') . '=' . $db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();
            $query->clear();
            
            if (is_null($result)) {

                if ($resType < RES_TYPE_COURS) {
                    /* check both players */
                    $p1 = str_replace("_", " ", $input->get('player1'));
                    $p2 = str_replace("_", " ", $input->get('player2'));

                    $user1 = array_search($p1, $usersName);
                    if ($user1 == false)
                        return ERR_USER1_INVAL;

                    $user2 = array_search($p2, $usersName);
                    if ($user2 == false)
                        return ERR_USER2_INVAL;

                    if ($user1 == $user2)
                        return ERR_SAMEUSER;

                    $grp = $usersGroup[$user->id];
                    if ($usersGroup[$user1] != $grp && $usersGroup[$user2] != $grp)
                        return ERR_NOT_ALLOWED;
                    
                    $ret = ModTennisHelper::resAdd($db, $query, $user1, $user2, $d, $resType);
                    if ($ret)
                        return $ret;

                    return $p1 . '<br>+' . $p2;
                } else {
                    $ret = ModTennisHelper::resAdd($db, $query, $user->id, NULL, $d, $resType);
                    if ($ret)
                        return $ret;
                    
                    return RES_TYPE[$resType];
                }
                
            } else {
                /* rejected if already reserved by another user */
                if (!$user->authorise('core.admin') && !in_array($result[0], $usersSameGroup) &&
                ($resType == RES_TYPE_NORMAL))
                    return ERR_BUSY;

                /* normal reservation can't override cours/manif reservation */
                if ($result[2] >= RES_TYPE_COURS && $resType < RES_TYPE_COURS)
                    return ERR_BUSY;

                /* send an email if normal reservation replaced by cours ? */
                
                if (ModTennisHelper::resDel($db, $query, $d))
                    return ERR_INTERNAL;

                if ($resType >= RES_TYPE_COURS && $result[2] != $resType) {
                    if (ModTennisHelper::resAdd($db, $query, NULL, NULL, $d, $resType))
                        return ERR_INTERNAL;
                    return RES_TYPE[$resType];
                } else
                    return "";
                
            }

        case 'prevCal':
        case 'nextCal':
        case 'refreshCal':
            return ModTennisHelper::buildCalendar($cmd, $input->get('width'));

        case 'getStrings':
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS);

        case 'getUsersName':
            return ModTennisHelper::getUsersName();

        case 'showSelectPlayers':
            return ModTennisHelper::showSelectPlayers($input->get('date'), $input->get('hour'));
        }
    }
}

?>