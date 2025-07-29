<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\PostTypes;

use Inc\Base\BaseController;

class Biography extends BaseController
{
    public function register()
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes_wlbiography', [$this, 'add_meta_boxes']);
        add_action('save_post_wlbiography', [$this, 'save_meta_box_data'], 10, 3);
    }

    public function register_post_type()
    {
        $labels = [
            'name' => __('Biographies', 'wrklst-plugin'),
            'singular_name' => __('Biography', 'wrklst-plugin'),
            'menu_name' => __('WrkLst Biographies', 'wrklst-plugin'),
            'add_new' => __('Add New', 'wrklst-plugin'),
            'add_new_item' => __('Add New Biography', 'wrklst-plugin'),
            'edit_item' => __('Edit Biography', 'wrklst-plugin'),
            'new_item' => __('New Biography', 'wrklst-plugin'),
            'view_item' => __('View Biography', 'wrklst-plugin'),
            'search_items' => __('Search Biographies', 'wrklst-plugin'),
            'not_found' => __('No biographies found', 'wrklst-plugin'),
            'not_found_in_trash' => __('No biographies found in trash', 'wrklst-plugin'),
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'biography'],
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'],
            'show_in_rest' => true,
        ];

        register_post_type('wlbiography', $args);
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'wl_bio_options_mb',
            __('WrkLst Biography Options', 'wrklst-plugin'),
            [$this, 'render_meta_box'],
            'wlbiography',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post)
    {
        wp_nonce_field('wl_bio_meta_box', 'wl_bio_meta_box_nonce');
        
        $wl_biography_data = get_post_meta($post->ID, 'wl_biography_data', true);
        $artist_id = isset($wl_biography_data['artist_id']) ? $wl_biography_data['artist_id'] : '';
        ?>
        <p>
            <label for="wl_input_artist_id"><?php _e('Artist ID:', 'wrklst-plugin'); ?></label><br />
            <input type="text" id="wl_input_artist_id" name="wl_input_artist_id" value="<?php echo esc_attr($artist_id); ?>" class="widefat" />
        </p>
        <?php
    }

    public function save_meta_box_data($post_id, $post, $update)
    {
        if (!$update) {
            return;
        }

        if (!isset($_POST['wl_bio_meta_box_nonce']) || !wp_verify_nonce($_POST['wl_bio_meta_box_nonce'], 'wl_bio_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!isset($_POST['wl_input_artist_id'])) {
            return;
        }

        $wl_biography_data = [
            'artist_id' => sanitize_text_field($_POST['wl_input_artist_id']),
        ];

        update_post_meta($post_id, 'wl_biography_data', $wl_biography_data);
    }
}