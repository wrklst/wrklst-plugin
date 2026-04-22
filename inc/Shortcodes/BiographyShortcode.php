<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\Shortcodes;

use Inc\Base\BaseController;

class BiographyShortcode extends BaseController
{
    public function register()
    {
        add_shortcode('wrklst_bio_content', [$this, 'render_biography_shortcode']);
    }

    public function render_biography_shortcode($atts)
    {
        $atts = shortcode_atts([
            'id' => 0,
            'newspage' => false,
        ], $atts, 'wrklst_bio_content');

        $id = false;
        
        if (!empty($atts['id']) && is_numeric($atts['id'])) {
            $id = absint($atts['id']);
        } elseif ($atts['newspage']) {
            $id = 'news';
        }

        if (!$id) {
            return '';
        }

        $post_query = new \WP_Query([
            'post_type' => 'wlbiography',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'wl_biography_data',
                    'value' => serialize(['artist_id' => (string) $id]),
                    'compare' => '=',
                ],
            ],
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        if ($post_query->have_posts()) {
            $post_query->the_post();
            $content = get_the_content();
            wp_reset_postdata();
            return $content;
        }

        return '';
    }
}