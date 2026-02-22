<?php

namespace Joomla\Module\Tennis\Site\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\Module\YourModule\Site\Helper\TennisHelper;

class Dispatcher implements DispatcherInterface, HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected $module;
    protected $app;

    public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
    {
        $this->module = $module;
        $this->app = $app;
    }

    public function dispatch()
    {
	Log::addLogger(
	    ['text_file' => 'mod_tennis.php'],
	    Log::ALL,
	    ['mod_tennis']
	);

        $params = new Registry($this->module->params);

        // PASS $params to helper
        $data = TennisHelper::getData($params);

        require ModuleHelper::getLayoutPath('mod_tennis', $params->get('layout', 'default'));
    }
}