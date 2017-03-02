<?php
defined('_JEXEC') or die;

function printUser($val)
{
    echo '<pre>';
    print_r($val->id);
    echo ', ';
    print_r($val->name);
    echo ', ';
    print_r($val->username);
    echo  '</pre>';
}

//foreach ($users_list as $user)
//    printUser($user);

$session = JFactory::getSession();

echo '<div id="calendar">';
echo ModTennisHelper::buildCalendar($session->get('year'), $session->get('week'));
echo '</div>';

?>