<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Api\Callbacks;
use \Inc\Base\BaseController;

class AdminCallbacks extends BaseController
{
    public function adminWorks()
    {
        return require_once $this->plugin_path.'templates/works.php';
    }

    public function adminSettings()
    {
        return require_once $this->plugin_path.'templates/settings.php';
    }

    public function wrklstOptionsGroup($input)
    {
        $options = get_option('wrklst_options');
        if ($input['api']) $options['api'] = $input['api']; else $options['api'] = '';
        if ($input['account']) $options['account'] = $input['account']; else $options['account'] = '';
        return $options;
    }

    public function wrklstAdminWlApiSection()
    {
        echo 'Enter your WrkLst API crendentials to connect this Wordpress Website with your WrkLst account.';
    }

    public function wrklstAccountId()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['account']))
        {
            $options = [];
            $options['account'] = '';
        }

        echo '<input type="text" class="regular-text" name="wrklst_options[account]" value="'.esc_attr($options['account']).'" placeholder="Enter your account Id" autocomplete="off" /><span>.wrklst.com</span>';
    }

    public function wrklstApiKey()
    {
        $options = get_option('wrklst_options');
        if(!isset($options['api']))
        {
            $options = [];
            $options['api'] = '';
        }

        echo '<input type="password" class="regular-text" name="wrklst_options[api]" value="'.esc_attr($options['api']).'" placeholder="Enter your api key" autocomplete="off" />';
    }
}
