<?php

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

$document = JFactory::getDocument();

$format = $params->get('format', 'debug');

# Set current year and week
$date = new DateTime(date_default_timezone_get());
$session = JFactory::getSession();
$session->set('week', $date->format("W"));
$session->set('year', $date->format("Y"));
//$session->set('width', 0);

JHtml::script(Juri::base() . 'modules/mod_tennis/mod_tennis.js');
JHtml::stylesheet('modules/mod_tennis/stylesheet.css');

require JModuleHelper::getLayoutPath('mod_tennis');

?>