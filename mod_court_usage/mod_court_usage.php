<?php

defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';
require_once dirname(__FILE__) . '/../mod_tennis/const.php';

require_once JPATH_LIBRARIES . '/phpexcel/library/PHPExcel.php';

JHtml::script('https://www.gstatic.com/charts/loader.js');
JHtml::script(Juri::base() . 'modules/mod_court_usage/mod_court_usage.js');

JHtml::stylesheet('modules/mod_court_usage/stylesheet.css');

require JModuleHelper::getLayoutPath('mod_court_usage');

?>