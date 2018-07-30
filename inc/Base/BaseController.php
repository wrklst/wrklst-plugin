<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Base;
use \Inc\Api\SettingsApi;

class BaseController
{
    public $settings;

    function __construct() {
        $this->settings = new SettingsApi();
        $this->plugin_path = plugin_dir_path(dirname(__FILE__, 2));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__, 2));
        $this->plugin_name = plugin_basename(dirname(__FILE__, 3)).'/wrklst-plugin.php';
    }
}
