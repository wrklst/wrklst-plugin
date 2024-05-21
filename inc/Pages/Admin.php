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
            ],
            [
                'id'        => 'wrklst_options_section2',
                'title'     => 'Custom Post Types',
                'callback'  => [$this->callbacks,'wrklstAdminWlApiSection2'],
                'page'      => 'wrklst_settings',
            ],
            [
                'id'        => 'wrklst_options_section3',
                'title'     => 'Biography Webhook Connection',
                'callback'  => [$this->callbacks,'wrklstAdminWlApiSection3'],
                'page'      => 'wrklst_settings',
            ],
            [
                'id'        => 'wrklst_options_section4',
                'title'     => 'Work Caption Settings',
                'callback'  => [$this->callbacks,'wrklstAdminWlApiSection4'],
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
                'title'     => 'WrkLst API Key',
                'callback'  => [$this->callbacks,'wrklstApiKey'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section',
                'args'      => [
                    'label_for' => 'api-id',
                    'class' => 'api-id-class',
                ],
            ],
            [
                'id'        => 'cpt-artist',
                'title'     => 'CPT Artist',
                'callback'  => [$this->callbacks,'wrklstCustomPostTypeArtist'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section2',
                'args'      => [
                    'label_for' => 'cptartist',
                    'class' => 'cpt-artist-class',
                ],
            ],
            [
                'id'        => 'cpt-exhibition',
                'title'     => 'CPT Exhibition',
                'callback'  => [$this->callbacks,'wrklstCustomPostTypeExhibition'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section2',
                'args'      => [
                    'label_for' => 'cptexhibition',
                    'class' => 'cpt-exhibition-class',
                ],
            ],
            [
                'id'        => 'cpt-artfair',
                'title'     => 'CPT Art Fair',
                'callback'  => [$this->callbacks,'wrklstCustomPostTypeArtFair'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section2',
                'args'      => [
                    'label_for' => 'cptartfair',
                    'class' => 'cpt-artfair-class',
                ],
            ],
            [
                'id'        => 'wlbiowebhook',
                'title'     => 'Activate Bio Webhook Connection',
                'callback'  => [$this->callbacks,'wrklstActivateWlBioWebhook'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section3',
                'args'      => [
                    'label_for' => 'wlbiowebhook',
                    'class' => 'wlbiowebhook-class',
                ],
            ],
            [
                'id'        => 'musformatbio',
                'title'     => 'Biography Format',
                'callback'  => [$this->callbacks,'wrklstBioFormat'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section3',
                'args'      => [
                    'label_for' => 'musformatbio',
                    'class' => 'musformatbio-class',
                ],
            ],
            [
                'id'        => 'musformatnews',
                'title'     => 'News Format',
                'callback'  => [$this->callbacks,'wrklstNewsFormat'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section3',
                'args'      => [
                    'label_for' => 'musformatnews',
                    'class' => 'musformatnews-class',
                ],
            ],
            [
                'id'        => 'whapikey',
                'title'     => 'Webhook Auth Token',
                'callback'  => [$this->callbacks,'wrklstWebhookApi'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section3',
                'args'      => [
                    'label_for' => 'whapikey',
                    'class' => 'whapikey-class',
                ],
            ],
            [
                'id'        => 'workdcaptioninvnr',
                'title'     => 'Include Inventory # in Caption',
                'callback'  => [$this->callbacks,'wrklstWorkCaptionInvNr'],
                'page'      => 'wrklst_settings',
                'section'   => 'wrklst_options_section4',
                'args'      => [
                    'label_for' => 'workdcaptioninvnr',
                    'class' => 'workdcaptioninvnr-class',
                ],
            ]
        ];

        $this->settings->setCfFields($args);
    }

}
