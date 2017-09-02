<?php

JHTML::_('behavior.modal', 'a.modal');

require_once dirname(__FILE__) . '/const.php';

const hourWidth = 50; /* pixel */
const cellWidth = 100; /* pixel */

class ModTennisHelper
{
    public static function getWeekReservation($date, &$params)
    {
        /* delete any pending reservation first */
        $ret = ModTennisHelper::resDelete(null, null, $params);
        if ($ret)
            return $ret;

        /* get now all reservation of the week */
        $start = $date->format('Y-m-d');
        $date->modify("+7 days");
        $end = $date->format('Y-m-d');

        $db = &JFactory::getDbo();
        $query= $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'user1', 'user2', 'date', 'type')))
              ->from($db->quoteName('#__reservation'))
              ->order($db->quoteName('date').' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
              	$db->quoteName('date').' <= '.$db->quote($end));

        $db->setQuery($query);
        return $db->loadAssocList('date');
    }

    public static function loadUsersName()
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'username')))
              ->from($db->quoteName('#__users'))
              ->order($db->quoteName('name').' ASC');
        $db->setQuery($query);

        return $db->loadAssocList('id', 'username');
    }

    public static function buildCalHeader()
    {
        $user = &JFactory::getUser();
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
        if (in_array(GRP_MANAGER, $user->get('groups'))) {
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

    public static function fillCalCell($name1, $name2, $type)
    {
        if ($type < RES_TYPE_COURS)
            $v = $name1 .'<br>'.$name2;
        else if ($type == RES_TYPE_OPENED)
            $v = 'Rés. en cours<br>'.$name1;
        else
            $v = RES_TYPE[$type];
        return $v;
    }

    public static function buildCalendar($cmd, $width, &$params)
    {
        # to fill calendar division
        $w = $width - (hourWidth);
        $num = ($w / cellWidth) >> 0;
        if ($num > 7)
            $num = 7;
        $cell_width = (($w / $num) >> 0) + 1;
        $cell_width = $cell_width * 100 / $width;

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
        $date->modify($inc.' days');

        $user = &JFactory::getUser();

        $module = JModuleHelper::getModule('mod_tennis');
        $begin = $params->get('start_hour');
        $end = $params->get('end_hour');

        $str = '<style>.calendar td { width:'.$cell_width.'%;}</style>'.
            '<table class="calendar">' .
            '<tr class="weekdays"><td class="first-column"></td>';

        $d = [];
        for ($i = 0; $i < $num; $i++) {
            $d[$i] = clone $date;
            $d[$i]->modify($i."day");
            $str .= '<td class="day-head">'.$d[$i]->format("l") .
                '<br>'.$d[$i]->format('j M').'</td>';
        }
        $str .= '</tr>';

        $table = ModTennisHelper::getWeekReservation($date, $params);

        for ($h = $begin; $h <= $end; $h++) {

            $str .= '<tr class="days">';
            $str .= '<td class="day-hour first-column">'.$h.':00</td>';

            for ($i = 0; $i < $num; $i++) {
                $d[$i]->setTime($h, 0, 0);
                $item = $table[$d[$i]->format('Y-m-d H:i:s')];

                if ($item) {
                    $resType = $item["type"];
                    $user1 = &JFactory::getUser($item['user1']);
                    $user2 = &JFactory::getUser($item['user2']);

                    $v = ModTennisHelper::fillCalCell($user1->name, $user2->name, $resType);
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

        if (in_array(GRP_MANAGER, $user->get('groups'))) {
            $str .= '<hr><p>Exporter les reservations faites du '.
                '<input type="date" name="debut" id="exportBegin" style="width:80px"/>'.
                ' au <input type="date" name="fin" id="exportEnd" style="width:80px"/>.</p>'.
                 '<input type="submit" value="exporter" class="button" id="exportDb" />';
        }

        return $str;
    }

    public static function showSelPlayer($weekday, $hour)
    {
        $session = &JFactory::getSession();
        $user = &JFactory::getUser();

        $date = new DateTime('now', timezone_open('Europe/Zurich'));
        $inc = $weekday + $session->get('date');
        $date->modify($inc.' days');
        $date->setTime($hour, 0, 0);

        /* save userGroup into user1 field */
        $ret = ModTennisHelper::resInsert($user->id, NULL, $date->format('Y-m-d H:i:s'),
        	RES_TYPE_OPENED);
        if ($ret)
            return $ret;

        /* to fill resRequest div */
        $str = '<p>Veuillez sélectionner les deux joueurs '.
            'pour la réservation du '.$date->format('d M').' à '.$hour.' heure. '.
            'Si vous jouez avec un invité, veuillez selectionner "invite".</p>'.
            '<p>Le joueur est identifié avec le prénom.nom et sans accent.</p>'.
            '<p>Dans le cas o&ugrave; vous avez un emp&ecirc;chement pour honnorer votre réservation, '.
            "vous &ecirc;tes cordialement invit&eacute; &agrave; l'annuler afin que le court soit ".
            ' libre pour un autre membre.</p>'.

            '<div id="SPmsg"></div>'.

            '<div style="clear:both;padding:5px;">'.
            '<div style="float:left;margin-right:3px;margin-top:5px;">Joueur 1</div>'.
            '<input list="userlist" class="player" id="player1"'.
            'value ="'.$user->username.'"/>'.
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

    public static function checkUserBusy(&$db, &$query, $user, &$params)
    {
        # No limitation for invite
        $u = JFactory::getUser($user);
        if ($u->username == "invite")
            return false;

        $today = new DateTime('now', timezone_open('Europe/Zurich'));
        $today->modify(- $params->get('delay') .' minutes');

        $query->select($db->quoteName('date'))
              ->from($db->quoteName('#__reservation'))
              ->where("(".$db->quoteName('user1')."=".$db->quote($user)." or " .
              $db->quoteName('user2')."=".$db->quote($user) .") and " .
              $db->quoteName('date').">=".$db->quote($today->format('Y-m-d H:i:00')) .
              " and ".$db->quoteName('type')."<".$db->quote(RES_TYPE_COURS));

        try {
            $db->setQuery($query);
            $result = $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }

        $query->clear();
        return $result->num_rows >= $params->get('max_reserv');
    }

    public static function resInsert($user1, $user2, $date, $type)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $now = new DateTime('now', timezone_open('Europe/Zurich'));

        $value = implode(',', array($db->quote($user1), $db->quote($user2),
        $db->quote($date), $db->quote($type), $db->quote($now->format('Y-m-d H:i:s'))));

        $query->insert($db->quoteName('#__reservation'))
              ->columns($db->quoteName(array('user1', 'user2', 'date', 'type', 'insertDate')))
              ->values($value);
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    public static function resUpdate($user1, $user2, $date, $type, &$params)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        if ($type < RES_TYPE_COURS) {
            if (ModTennisHelper::checkUserBusy($db, $query, $user1, $params))
                return ERR_USER1_BUSY;
            if (ModTennisHelper::checkUserBusy($db, $query, $user2, $params))
                return ERR_USER2_BUSY;
        }

        $values = array(
            $db->quoteName('user1').'='.$db->quote($user1),
            $db->quoteName('user2').'='.$db->quote($user2),
            $db->quoteName('type').'='.$db->quote($type));

        $query->update($db->quoteName('#__reservation'))
              ->set($values)
              ->where($db->quoteName('date').'='.$db->quote($date));
        try {
            $db->setQuery($query);
            $db->query();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    public static function resDelete($userId, $date, &$params)
    {
        $db = &JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->delete($db->quoteName('#__reservation'));

        if (is_null($date)) {
            if (is_null($userId)) {
                /* delete all opened reservation early than (now - timeout) */
                $date = new DateTime('now', timezone_open('Europe/Zurich'));
                $date->modify(- $params->get('timeout').' minutes');

                $query->where(array(
                    $db->quoteName('insertDate').'<'.$db->quote($date->format('Y-m-d H:i:s')),
                    $db->quoteName('type').'='.$db->quote(RES_TYPE_OPENED)
                ));
            } else
                /* delete only opened reservation for the given user */
                $query->where(array(
                    $db->quoteName('user1').'='.$db->quote($userId),
                    $db->quoteName('type').'='.$db->quote(RES_TYPE_OPENED)
                ));
        } else
            /* delete the given reservation */
            $query->where($db->quoteName('date').'='.$db->quote($date));

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
     * 'reserveCancel' cancels reservation
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
        $params = new JRegistry($module->params);

        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return ERR_INVAL;

        if ($cmd == 'getStrings')
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS);

        $user = &JFactory::getUser();
        if ($user->guest)
            return ERR_GUEST;
        if ($user->block)
            return ERR_INVAL;

        $session = &JFactory::getSession();
        $usersName = $session->get('usersName');

        if ($session->get('userId') != $user->id) {
            # if user change then reload details */
            $usersName = NULL;
            $session->set('userId', $user->id);
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
                $date->modify($inc."day");
                $date->setTime($input->get('hour'), 0, 0);
                $d = $date->format('Y-m-d H:i:s');
            }
        }

        switch ($cmd) {
        case 'reserve':

            $manager = in_array(GRP_MANAGER, $user->get('groups'));
            $resType = $input->get('resType');
            $grp = $user->group_id;

            /* check day/hour status */
            $db = &JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select($db->quoteName(array('user1','user2','type')))
                  ->from($db->quoteName('#__reservation'))
                  ->where($db->quoteName('date').'='.$db->quote($d));
            $db->setQuery($query);
            $result = $db->loadRow();

            if (is_null($result) && $resType < RES_TYPE_COURS)
                return ERR_TIMEOUT;

            if ($result[2] == RES_TYPE_OPENED) {

                if ($result[0] != $user->id)
                    return ERR_BUSY;

                if ($resType < RES_TYPE_COURS) {
                    /* check both players */
                    $p = strtolower($input->get('player1'));
                    $id1 = array_search($p, $usersName);
                    if ($id1 == false)
                        return ERR_USER1_INVAL;

                    $p = strtolower($input->get('player2'));
                    $id2 = array_search($p, $usersName);
                    if ($id2 == false)
                        return ERR_USER2_INVAL;

                    if ($id1 == $id2)
                        return ERR_SAMEUSER;

                    $user1 = &JFactory::getUser($id1);
                    $user2 = &JFactory::getUser($id2);

                    if (!$manager) {
                        if (is_null($grp)) {
                            /* user not in a group */
                            if ($id1 != $user->id && $id2 != $user->id)
                                return ERR_NOT_ALLOWED;
                        } else {
                            /* at least one user must be in the group of the current user */
                            if ($user1->group_id != $grp && $user2->group_id != $grp)
                                return ERR_NOT_ALLOWED;
                        }
                    }

                    if ($user1->block)
                        return ERR_USER1_DISABLED;
                    if ($user2->block)
                        return ERR_USER2_DISABLED;

                    $v = ModTennisHelper::fillCalCell($user1->name, $user2->name, $resType);

                } else {
                    $id1 = $user->id;
                    $id2 = NULL;
                    $v = RES_TYPE[$resType];
                }
                $ret = ModTennisHelper::resUpdate($id1, $id2, $d, $resType, $params);
                if ($ret)
                    return $ret;

            } else {
                /* rejected if already reserved by another user */

                if ($resType < RES_TYPE_COURS) {

					/* normal reservation can't override cours/manif reservation */
                    if ($result[2] >= RES_TYPE_COURS)
                        return ERR_BUSY;

                    $user1 = &JFactory::getUser($result[0]);
                    $user2 = &JFactory::getUser($result[1]);

                    /* manager can reserve for any user */
                    if (!$manager && ($user1->group_id != $grp) && ($user2->group_id != $grp))
                        return ERR_BUSY;

                } else {
                    /* only admin can set cours and manif */
                    if (!$manager)
                        return ERR_BUSY;
                }

                /* send an email if normal reservation replaced by cours ? */

                $ret = ModTennisHelper::resDelete(NULL, $d, $params);
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

        case 'reserveCancel':
            return ModTennisHelper::resDelete($user->id, NULL, $params);

        case 'prevCal':
        case 'nextCal':
        case 'currCal':
            return ModTennisHelper::buildCalendar($cmd, $input->get('width'), $params);

        case 'calHeader':
            return ModTennisHelper::buildCalHeader();

        case 'getUsersName':
            $a = array();
            foreach ($usersName as $id => $d)
                array_push($a, $d);
            return $a;

        case 'selPlayer':
            return ModTennisHelper::showSelPlayer($input->get('date'), $input->get('hour'));

        case 'exportDb':
            require_once dirname(__FILE__) . '/export.php';
            return ModTennisExporter::exportDb();

        default:
            return ERR_INTERNAL;
        }
    }
    }

?>