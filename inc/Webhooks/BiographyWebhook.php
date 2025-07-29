<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\Webhooks;

use Inc\Base\BaseController;

class BiographyWebhook extends BaseController
{
    public function register()
    {
        if (!is_admin()) {
            add_action('init', [$this, 'listen_for_webhook']);
        }
    }

    public function listen_for_webhook()
    {
        if (!isset($_GET['webhook-listener']) || $_GET['webhook-listener'] !== 'wl-biography') {
            return;
        }

        $options = get_option('wrklst_options');
        
        if (empty($options['wlbiowebhook'])) {
            return;
        }

        $this->process_webhook();
    }

    private function process_webhook()
    {
        $body = file_get_contents('php://input');
        
        if (empty($body)) {
            http_response_code(400);
            die('No data received');
        }

        $webhook_input = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            die('Invalid JSON data');
        }

        if (!$this->validate_webhook_token($webhook_input)) {
            http_response_code(401);
            die('Unauthorized');
        }

        $this->process_biography_update($webhook_input);
        
        // Clear caches
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        if (function_exists('sg_cachepress_purge_everything')) {
            sg_cachepress_purge_everything();
        }

        http_response_code(200);
        die('1');
    }

    private function validate_webhook_token($webhook_input)
    {
        if (empty($webhook_input['token'])) {
            return false;
        }

        $wrklst_options = get_option('wrklst_options');
        
        if (empty($wrklst_options['whapikey']) || strlen($wrklst_options['whapikey']) <= 30) {
            return false;
        }

        return hash_equals($wrklst_options['whapikey'], $webhook_input['token']);
    }

    private function process_biography_update($webhook_input)
    {
        if (!isset($webhook_input['artist']) || !isset($webhook_input['categories'])) {
            return;
        }

        $wrklst_options = get_option('wrklst_options');
        
        require_once $this->plugin_path . 'vendor/autoload.php';
        
        $mustache = new \Mustache_Engine([
            'escape' => function($value) {
                if (strpos($value, '*[[DONOTESCAPE]]*') !== false) {
                    return str_replace('*[[DONOTESCAPE]]*', '', $value);
                }
                return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
            }
        ]);

        // Process biography
        if (!empty($wrklst_options['musformatbio'])) {
            $this->update_biography($webhook_input, $mustache, $wrklst_options['musformatbio']);
        }

        // Process news if present
        if (isset($webhook_input['news']) && !empty($wrklst_options['musformatnews'])) {
            $this->update_news($webhook_input, $mustache, $wrklst_options['musformatnews']);
        }
    }

    private function update_biography($webhook_input, $mustache, $template)
    {
        $artist_id = sanitize_text_field($webhook_input['artist']['id']);
        $post_id = $this->find_biography_post($artist_id);
        
        $content = $mustache->render($template, $webhook_input);
        
        $post_data = [
            'ID' => $post_id,
            'post_content' => wp_kses_post($content),
            'post_title' => sanitize_text_field($webhook_input['artist']['display']),
            'post_name' => sanitize_title("WrkLst " . $webhook_input['artist']['display'] . " " . $artist_id),
            'post_status' => 'publish',
            'post_type' => 'wlbiography',
        ];

        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, 'wl_biography_data', ['artist_id' => $artist_id]);
        }
    }

    private function update_news($webhook_input, $mustache, $template)
    {
        $post_id = $this->find_biography_post('news');
        
        $content = $mustache->render($template, $webhook_input);
        
        $post_data = [
            'ID' => $post_id,
            'post_content' => wp_kses_post($content),
            'post_title' => 'News',
            'post_name' => 'wrklst-news',
            'post_status' => 'publish',
            'post_type' => 'wlbiography',
        ];

        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, 'wl_biography_data', ['artist_id' => 'news']);
        }
    }

    private function find_biography_post($artist_id)
    {
        $query = new \WP_Query([
            'post_type' => 'wlbiography',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'wl_biography_data',
                    'value' => serialize(['artist_id' => (string) $artist_id]),
                    'compare' => '=',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        return $query->have_posts() ? $query->posts[0] : 0;
    }
}