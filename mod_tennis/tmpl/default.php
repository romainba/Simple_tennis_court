<?php
defined('_JEXEC') or die;

$session = JFactory::getSession();

echo '<div id="calendar">';
echo ModTennisHelper::buildCalendar($session->get('year'), $session->get('week'));
echo '</div>';

?>