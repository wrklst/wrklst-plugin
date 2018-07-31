<?php
/**
* @package WrkLstPlugin
*/
/**
* Plugin Name: WrkLst Plugin
* Plugin URI: https://github.com/wrklst/wp-wrklst-plugin
* Description: Integrate your WrkLst Database with your Wordpress Website.
* Version: 0.1
* Author: Tobias Vielmetter-Diekmann
* Author URI: https://wrklst.art/
* License: GPL12
*/

if(!function_exists( 'add_action')) die();

if(file_exists(dirname(__FILE__).'/vendor/autoload.php')) {
    require_once dirname(__FILE__).'/vendor/autoload.php';
}

//Plugin Activation
function activate_wrklst_plugin() {
    Inc\Base\Activate::exec();
}

//Plugin Deactivation
function deactivate_wrklst_plugin() {
    Inc\Base\Deactivate::exec();
}

//Register Hooks for Activation/Deactivation
register_activation_hook(__FILE__, 'activate_wrklst_plugin');
register_deactivation_hook(__FILE__, 'deactivate_wrklst_plugin');

//Init full plugin
if(class_exists('Inc\\Init')) {
    Inc\Init::register_services();
}

if (isset($_POST['wrklst_check_existing'])) {
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');

	$nonce = $_POST['wpnonce'];
	if (!wp_verify_nonce($nonce, 'wrklst_security_nonce')) {
        die('Error: Invalid request.');
		exit;
	}
    if(isset($_POST['wrklst_data']['hits']) && count($_POST['wrklst_data']['hits']))
    {
        $check_ids = [];
        $wrklst_data = $_POST['wrklst_data'];
        foreach($_POST['wrklst_data']['hits'] as $dataset)
        {
            $check_ids[] = $dataset['import_source_id'];
        }
        $args = [
            'post_status' => 'inherit',
            'post_type'   => 'attachment',
            'posts_per_page' => -1,
            'meta_key'   => 'wrklst_id',
            'meta_query' => [
        		[
        			'key'     => 'wrklst_id',
        			'value'   => $check_ids,
        			'compare' => 'IN',
        		],
        	],
        ];

        $q = new WP_Query( $args );
        $existing_att = [];
        foreach($q->posts as $att)
        {
            $existing_att[] = [
                'id' => $att->ID,
                'wrklst_id' => (int)get_post_meta( $att->ID,'wrklst_id')[0],
            ];
        }
        foreach($wrklst_data['hits'] as $k => $v)#
        {
            $exists_count = 0;
            //if this works has more than one image, go through the sub images
            $wrklst_data['hits'][$k]['multi_img'] = (int)$wrklst_data['hits'][$k]['multi_img'];
            if($wrklst_data['hits'][$k]['multi_img'])
            {
                foreach($wrklst_data['hits'][$k]['imgs'] as $kk=>$vv)
                {
                    $wrklst_data['hits'][$k]['imgs'][$kk]['exists'] = 0;
                    $args_img = [
                        'post_status' => 'inherit',
                        'post_type'   => 'attachment',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            [
                    			'key'     => 'wrklst_id',
                    			'value'   => $wrklst_data['hits'][$k]['import_source_id'],
                    			'compare' => 'IN',
                    		],
                            [
                    			'key'     => 'wrklst_image_id',
                    			'value'   => $wrklst_data['hits'][$k]['imgs'][$kk]['id'],
                    			'compare' => 'IN',
                    		],
                    	],
                    ];
                    $qimg = new WP_Query( $args_img );
                    if(count($qimg->posts)) {
                        $wrklst_data['hits'][$k]['imgs'][$kk]['exists'] = 1;
                        $exists_count++;
                    }
                }
            }
            $wrklst_data['hits'][$k]['wpnonce'] = wp_create_nonce('wrklst_security_nonce');
            $wrklst_data['hits'][$k]['exists'] = 0;
            if(isset($wrklst_data['hits'][$k]['imgs']) && $exists_count==count($wrklst_data['hits'][$k]['imgs']))
                $wrklst_data['hits'][$k]['exists'] = 2;
            else if($exists_count)
                $wrklst_data['hits'][$k]['exists'] = 1;
            else if(!$wrklst_data['hits'][$k]['multi_img'])
            {
                foreach($existing_att as $att) {
                    if($att['wrklst_id'] == $wrklst_data['hits'][$k]['import_source_id'])
                    {
                        $wrklst_data['hits'][$k]['exists'] = 1;
                        break;
                    }
                }
            }
        }
        //print_r($wrklst_data);die();
        wp_send_json($wrklst_data,200);
        exit;
    }
    wp_send_json($wrklst_data,200);
    exit;
}

//oldschool url to image attachment procedure:
if (isset($_POST['wrklst_upload'])) {
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');

	$nonce = $_POST['wpnonce'];
	if (!wp_verify_nonce($nonce, 'wrklst_security_nonce')) {
        die('Error: Invalid request.');
		exit;
	}

    $wrklst_settings = get_option('wrklst_options');

    $url = str_replace('https:', 'http:', $_POST['image_url']);
    $invnr = $_POST['invnr'];
    $artist = $_POST['artist'];
    $parsed_url = parse_url($url);
    if(strcmp($parsed_url['host'], 'img.wrklst.com')) {
        die('Error: Invalid host in URL (must be img.wrklst.com) '+$parsed_url['host']);
    }

    $response = wp_remote_get($url);
	if (is_wp_error($response)) die('Error: '.$response->get_error_message());

	$search_query_tags = explode(' ' , $_POST['search_query']);
    array_unshift($search_query_tags,$artist,$invnr);
    array_splice($search_query_tags, 2);
    foreach ($search_query_tags as $k=>$v) {
		$v = str_replace("..", "", $v);
		$v = str_replace("/", "", $v);
		$search_query_tags[$k] = trim($v);
	}
    $path_info = pathinfo($url);
	$file_name = sanitize_file_name(implode('_', $search_query_tags).'_'.time().'.jpg');

	$wp_upload_dir = wp_upload_dir();
	$image_upload_path = $wp_upload_dir['path'];

	if (!is_dir($image_upload_path)) {
		if (!@mkdir($image_upload_path, 0777, true)) die('Error: Failed to create upload folder '.$image_upload_path);
	}

	$target_file_name = $image_upload_path . '/' . $file_name;
	$result = @file_put_contents($target_file_name, $response['body']);
	unset($response['body']);
	if ($result === false) die('Error: Failed to write file '.$target_file_name);

	require_once(ABSPATH.'wp-admin/includes/image.php');
	if (!wp_read_image_metadata($target_file_name)) {
		unlink($target_file_name);
		die('Error: File is not an image.');
	}

	$image_title = ''.($_POST['title']).'';
    $attachment_caption = '';
    if(!isset($wrklst_settings['image_caption']))
        $attachment_caption = ''.($_POST['image_caption']).'';
    else if (!$wrklst_settings['image_caption'] | $wrklst_settings['image_caption']=='true')
        $attachment_caption = ''.($_POST['image_caption']).'';

    $wp_filetype = wp_check_filetype(basename($target_file_name), null);

	$attachment = array(
        'guid' => $wp_upload_dir['url'].'/'.basename($target_file_name),
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $image_title,
        'post_content'   => '',
        'post_status' => 'inherit'
	);
    $attach_id = @wp_insert_attachment($attachment, $target_file_name, 0);
    if ($attach_id == 0) die('Error: File attachment error');

	$attach_data = wp_generate_attachment_metadata($attach_id, $target_file_name);
	$result = wp_update_attachment_metadata($attach_id, $attach_data);

	if ($result === false) die('Error: File attachment metadata error');

	$image_data = array();
	$image_data['ID'] = $attach_id;
	$image_data['post_excerpt'] = $attachment_caption;
	@wp_update_post($image_data);

    update_post_meta( $attach_id, 'wrklst_id', $_POST['import_source_id'] );
    update_post_meta( $attach_id, 'wrklst_image_id', $_POST['image_id'] );
    update_post_meta( $attach_id, 'wrklst_inventory_id', $_POST['import_inventory_id'] );
    update_post_meta( $attach_id, 'wrklst_artist_name', $_POST['artist'] );
    update_post_meta( $attach_id, 'wrklst_inv_nr', $_POST['invnr'] );

    wp_send_json(['id'=>$attach_id],200);
    exit;
}
