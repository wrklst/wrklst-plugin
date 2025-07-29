<?php
/**
 * @package WrkLstPlugin
 */
namespace Inc\Ajax;

use Inc\Base\BaseController;

class AjaxHandler extends BaseController
{
    private $ajax_actions = [
        'wrklst_api_cred' => 'handle_api_credentials',
        'wrklst_get_inventories' => 'handle_get_inventories',
        'wrklst_get_inv_items' => 'handle_get_inventory_items',
        'wrklst_upload' => 'handle_image_upload',
    ];

    public function register()
    {
        foreach ($this->ajax_actions as $action => $method) {
            add_action('wp_ajax_' . $action, [$this, $method]);
            add_action('wp_ajax_nopriv_' . $action, [$this, 'handle_unauthorized']);
        }
    }

    public function handle_unauthorized()
    {
        wp_send_json_error(['message' => __('Unauthorized access', 'wrklst-plugin')], 401);
    }

    private function verify_nonce($nonce_action = 'wrklst_security_nonce')
    {
        if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'], $nonce_action)) {
            wp_send_json_error(['message' => __('Security check failed', 'wrklst-plugin')], 403);
        }
    }

    private function check_permissions()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in', 'wrklst-plugin')], 401);
        }

        // Check for media upload capability for media-related operations
        if (!current_user_can('upload_files') && !current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'wrklst-plugin')], 403);
        }
    }

    public function handle_api_credentials()
    {
        $this->check_permissions();
        
        wp_send_json_success([
            'wrklst_nonce' => wp_create_nonce('wrklst_security_nonce'),
        ]);
    }

    public function handle_get_inventories()
    {
        $this->verify_nonce();
        $this->check_permissions();

        $data = wp_cache_get('wrklst_inventories');
        
        if (false === $data) {
            $wrklst_settings = get_option('wrklst_options');
            
            if (empty($wrklst_settings['api']) || empty($wrklst_settings['account'])) {
                wp_send_json_error(['message' => __('API settings not configured', 'wrklst-plugin')], 500);
            }

            $api_key = sanitize_text_field($wrklst_settings['api']);
            $account = sanitize_text_field($wrklst_settings['account']);
            $wrklst_url = 'https://' . $account . '.wrklst.com';

            $response = wp_remote_get(
                $wrklst_url . '/ext/api/wordpress/inventories',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                    ],
                    'timeout' => 30,
                ]
            );

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => $response->get_error_message()], 500);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => __('Invalid API response', 'wrklst-plugin')], 500);
            }

            wp_cache_set('wrklst_inventories', $data, '', 240);
        }

        wp_send_json_success($data);
    }

    public function handle_get_inventory_items()
    {
        $this->verify_nonce();
        $this->check_permissions();

        $work_status = isset($_POST['work_status']) ? sanitize_text_field($_POST['work_status']) : '';
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // Handle inventory filter - 'all' means no filter
        $inv_sec_id_raw = isset($_POST['inv_sec_id']) ? $_POST['inv_sec_id'] : 'all';
        $inv_sec_id = ($inv_sec_id_raw === 'all' || $inv_sec_id_raw === '0') ? 0 : absint($inv_sec_id_raw);
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        $wrklst_settings = get_option('wrklst_options');
        
        if (empty($wrklst_settings['api']) || empty($wrklst_settings['account'])) {
            wp_send_json_error(['message' => __('API settings not configured', 'wrklst-plugin')], 500);
        }

        $api_key = sanitize_text_field($wrklst_settings['api']);
        $account = sanitize_text_field($wrklst_settings['account']);
        $wrklst_url = 'https://' . $account . '.wrklst.com';

        $cache_key = sprintf(
            'wrklst_inv_req_%s|%d|%d|%d|%s',
            $work_status,
            $per_page,
            $page,
            $inv_sec_id,
            $search
        );
        
        $data = wp_cache_get($cache_key);

        if (false === $data) {
            $query_args = [
                'token' => $api_key,
                'work_status' => $work_status,
                'per_page' => $per_page,
                'page' => $page,
                'search' => $search,
            ];

            // Only add inv_sec_id if a specific inventory is selected
            if ($inv_sec_id > 0) {
                $query_args['inv_sec_id'] = $inv_sec_id;
            }

            if (!empty($wrklst_settings['workdcaptioninvnr'])) {
                $query_args['incinvnr'] = 1;
            }

            $response = wp_remote_get(
                add_query_arg($query_args, $wrklst_url . '/ext/api/wordpress/'),
                ['timeout' => 30]
            );

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => $response->get_error_message()], 500);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => __('Invalid API response', 'wrklst-plugin')], 500);
            }

            wp_cache_set($cache_key, $data, '', 30);
        }

        $wrklst_data = $this->process_inventory_data($data);
        wp_send_json_success($wrklst_data);
    }

    private function process_inventory_data($data)
    {
        if (!isset($data['hits']) || !is_array($data['hits'])) {
            return $data;
        }

        $check_ids = array_column($data['hits'], 'import_source_id');
        
        if (empty($check_ids)) {
            return $data;
        }

        $args = [
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'wrklst_id',
                    'value' => array_map('absint', $check_ids),
                    'compare' => 'IN',
                ],
            ],
        ];

        $query = new \WP_Query($args);
        $existing_attachments = [];

        foreach ($query->posts as $attachment) {
            $wrklst_id = get_post_meta($attachment->ID, 'wrklst_id', true);
            $existing_attachments[$wrklst_id] = $attachment->ID;
        }

        foreach ($data['hits'] as $k => &$hit) {
            $hit['multi_img'] = (int) $hit['multi_img'];
            $hit['wpnonce'] = wp_create_nonce('wrklst_security_nonce');
            $hit['exists'] = 0;

            if ($hit['multi_img'] && isset($hit['imgs']) && is_array($hit['imgs'])) {
                $exists_count = $this->check_multi_images($hit);
                
                if ($exists_count === count($hit['imgs']) && $exists_count > 1) {
                    $hit['exists'] = 2;
                } elseif ($exists_count > 0) {
                    $hit['exists'] = 1;
                }
            } elseif (isset($existing_attachments[$hit['import_source_id']])) {
                $hit['exists'] = 1;
            }
        }

        return $data;
    }

    private function check_multi_images(&$hit)
    {
        $exists_count = 0;

        foreach ($hit['imgs'] as &$img) {
            $img['exists'] = 0;
            
            $args = [
                'post_status' => 'inherit',
                'post_type' => 'attachment',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'wrklst_id',
                        'value' => absint($hit['import_source_id']),
                    ],
                    [
                        'key' => 'wrklst_image_id',
                        'value' => absint($img['id']),
                    ],
                ],
            ];

            $query = new \WP_Query($args);
            
            if ($query->have_posts()) {
                $img['exists'] = 1;
                $exists_count++;
            }
        }

        return $exists_count;
    }

    public function handle_image_upload()
    {
        $this->verify_nonce();
        $this->check_permissions();

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';
        $invnr = isset($_POST['invnr']) ? sanitize_text_field($_POST['invnr']) : '';
        $artist = isset($_POST['artist']) ? sanitize_text_field($_POST['artist']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $caption = isset($_POST['image_caption']) ? sanitize_textarea_field($_POST['image_caption']) : '';
        $description = isset($_POST['image_description']) ? sanitize_textarea_field($_POST['image_description']) : '';
        $alt_text = isset($_POST['image_alt']) ? sanitize_text_field($_POST['image_alt']) : '';
        $import_source_id = isset($_POST['import_source_id']) ? absint($_POST['import_source_id']) : 0;
        $image_id = isset($_POST['image_id']) ? absint($_POST['image_id']) : 0;
        $import_inventory_id = isset($_POST['import_inventory_id']) ? absint($_POST['import_inventory_id']) : 0;
        $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

        // Validate URL
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(['message' => __('Invalid image URL', 'wrklst-plugin')], 400);
        }

        // Ensure URL is from allowed domain
        $parsed_url = wp_parse_url($image_url);
        if ($parsed_url['host'] !== 'img.wrklst.com') {
            wp_send_json_error(['message' => __('Invalid image host', 'wrklst-plugin')], 400);
        }

        // Force HTTPS
        $image_url = set_url_scheme($image_url, 'https');

        // Download image using WordPress functions
        $tmp = download_url($image_url, 300);
        
        if (is_wp_error($tmp)) {
            wp_send_json_error(['message' => $tmp->get_error_message()], 500);
        }

        // Prepare file array for sideload
        $file_array = [
            'name' => $this->generate_safe_filename($artist, $invnr, $search_query),
            'tmp_name' => $tmp,
        ];

        // Check file type
        $wp_filetype = wp_check_filetype_and_ext($file_array['tmp_name'], $file_array['name']);
        
        if (!$wp_filetype['type'] || !$wp_filetype['ext']) {
            @unlink($file_array['tmp_name']);
            wp_send_json_error(['message' => __('Invalid file type', 'wrklst-plugin')], 400);
        }

        // Sideload the image
        $attach_id = media_handle_sideload($file_array, 0, $title);

        if (is_wp_error($attach_id)) {
            @unlink($file_array['tmp_name']);
            wp_send_json_error(['message' => $attach_id->get_error_message()], 500);
        }

        // Update attachment metadata
        $attachment_data = [
            'ID' => $attach_id,
            'post_excerpt' => $caption,
            'post_content' => $description,
        ];
        
        wp_update_post($attachment_data);

        // Update custom meta
        update_post_meta($attach_id, '_wp_attachment_image_alt', $alt_text);
        update_post_meta($attach_id, 'wrklst_id', $import_source_id);
        update_post_meta($attach_id, 'wrklst_image_id', $image_id);
        update_post_meta($attach_id, 'wrklst_inventory_id', $import_inventory_id);
        update_post_meta($attach_id, 'wrklst_artist_name', $artist);
        update_post_meta($attach_id, 'wrklst_inv_nr', $invnr);

        wp_send_json_success(['id' => $attach_id]);
    }

    private function generate_safe_filename($artist, $invnr, $search_query)
    {
        $search_query_tags = explode(' ', $search_query);
        array_unshift($search_query_tags, $artist, $invnr);
        $search_query_tags = array_slice($search_query_tags, 0, 3);

        $safe_tags = array_map(function($tag) {
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $tag);
        }, $search_query_tags);

        $safe_tags = array_filter($safe_tags);
        
        return implode('_', $safe_tags) . '_' . time() . '.jpg';
    }
}