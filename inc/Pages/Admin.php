<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Pages;
use \Inc\Base\BaseController;
use \Inc\Api\Callbacks\AdminCallbacks;

class Admin extends BaseController
{
    public $pages = [];
    public $subpages = [];
    public $callbacks;


    public function register() {
        $this->callbacks = new AdminCallbacks();
        $this->pages = [
            [
                'parent_slug' => 'WrkLst',
                'page_title' => 'WrkLst',
                'menu_title' => '<i>W</i>rkLst',
                'capability' => 'manage_options',
                'menu_slug' => 'wrklst_works',
                'callback' => [$this->callbacks,'adminWorks'],
                'incon_url' => $this->plugin_url.'assets/img/wrklst-logo.png',
                'position' => 11
            ],
        ];
        $this->subpages = [
            [
                'parent_slug' => 'wrklst_works',
                'page_title' => 'Settings',
                'menu_title' => 'Settings',
                'capability' => 'manage_options',
                'menu_slug' => 'wrklst_settings',
                'callback' => [$this->callbacks,'adminSettings'],
            ],
        ];

        $this->setCfSettings();
        $this->setCfSections();
        $this->setCfFields();

        $this->settings->addPages($this->pages)->withSubPage('Works')->addSubPages($this->subpages)->register();
    }

    public function setCfSettings()
    {
        $args = [
            [
                'option_group'  => 'wrklst_options',
                'option_name'   => 'wrklst_options',
                'callback'      => [$this->callbacks,'wrklstOptionsGroup'],
            ]
        ];

        $this->settings->setCfSettings($args);
    }

    public function setCfSections()
    {
        $args = [
            [
                'id'        => 'wrklst_options_section',
                'title'     => 'API Connection to WrkLst',
                'callback'  => [$this->callbacks,'wrklstAdminWlApiSection'],
                'page'      => 'wrklst_settings',
            ]
        ];

        $this->settings->setCfSections($args);
    }

    public function setCfFields()
    {
        $args = [
            [
                'id'        => 'account-id',
                'title'     => 'Account ID',
                'callback'  => [$this->callbacks,'wrklstAccountId'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section',
                'args'      => [
                    'label_for' => 'account-id',
                    'class' => 'account-id-class',
                ],
            ],
            [
                'id'        => 'api-id',
                'title'     => 'API Key',
                'callback'  => [$this->callbacks,'wrklstApiKey'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section',
                'args'      => [
                    'label_for' => 'api-id',
                    'class' => 'api-id-class',
                ],
            ]
        ];

        $this->settings->setCfFields($args);
    }

}
