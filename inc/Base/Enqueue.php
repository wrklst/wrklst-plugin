<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Base;
use \Inc\Base\BaseController;

class Enqueue extends BaseController
{
    public function register() {
        add_action('admin_enqueue_scripts',[$this,'enqueue']);
    }

    function enqueue() {
        wp_enqueue_script('send_b64_data',$this->plugin_url.'assets/send_b64_data.js');
        wp_enqueue_style('wrklstStyle',$this->plugin_url.'assets/style.css');
        wp_enqueue_script('wrklstScript',$this->plugin_url.'assets/admin.js');
        wp_enqueue_script('wrklstScript2',$this->plugin_url.'assets/debounce.js');
        wp_enqueue_script('wl-media-upload-extension-select',$this->plugin_url.'assets/media_upload_extension.select.js',array( 'jquery' ), '', true);
    }
}
