<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Base;
use \Inc\Base\BaseController;

class Enqueue extends BaseController
{
    public function register() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_enqueue_media', [$this, 'enqueue_media_scripts']);
    }

    function enqueue($hook) {
        // Load on all admin pages for now to ensure scripts are available
        // TODO: Optimize to load only on specific pages once we identify all page hooks
        
        // Enqueue jQuery first
        wp_enqueue_script('jquery');
        
        // Enqueue debounce BEFORE other scripts that depend on it
        wp_enqueue_script('wrklst-debounce', $this->plugin_url . 'assets/debounce.js', array('jquery'), WRKLST_PLUGIN_VERSION, true);
        
        // Enqueue WrkLst AJAX handler
        wp_enqueue_script('wrklst-ajax', $this->plugin_url . 'assets/wrklst-ajax.js', array('jquery'), WRKLST_PLUGIN_VERSION, true);
        
        // Localize the ajax url for frontend use
        wp_localize_script('wrklst-ajax', 'wrklst_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wrklst_security_nonce')
        ));
        
        // Load base class if on pages that need it
        if (strpos($hook, 'wrklst') !== false) {
            wp_enqueue_script('media-views');
            wp_enqueue_script('backbone');
            wp_enqueue_script('wrklst-base', $this->plugin_url . 'assets/wrklst-base.js', array('jquery', 'wrklst-ajax', 'media-views', 'backbone'), WRKLST_PLUGIN_VERSION, true);
        }
        
        // Enqueue other scripts with proper dependencies
        wp_enqueue_script('send_b64_data', $this->plugin_url . 'assets/send_b64_data.js', array('jquery'), WRKLST_PLUGIN_VERSION, true);
        wp_enqueue_style('wrklstStyle', $this->plugin_url . 'assets/style.css', array(), WRKLST_PLUGIN_VERSION);
        wp_enqueue_script('wrklstScript', $this->plugin_url . 'assets/admin.js', array('jquery', 'wrklst-ajax', 'wrklst-debounce'), WRKLST_PLUGIN_VERSION, true);
        
        // Enqueue works page script and styles if on that page
        if (strpos($hook, 'wrklst_works') !== false && isset($_GET['page']) && $_GET['page'] === 'wrklst_works') {
            wp_enqueue_script('wrklst-works', $this->plugin_url . 'assets/wrklst-works.js', array('jquery', 'wrklst-ajax', 'wrklst-debounce', 'wrklst-base'), WRKLST_PLUGIN_VERSION, true);
            wp_enqueue_style('wrklst-works', $this->plugin_url . 'assets/wrklst-works.css', array(), WRKLST_PLUGIN_VERSION);
        }
    }
    
    function enqueue_media_scripts() {
        // Ensure debounce is loaded
        wp_enqueue_script('wrklst-debounce', $this->plugin_url . 'assets/debounce.js', array('jquery'), WRKLST_PLUGIN_VERSION, true);
        
        // Ensure our scripts are available in the media modal
        wp_enqueue_script('wrklst-ajax', $this->plugin_url . 'assets/wrklst-ajax.js', array('jquery'), WRKLST_PLUGIN_VERSION, true);
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('wrklst-ajax', 'wrklst_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wrklst_security_nonce')
        ));
        
        // Load the base class
        wp_enqueue_script('wrklst-base', $this->plugin_url . 'assets/wrklst-base.js', array('jquery', 'wrklst-ajax', 'media-views', 'backbone'), WRKLST_PLUGIN_VERSION, true);
        
        // Use the proper WordPress Backbone.js version
        wp_enqueue_script('wl-media-upload-extension', $this->plugin_url . 'assets/media_upload_extension_original.js', array('jquery', 'wrklst-ajax', 'media-views', 'wrklst-debounce', 'backbone', 'wrklst-base'), WRKLST_PLUGIN_VERSION, true);
        
        // Add inline script to ensure ajaxurl and plugin URL are available
        wp_add_inline_script('wl-media-upload-extension', 
            'var ajaxurl = ajaxurl || "' . admin_url('admin-ajax.php') . '"; var wrklst_plugin_url = "' . $this->plugin_url . '";', 
            'before'
        );
    }
}
