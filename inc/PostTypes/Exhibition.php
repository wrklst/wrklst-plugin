<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\PostTypes;

use Inc\Base\BaseController;

class Exhibition extends BaseController
{
    private $post_type = 'wlexhibition';
    
    public function register()
    {
        $options = get_option('wrklst_options');
        
        // Only register if enabled in settings
        if (empty($options['cptexhibition'])) {
            return;
        }
        
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type()
    {
        $labels = [
            'name'                  => _x('Exhibitions', 'Post Type General Name', 'wrklst-plugin'),
            'singular_name'         => _x('Exhibition', 'Post Type Singular Name', 'wrklst-plugin'),
            'menu_name'             => __('Exhibitions', 'wrklst-plugin'),
            'name_admin_bar'        => __('Exhibitions', 'wrklst-plugin'),
            'archives'              => __('Exhibition Archives', 'wrklst-plugin'),
            'attributes'            => __('Exhibition Attributes', 'wrklst-plugin'),
            'parent_item_colon'     => __('Parent Exhibition:', 'wrklst-plugin'),
            'all_items'             => __('All Exhibitions', 'wrklst-plugin'),
            'add_new_item'          => __('Add New Exhibition', 'wrklst-plugin'),
            'add_new'               => __('Add New', 'wrklst-plugin'),
            'new_item'              => __('New Exhibition', 'wrklst-plugin'),
            'edit_item'             => __('Edit Exhibition', 'wrklst-plugin'),
            'update_item'           => __('Update Exhibition', 'wrklst-plugin'),
            'view_item'             => __('View Exhibition', 'wrklst-plugin'),
            'view_items'            => __('View Exhibitions', 'wrklst-plugin'),
            'search_items'          => __('Search Exhibition', 'wrklst-plugin'),
            'not_found'             => __('Not found', 'wrklst-plugin'),
            'not_found_in_trash'    => __('Not found in Trash', 'wrklst-plugin'),
            'featured_image'        => __('Featured Image', 'wrklst-plugin'),
            'set_featured_image'    => __('Set featured image', 'wrklst-plugin'),
            'remove_featured_image' => __('Remove featured image', 'wrklst-plugin'),
            'use_featured_image'    => __('Use as featured image', 'wrklst-plugin'),
            'insert_into_item'      => __('Insert into Exhibition', 'wrklst-plugin'),
            'uploaded_to_this_item' => __('Uploaded to this Exhibition', 'wrklst-plugin'),
            'items_list'            => __('Exhibitions list', 'wrklst-plugin'),
            'items_list_navigation' => __('Exhibitions list navigation', 'wrklst-plugin'),
            'filter_items_list'     => __('Filter Exhibitions list', 'wrklst-plugin'),
        ];
        
        $args = [
            'label'                 => __('Exhibition', 'wrklst-plugin'),
            'description'           => __('An Exhibition', 'wrklst-plugin'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'revisions', 'thumbnail', 'page-attributes'],
            'taxonomies'            => ['topic'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-art',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'exhibitions',
            'rewrite'               => ['slug' => 'exhibitions'],
        ];
        
        register_post_type($this->post_type, $args);
    }
}