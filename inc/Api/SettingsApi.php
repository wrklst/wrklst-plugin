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
            add_action('init',[$this,'registerCustomPostTypes']);
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

    public function registerCustomPostTypes()
    {
        $labels = array(
    		'name'                  => _x( 'Artists', 'Post Type General Name', 'wrklst_plugin' ),
    		'singular_name'         => _x( 'Artist', 'Post Type Singular Name', 'wrklst_plugin' ),
    		'menu_name'             => __( 'Artists', 'wrklst_plugin' ),
    		'name_admin_bar'        => __( 'Artists', 'wrklst_plugin' ),
    		'archives'              => __( 'Artist Archives', 'wrklst_plugin' ),
    		'attributes'            => __( 'Artist Attributes', 'wrklst_plugin' ),
    		'parent_item_colon'     => __( 'Parent Artist:', 'wrklst_plugin' ),
    		'all_items'             => __( 'All Artists', 'wrklst_plugin' ),
    		'add_new_item'          => __( 'Add New Artist', 'wrklst_plugin' ),
    		'add_new'               => __( 'Add New', 'wrklst_plugin' ),
    		'new_item'              => __( 'New Artist', 'wrklst_plugin' ),
    		'edit_item'             => __( 'Edit Artist', 'wrklst_plugin' ),
    		'update_item'           => __( 'Update Artist', 'wrklst_plugin' ),
    		'view_item'             => __( 'View Artist', 'wrklst_plugin' ),
    		'view_items'            => __( 'View Artists', 'wrklst_plugin' ),
    		'search_items'          => __( 'Search Artist', 'wrklst_plugin' ),
    		'not_found'             => __( 'Not found', 'wrklst_plugin' ),
    		'not_found_in_trash'    => __( 'Not found in Trash', 'wrklst_plugin' ),
    		'featured_image'        => __( 'Featured Image', 'wrklst_plugin' ),
    		'set_featured_image'    => __( 'Set featured image', 'wrklst_plugin' ),
    		'remove_featured_image' => __( 'Remove featured image', 'wrklst_plugin' ),
    		'use_featured_image'    => __( 'Use as featured image', 'wrklst_plugin' ),
    		'insert_into_item'      => __( 'Insert into Artist', 'wrklst_plugin' ),
    		'uploaded_to_this_item' => __( 'Uploaded to this Artist', 'wrklst_plugin' ),
    		'items_list'            => __( 'Artists list', 'wrklst_plugin' ),
    		'items_list_navigation' => __( 'Artists list navigation', 'wrklst_plugin' ),
    		'filter_items_list'     => __( 'Filter Artists list', 'wrklst_plugin' ),
    	);
    	$args = array(
    		'label'                 => __( 'Artist', 'wrklst_plugin' ),
    		'description'           => __( 'An Artist', 'wrklst_plugin' ),
    		'labels'                => $labels,
    		'supports'              => array( 'title', 'revisions', 'thumbnail' ), //'editor', 'custom-fields', 'page-attributes', 'thumbnail',
    		'taxonomies'            => array( 'topic' ),
    		'hierarchical'          => false,
    		'public'                => true,
    		'show_ui'               => true,
    		'show_in_menu'          => true,
    		'menu_position'         => 20,
    		'menu_icon'             => plugin_dir_url(dirname(__FILE__, 2)).'assets/img/baseline-palette-24px.svg',
    		'show_in_admin_bar'     => true,
    		'show_in_nav_menus'     => true,
    		'can_export'            => true,
    		'has_archive'           => true,
    		'exclude_from_search'   => false,
    		'publicly_queryable'    => true,
    		'capability_type'       => 'page',
    		'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'artists', 'with_front' => false ),
    	);
    	register_post_type( 'wl_artists', $args );

        $labels = array(
    		'name'                  => _x( 'Exhibitions', 'Post Type General Name', 'wrklst_plugin' ),
    		'singular_name'         => _x( 'Exhibition', 'Post Type Singular Name', 'wrklst_plugin' ),
    		'menu_name'             => __( 'Exhibitions', 'wrklst_plugin' ),
    		'name_admin_bar'        => __( 'Exhibitions', 'wrklst_plugin' ),
    		'archives'              => __( 'Exhibition Archives', 'wrklst_plugin' ),
    		'attributes'            => __( 'Exhibition Attributes', 'wrklst_plugin' ),
    		'parent_item_colon'     => __( 'Parent Exhibition:', 'wrklst_plugin' ),
    		'all_items'             => __( 'All Exhibitions', 'wrklst_plugin' ),
    		'add_new_item'          => __( 'Add New Exhibition', 'wrklst_plugin' ),
    		'add_new'               => __( 'Add New', 'wrklst_plugin' ),
    		'new_item'              => __( 'New Exhibition', 'wrklst_plugin' ),
    		'edit_item'             => __( 'Edit Exhibition', 'wrklst_plugin' ),
    		'update_item'           => __( 'Update Exhibition', 'wrklst_plugin' ),
    		'view_item'             => __( 'View Exhibition', 'wrklst_plugin' ),
    		'view_items'            => __( 'View Exhibitions', 'wrklst_plugin' ),
    		'search_items'          => __( 'Search Exhibition', 'wrklst_plugin' ),
    		'not_found'             => __( 'Not found', 'wrklst_plugin' ),
    		'not_found_in_trash'    => __( 'Not found in Trash', 'wrklst_plugin' ),
    		'featured_image'        => __( 'Featured Image', 'wrklst_plugin' ),
    		'set_featured_image'    => __( 'Set featured image', 'wrklst_plugin' ),
    		'remove_featured_image' => __( 'Remove featured image', 'wrklst_plugin' ),
    		'use_featured_image'    => __( 'Use as featured image', 'wrklst_plugin' ),
    		'insert_into_item'      => __( 'Insert into Exhibition', 'wrklst_plugin' ),
    		'uploaded_to_this_item' => __( 'Uploaded to this Exhibition', 'wrklst_plugin' ),
    		'items_list'            => __( 'Exhibitions list', 'wrklst_plugin' ),
    		'items_list_navigation' => __( 'Exhibitions list navigation', 'wrklst_plugin' ),
    		'filter_items_list'     => __( 'Filter Exhibitions list', 'wrklst_plugin' ),
    	);
    	$args = array(
    		'label'                 => __( 'Exhibition', 'wrklst_plugin' ),
    		'description'           => __( 'An Exhibition', 'wrklst_plugin' ),
    		'labels'                => $labels,
    		'supports'              => array( 'title', 'revisions', 'thumbnail' ), //'editor', 'custom-fields', 'page-attributes', 'thumbnail',
    		'taxonomies'            => array( 'topic' ),
    		'hierarchical'          => false,
    		'public'                => true,
    		'show_ui'               => true,
    		'show_in_menu'          => true,
    		'menu_position'         => 20,
    		'menu_icon'             => plugin_dir_url(dirname(__FILE__, 2)).'assets/img/baseline-streetview-24px.svg',
    		'show_in_admin_bar'     => true,
    		'show_in_nav_menus'     => true,
    		'can_export'            => true,
    		'has_archive'           => true,
    		'exclude_from_search'   => false,
    		'publicly_queryable'    => true,
    		'capability_type'       => 'page',
    		'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'exhibitions', 'with_front' => false ),
    	);
    	register_post_type( 'wl_exhibitions', $args );

        $labels = array(
    		'name'                  => _x( 'Art Fairs', 'Post Type General Name', 'wrklst_plugin' ),
    		'singular_name'         => _x( 'Art Fair', 'Post Type Singular Name', 'wrklst_plugin' ),
    		'menu_name'             => __( 'Art Fairs', 'wrklst_plugin' ),
    		'name_admin_bar'        => __( 'Art Fairs', 'wrklst_plugin' ),
    		'archives'              => __( 'Art Fair Archives', 'wrklst_plugin' ),
    		'attributes'            => __( 'Art Fair Attributes', 'wrklst_plugin' ),
    		'parent_item_colon'     => __( 'Parent Art Fair:', 'wrklst_plugin' ),
    		'all_items'             => __( 'All Art Fairs', 'wrklst_plugin' ),
    		'add_new_item'          => __( 'Add New Art Fair', 'wrklst_plugin' ),
    		'add_new'               => __( 'Add New', 'wrklst_plugin' ),
    		'new_item'              => __( 'New Art Fair', 'wrklst_plugin' ),
    		'edit_item'             => __( 'Edit Art Fair', 'wrklst_plugin' ),
    		'update_item'           => __( 'Update Art Fair', 'wrklst_plugin' ),
    		'view_item'             => __( 'View Art Fair', 'wrklst_plugin' ),
    		'view_items'            => __( 'View Art Fairs', 'wrklst_plugin' ),
    		'search_items'          => __( 'Search Art Fair', 'wrklst_plugin' ),
    		'not_found'             => __( 'Not found', 'wrklst_plugin' ),
    		'not_found_in_trash'    => __( 'Not found in Trash', 'wrklst_plugin' ),
    		'featured_image'        => __( 'Featured Image', 'wrklst_plugin' ),
    		'set_featured_image'    => __( 'Set featured image', 'wrklst_plugin' ),
    		'remove_featured_image' => __( 'Remove featured image', 'wrklst_plugin' ),
    		'use_featured_image'    => __( 'Use as featured image', 'wrklst_plugin' ),
    		'insert_into_item'      => __( 'Insert into Art Fair', 'wrklst_plugin' ),
    		'uploaded_to_this_item' => __( 'Uploaded to this Art Fair', 'wrklst_plugin' ),
    		'items_list'            => __( 'Art Fairs list', 'wrklst_plugin' ),
    		'items_list_navigation' => __( 'Art Fairs list navigation', 'wrklst_plugin' ),
    		'filter_items_list'     => __( 'Filter Art Fairs list', 'wrklst_plugin' ),
    	);
    	$args = array(
    		'label'                 => __( 'Art Fair', 'wrklst_plugin' ),
    		'description'           => __( 'An Art Fair', 'wrklst_plugin' ),
    		'labels'                => $labels,
    		'supports'              => array( 'title', 'revisions', 'thumbnail' ), //'editor', 'custom-fields', 'page-attributes', 'thumbnail',
    		'taxonomies'            => array( 'topic' ),
    		'hierarchical'          => false,
    		'public'                => true,
    		'show_ui'               => true,
    		'show_in_menu'          => true,
    		'menu_position'         => 20,
    		'menu_icon'             => plugin_dir_url(dirname(__FILE__, 2)).'assets/img/baseline-streetview-24px.svg',
    		'show_in_admin_bar'     => true,
    		'show_in_nav_menus'     => true,
    		'can_export'            => true,
    		'has_archive'           => true,
    		'exclude_from_search'   => false,
    		'publicly_queryable'    => true,
    		'capability_type'       => 'page',
    		'show_in_rest'          => true,
            'rewrite'               => array( 'slug' => 'artfairs', 'with_front' => false ),
    	);
    	register_post_type( 'wl_artfairs', $args );
    }
}
