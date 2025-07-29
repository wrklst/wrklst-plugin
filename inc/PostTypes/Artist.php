<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\PostTypes;

use Inc\Base\BaseController;

class Artist extends BaseController
{
    private $post_type = 'wlartist';
    
    public function register()
    {
        $options = get_option('wrklst_options');
        
        // Only register if enabled in settings
        if (empty($options['cptartist'])) {
            return;
        }
        
        add_action('init', [$this, 'register_post_type']);
    }

    public function register_post_type()
    {
        $labels = [
            'name'                  => _x('Artists', 'Post Type General Name', 'wrklst-plugin'),
            'singular_name'         => _x('Artist', 'Post Type Singular Name', 'wrklst-plugin'),
            'menu_name'             => __('Artists', 'wrklst-plugin'),
            'name_admin_bar'        => __('Artists', 'wrklst-plugin'),
            'archives'              => __('Artist Archives', 'wrklst-plugin'),
            'attributes'            => __('Artist Attributes', 'wrklst-plugin'),
            'parent_item_colon'     => __('Parent Artist:', 'wrklst-plugin'),
            'all_items'             => __('All Artists', 'wrklst-plugin'),
            'add_new_item'          => __('Add New Artist', 'wrklst-plugin'),
            'add_new'               => __('Add New', 'wrklst-plugin'),
            'new_item'              => __('New Artist', 'wrklst-plugin'),
            'edit_item'             => __('Edit Artist', 'wrklst-plugin'),
            'update_item'           => __('Update Artist', 'wrklst-plugin'),
            'view_item'             => __('View Artist', 'wrklst-plugin'),
            'view_items'            => __('View Artists', 'wrklst-plugin'),
            'search_items'          => __('Search Artist', 'wrklst-plugin'),
            'not_found'             => __('Not found', 'wrklst-plugin'),
            'not_found_in_trash'    => __('Not found in Trash', 'wrklst-plugin'),
            'featured_image'        => __('Featured Image', 'wrklst-plugin'),
            'set_featured_image'    => __('Set featured image', 'wrklst-plugin'),
            'remove_featured_image' => __('Remove featured image', 'wrklst-plugin'),
            'use_featured_image'    => __('Use as featured image', 'wrklst-plugin'),
            'insert_into_item'      => __('Insert into Artist', 'wrklst-plugin'),
            'uploaded_to_this_item' => __('Uploaded to this Artist', 'wrklst-plugin'),
            'items_list'            => __('Artists list', 'wrklst-plugin'),
            'items_list_navigation' => __('Artists list navigation', 'wrklst-plugin'),
            'filter_items_list'     => __('Filter Artists list', 'wrklst-plugin'),
        ];
        
        $args = [
            'label'                 => __('Artist', 'wrklst-plugin'),
            'description'           => __('An Artist', 'wrklst-plugin'),
            'labels'                => $labels,
            'supports'              => ['title', 'revisions', 'thumbnail'],
            'taxonomies'            => ['topic'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-admin-users',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'artists',
            'rewrite'               => ['slug' => 'artists'],
        ];
        
        register_post_type($this->post_type, $args);
    }
}