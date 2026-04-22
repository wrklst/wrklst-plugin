<?php
/**
* @package WrkLstPlugin
*/
namespace Inc\Api;

class SettingsApi
{
    public $pages = [];
    public $subpages = [];
    public $cf_settings = [];
    public $cf_sections = [];
    public $cf_fields = [];


    public function register()
    {
        if(!empty($this->pages)) {
            add_action('admin_menu',[$this,'addAdminMenu']);
        }

        if(!empty($this->cf_settings)) {
            add_action('admin_init',[$this,'registerCustomFields']);
        }
    }

    public function addPages(array $pages)
    {
        $this->pages = $pages;
        return $this;
    }

    public function withSubPage(string $title = null)
    {
        if(empty($this->pages))
        {
            return $this;
        }

        $this->subpages = [
            [
                'parent_slug' => $this->pages[0]['menu_slug'],
                'page_title' => $this->pages[0]['page_title'],
                'menu_title' => $title?$title:$this->pages[0]['menu_title'],
                'capability' => $this->pages[0]['capability'],
                'menu_slug' => $this->pages[0]['menu_slug'],
                'callback' => $this->pages[0]['callback'],
            ],
        ];
        return $this;
    }

    public function addSubPages(array $pages)
    {
        $this->subpages = array_merge($this->subpages, $pages);
        return $this;
    }

    public function addAdminMenu()
    {
        foreach($this->pages as $page) {
            add_menu_page($page['page_title'],$page['menu_title'],$page['capability'],$page['menu_slug'],$page['callback'],$page['incon_url'],$page['position']);
        }

        foreach($this->subpages as $page) {
            add_submenu_page($page['parent_slug'],$page['page_title'],$page['menu_title'],$page['capability'],$page['menu_slug'],$page['callback']);
        }
    }

    public function setCfSettings(array $settings)
    {
        $this->cf_settings = $settings;
        return $this;
    }

    public function setCfSections(array $sections)
    {
        $this->cf_sections = $sections;
        return $this;
    }

    public function setCfFields(array $fields)
    {
        $this->cf_fields = $fields;
        return $this;
    }

    public function registerCustomFields()
    {
        // register setting
        foreach($this->cf_settings as $setting)
            register_setting($setting['option_group'], $setting['option_name'], isset($setting['callback'])?$setting['callback']:'');

        // add settigns section
        foreach($this->cf_sections as $section)
            add_settings_section($section['id'], $section['title'], isset($section['callback'])?$section['callback']:'', $section['page']);

        //add settings field
        foreach($this->cf_fields as $field)
            add_settings_field($field['id'], $field['title'], isset($field['callback'])?$field['callback']:'', $field['page'], $field['section'], isset($field['args'])?$field['args']:'');
    }
}
