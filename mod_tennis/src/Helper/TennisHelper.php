<?php

namespace Joomla\Module\Tennis\Site\Helper;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use DateInterval;
use DatePeriod;
use DateTimeZone;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

require_once dirname(__FILE__) . '/const.php';

const hourWidth = 50; /* pixel */
const cellWidth = 100; /* pixel */

function getCaller()
{   
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    return $trace[2]['function'] ?? null;
}
    
class TennisHelper
{
    private $params;
    private $db;
    private $userFactory;

    function __construct()
    {
        /* get module parameters */
        $module = ModuleHelper::getModule('mod_tennis');
        $this->params = new Registry($module->params);

        $this->db = Factory::getContainer()->get(DatabaseInterface::class);
        $this->userFactory = Factory::getContainer()->get(UserFactoryInterface::class);

        $this->tz = new DateTimeZone('Europe/Zurich');
        $this->usesName = NULL;
    }

    function setQuery(&$query)
    {
        Log::add(getCaller() . $query);
        $this->db->setQuery($query);
    }

    function getDate($inc, $hour = NULL)
    {
        $date = new Date('now', $this->tz);
        $date->modify($inc.' days');
        if ($hour != NULL)
            $date->setTime($hour, 0, 0);
        return $date;
    }

    function getWeekReservation($date)
    {
        /* delete any pending reservation first */
        $ret = $this->resDelete(null, null);
        if ($ret)
           return $ret;

        /* get now all reservation of the week */
        $start = $date->format('Y-m-d');
        $date->modify("+7 days");
        $end = $date->format('Y-m-d');

        $db = $this->db;
        $query= $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'user1', 'user2', 'date', 'type')))
              ->from($db->quoteName('#__reservation'))
              ->order($db->quoteName('date').' ASC')
              ->where($db->quoteName('date').' >= '.$db->quote($start).' and '.
                 $db->quoteName('date').' <= '.$db->quote($end));

        $this->setQuery($query);
        return $db->loadAssocList('date');
    }

    function loadUsersName()
    {
    	$db = $this->db;
        $query = $db->getQuery(true);

        $query->select($db->quoteName(array('id', 'username')))
              ->from($db->quoteName('#__users'))
              ->order($db->quoteName('name').' ASC');

        $this->setQuery($query);
        return $db->loadAssocList('id', 'username');
    }

    function buildCalHeader()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $today = new Date('now', $this->tz);

        # to fill cal-header division
        $str = "Pour réserver une plage horaire, veuillez simplement " .
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

    function fillCalCell(&$name1, &$name2, &$type)
    {
        if ($type < RES_TYPE_COURS)
            $v = $name1 .'<br>'.$name2;
        else if ($type == RES_TYPE_OPENED)
            $v = 'Rés. en cours<br>'.$name1;
        else
            $v = RES_TYPE[$type];
        return $v;
    }

    function buildCalendar($cmd, $width)
    {
        # to fill calendar division
        $w = $width - (hourWidth);
        $num = ($w / cellWidth) >> 0;
        if ($num > 7)
            $num = 7;
        $cell_width = (($w / $num) >> 0) + 1;
        $cell_width = $cell_width * 100 / $width;

        $session = Factory::getApplication()->getSession();
        $inc = $session->get('date', 0);
        Log::add('session date ' . $inc);

        if ($cmd == 'prevCal')
            $inc -= $num;
        else if ($cmd == 'nextCal')
            $inc += $num;

        if ($cmd == 'currCal')
            $session->set('width', $width);
        else
            $session->set('date', $inc);

        $today = new Date('now', $this->tz);
        $date = $this->getDate($inc);

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        $begin = $this->params->get('start_hour');
        $end = $this->params->get('end_hour');

        $str = '<style>.calendar td { width:'.$cell_width.'%;}</style>'.
            '<table class="calendar">' .
            '<tr class="weekdays"><td class="first-column"></td>';

        $d = [];
        for ($i = 0; $i < $num; $i++) {
            $d[$i] = clone $date;
            $d[$i]->modify($i." day");
            $str .= '<td class="day-head">'.$d[$i]->format("l") .
                '<br>'.$d[$i]->format('j M').'</td>';
        }
        $str .= '</tr>';

        $table = $this->getWeekReservation($date);

        for ($h = $begin; $h <= $end; $h++) {

            $str .= '<tr class="days">';
            $str .= '<td class="day-hour first-column">'.$h.':00</td>';

            for ($i = 0; $i < $num; $i++) {
                $d[$i]->setTime($h, 0, 0);
                $item = $table[$d[$i]->format('Y-m-d H:i:s')];

                if ($item) {
                    $resType = $item["type"];
                    $user1 = $this->userFactory->loadUserById($item['user1']);
                    $user2 = $item['user2'];
                    $user2 = ($user2 == NULL) ? NULL : $this->userFactory->loadUserById($user2);

                    $v = $this->fillCalCell($user1->name, $user2->name, $resType);
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

    function showSelPlayer(&$weekday, &$hour)
    {
        $app = Factory::getApplication();
        $session = $app->getSession();
        $user = $app->getIdentity();
        $date = $this->getDate($weekday + $session->get('date'), $hour);

        Log::add('showSelPlayer inc ' . $inc . ' date ' . $date);

        $ret = $this->resInsert($user->id, NULL, $date->format('Y-m-d H:i:s'), RES_TYPE_OPENED);
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

    function checkUserBusy($user)
    {
        # No limitation for invite
        $u = $this->userFactory->loadUserById($user);

        if ($u->username == "invite")
            return false;

        $today = new Date('now', $this->tz);
        $today->modify(- $this->params->get('delay') .' minutes');

        $db = $this->db;
        $query = $db->getQuery(true);
 
        $query->select($db->quoteName('date'))
            ->from($db->quoteName('#__reservation'))
            ->where("(".$db->quoteName('user1')."=".$db->quote($user)." or " .
                    $db->quoteName('user2')."=".$db->quote($user) .") and " .
                    $db->quoteName('date').">=".$db->quote($today->format('Y-m-d H:i:00')) .
                    " and ".$db->quoteName('type')."<".$db->quote(RES_TYPE_COURS));
        try {
            $this->setQuery($query);
            $result = $db->loadObjectList();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }

        $query->clear();
        return $result->num_rows >= $this->params->get('max_reserv');
    }

    function resInsert($user1, $user2, $date, $type)
    {
        $db = $this->db;
        $query = $db->getQuery(true);

        $now = new Date('now', $this->tz);

        Log::add('resInsert now ' . $now . ' user1 ' . $user1 . ' user2 ' . $user2);

        $value = implode(',', array($db->quote($user1), ($user2 == NULL) ? 'NULL' : $db->quote($user2),
            $db->quote($date), $db->quote($type), $db->quote($now->format('Y-m-d H:i:s'))));

        $query->insert($db->quoteName('#__reservation'))
              ->columns($db->quoteName(array('user1', 'user2', 'date', 'type', 'insertDate')))
              ->values($value);
        try {
            $this->setQuery($query);
            $db->execute();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    function resUpdate($user1, $user2, $date, $type)
    {
         if ($type < RES_TYPE_COURS) {
            if ($this->checkUserBusy($user1))
                return ERR_USER1_BUSY;
            if ($this->checkUserBusy($user2))
                return ERR_USER2_BUSY;
        }

        $db = $this->db;
        $values = array(
            $db->quoteName('user1').'='.$db->quote($user1),
            $db->quoteName('user2').'='.$db->quote($user2),
            $db->quoteName('type').'='.$db->quote($type));

        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__reservation'))
              ->set($values)
              ->where($db->quoteName('date').'='.$db->quote($date));
        try {
            $this->setQuery($query);
            $db->execute();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    function resDelete($userId, $date)
    {
        $db = $this->db;
        $query = $db->getQuery(true);

        $query->delete($db->quoteName('#__reservation'));

        if (is_null($date)) {
            if (is_null($userId)) {
                /* delete all opened reservation early than (now - timeout) */
                $date = new Date('now', $this->tz);
                $date->modify(- $this->params->get('timeout').' minutes');

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
            $this->setQuery($query);
            $db->execute();
        } catch(Exception $e) {
            return ERR_INTERNAL;
        }
        return 0;
    }

    function reserve(&$user, &$user1, &$user2, &$resType, &$d)
    {
        $manager = in_array(GRP_MANAGER, $user->get('groups'));
        
        /* check day/hour status */
        $db = $this->db;
        $query = $db->getQuery(true);
        $query->select($db->quoteName(array('user1','user2','type')))
              ->from($db->quoteName('#__reservation'))
              ->where($db->quoteName('date').'='.$db->quote($d));

        $this->setQuery($query);
        $result = $db->loadRow();

        if (is_null($result) && $resType < RES_TYPE_COURS)
            return ERR_TIMEOUT;

        Log::add('reserve user1 ' . $user1 . ' user2 ' . $user2 . ' current ' . json_encode($result));

        if ($result[2] == RES_TYPE_OPENED) {
            /* reservation pre-reserved, check if it is by the same user */
            if ($result[0] != $user->id)
                return ERR_BUSY;

            if ($resType < RES_TYPE_COURS) {
                /* check both players */
                $p = strtolower($user1);
                $id1 = array_search($p, $this->usersName);
                if ($id1 == false)
                    return ERR_USER1_INVAL;

                $p = strtolower($user2);
                $id2 = array_search($p, $this->usersName);
                if ($id2 == false)
                    return ERR_USER2_INVAL;

                if ($id1 == $id2)
                    return ERR_SAMEUSER;

                $user1 = $this->userFactory->loadUserById($id1);
                $user2 = $this->userFactory->loadUserById($id2);
		    
                if (!$manager) {
                    if ($id1 != $user->id && $id2 != $user->id)
                        return ERR_NOT_ALLOWED;
                }

                if ($user1->block)
                    return ERR_USER1_DISABLED;
                if ($user2->block)
                    return ERR_USER2_DISABLED;

                $v = $this->fillCalCell($user1->name, $user2->name, $resType);

            } else {
                $id1 = $user->id;
                $id2 = NULL;
                $v = RES_TYPE[$resType];
            }
            $ret = $this->resUpdate($id1, $id2, $d, $resType);
            if ($ret)
                return $ret;

        } else {
            /* rejected if already reserved by another user */

            if ($resType < RES_TYPE_COURS) {

                /* normal reservation can't override cours/manif reservation */
                if ($result[2] >= RES_TYPE_COURS)
                    return ERR_BUSY;

                $user1 = $this->userFactory->loadUserById($result[0]);
                $user2 = $this->userFactory->loadUserById($result[1]);

            } else {
                /* only admin can set cours and manif */
                if (!$manager)
                    return ERR_BUSY;
            }

            /* send an email if normal reservation replaced by cours ? */

            $ret = $this->resDelete(NULL, $d);
            if ($ret)
                return $ret;
            $v = '';

            if ($resType >= RES_TYPE_COURS && $result[2] != $resType) {
                $ret = $this->resInsert($user->id, NULL, $d, $resType);
                if ($ret)
                    return $ret;
                $v = RES_TYPE[$resType];
            }
        }
        return $v;
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
    public function getAjax()
    {
        $app = Factory::getApplication();

        $user = $app->getIdentity();
        if ($user->guest)
            return ERR_GUEST;
        if ($user->block)
            return ERR_INVAL;

        $session = $app->getSession();
        if ($session->get('userId') != $user->id) {
            # if user change then reload details */
            $this->usersName = NULL;
            $session->set('userId', $user->id);
        }
        if ($this->usersName == NULL) {
            $this->usersName = $this->loadUsersName();
        }

        $input  = $app->getInput();
        $cmd = $input->get('cmd');
        if (is_null($cmd))
            return ERR_INVAL;

        switch ($cmd) {
        case NULL:
            return ERR_INVAL;

        case 'reserve':

            if (is_null($input->get('date')) or is_null($input->get('hour'))) {
                $d = NULL;
            } else {
                $date = $this->getDate($session->get('date') + $input->get('date'),
                                       $input->get('hour'));
                $d = $date->format('Y-m-d H:i:s');
            }
            
            return $this->reserve($user, $input->get('player1'), $input->get('player2'),
                                  $input->get('resType'), $d);

        case 'reserveCancel':
            return $this->resDelete($user->id, NULL);

        case 'prevCal':
        case 'nextCal':
        case 'currCal':
            return $this->buildCalendar($cmd, $input->get('width'));

        case 'calHeader':
            return $this->buildCalHeader();

        case 'getUsersName':
            $a = array();
            foreach ($this->usersName as $id => $d)
                array_push($a, $d);
            return $a;

        case 'getStrings':
            return array(ERR_NAMES, RES_TYPE, RES_TYPE_CLASS);

        case 'selPlayer':
            return $this->showSelPlayer($input->get('date'), $input->get('hour'));

        default:
            return ERR_INTERNAL;
        }
    }
}

?>
