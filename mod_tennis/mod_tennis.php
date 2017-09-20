<?php

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';
# require_once JPATH_LIBRARIES . '/phpexcel/library/PHPExcel.php';
require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

$document = JFactory::getDocument();

$format = $params->get('format', 'debug');

# Set current year and week
$date = new DateTime(date_default_timezone_get());
$session = & JFactory::getSession();
$session->set('date', 0);

$user = JFactory::getUser();
$session->set('userId', $user->id);

JHtml::script(Juri::base() . 'modules/mod_tennis/jquery.clearsearch.js');
JHtml::script('https://www.gstatic.com/charts/loader.js');
JHtml::script(Juri::base() . 'modules/mod_tennis/chart.js');
JHtml::script(Juri::base() . 'modules/mod_tennis/mod_tennis.js');
JHtml::stylesheet('modules/mod_tennis/stylesheet.css');

require JModuleHelper::getLayoutPath('mod_tennis');

?>