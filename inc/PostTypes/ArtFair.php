<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\PostTypes;

use Inc\Base\BaseController;

class ArtFair extends BaseController
{
    private $post_type = 'wlartfair';
    
    public function register()
    {
        $options = get_option('wrklst_options');
        
        // Only register if enabled in settings
        if (empty($options['cptartfair'])) {
            return;
        }
        
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type()
    {
        $labels = [
            'name'                  => _x('Art Fairs', 'Post Type General Name', 'wrklst-plugin'),
            'singular_name'         => _x('Art Fair', 'Post Type Singular Name', 'wrklst-plugin'),
            'menu_name'             => __('Art Fairs', 'wrklst-plugin'),
            'name_admin_bar'        => __('Art Fairs', 'wrklst-plugin'),
            'archives'              => __('Art Fair Archives', 'wrklst-plugin'),
            'attributes'            => __('Art Fair Attributes', 'wrklst-plugin'),
            'parent_item_colon'     => __('Parent Art Fair:', 'wrklst-plugin'),
            'all_items'             => __('All Art Fairs', 'wrklst-plugin'),
            'add_new_item'          => __('Add New Art Fair', 'wrklst-plugin'),
            'add_new'               => __('Add New', 'wrklst-plugin'),
            'new_item'              => __('New Art Fair', 'wrklst-plugin'),
            'edit_item'             => __('Edit Art Fair', 'wrklst-plugin'),
            'update_item'           => __('Update Art Fair', 'wrklst-plugin'),
            'view_item'             => __('View Art Fair', 'wrklst-plugin'),
            'view_items'            => __('View Art Fairs', 'wrklst-plugin'),
            'search_items'          => __('Search Art Fair', 'wrklst-plugin'),
            'not_found'             => __('Not found', 'wrklst-plugin'),
            'not_found_in_trash'    => __('Not found in Trash', 'wrklst-plugin'),
            'featured_image'        => __('Featured Image', 'wrklst-plugin'),
            'set_featured_image'    => __('Set featured image', 'wrklst-plugin'),
            'remove_featured_image' => __('Remove featured image', 'wrklst-plugin'),
            'use_featured_image'    => __('Use as featured image', 'wrklst-plugin'),
            'insert_into_item'      => __('Insert into Art Fair', 'wrklst-plugin'),
            'uploaded_to_this_item' => __('Uploaded to this Art Fair', 'wrklst-plugin'),
            'items_list'            => __('Art Fairs list', 'wrklst-plugin'),
            'items_list_navigation' => __('Art Fairs list navigation', 'wrklst-plugin'),
            'filter_items_list'     => __('Filter Art Fairs list', 'wrklst-plugin'),
        ];
        
        $args = [
            'label'                 => __('Art Fair', 'wrklst-plugin'),
            'description'           => __('An Art Fair', 'wrklst-plugin'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'revisions', 'thumbnail', 'page-attributes'],
            'taxonomies'            => ['topic'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-tickets-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'art-fairs',
            'rewrite'               => ['slug' => 'art-fairs'],
        ];
        
        register_post_type($this->post_type, $args);
    }
}