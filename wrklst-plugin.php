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



if (isset($_POST['wrklst_api_cred'])) {
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');
    if(!is_user_logged_in())die('Not logged in.');
    if( !current_user_can('editor') && !current_user_can('administrator') )die('No succifient rights.');
    wp_send_json([
        'wrklst_nonce' => wp_create_nonce('wrklst_security_nonce'),
    ],200);
    exit;
}



if (isset($_POST['wrklst_get_inventories'])) {
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');

	$nonce = $_POST['wpnonce'];
	if (!wp_verify_nonce($nonce, 'wrklst_security_nonce')) {
        die('Error: Invalid request.');
		exit;
	}

    if(!is_user_logged_in())die('Not logged in.');
    if( !current_user_can('editor') && !current_user_can('administrator') )die('No succifient rights.');

    $data = wp_cache_get( 'wrklst_inventories' );
    if ( false === $data )
    {
        $wrklst_settings = get_option('wrklst_options');
        $api_key = $wrklst_settings['api'];
        $wrklst_url = 'https://'.$wrklst_settings['account'].'.wrklst.com';

        $response = wp_remote_get($wrklst_url.'/ext/api/wordpress/inventories?token='.$api_key);
        if( is_wp_error( $response ) ) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        wp_cache_set( 'wrklst_inventories', $data, '', 240 );
    }
    wp_send_json($data,200);
    exit;
}

if (isset($_POST['wrklst_get_inv_items'])) {
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');

	$nonce = $_POST['wpnonce'];
	if (!wp_verify_nonce($nonce, 'wrklst_security_nonce')) {
        die('Error: Invalid request.');
		exit;
	}

    if(!is_user_logged_in())die('Not logged in.');
    if( !current_user_can('editor') && !current_user_can('administrator') )die('No succifient rights.');
    $wrklst_settings = get_option('wrklst_options');
    $api_key = $wrklst_settings['api'];
    $wrklst_url = 'https://'.$wrklst_settings['account'].'.wrklst.com';

    $cache_key = 'wrklst_inv_req_'.$_POST['work_status'].'|'.$_POST['per_page'].'|'.$_POST['page'].'|'.$_POST['inv_sec_id'].'|'.$_POST['search'];
    $data = wp_cache_get( $cache_key );
    if ( false === $data )
    {
        $response = wp_remote_get(
            $wrklst_url.'/ext/api/wordpress/?token='.$api_key
                .'&work_status='.$_POST['work_status']
                .'&per_page='.$_POST['per_page']
                .'&page='.$_POST['page']
                .'&inv_sec_id='.$_POST['inv_sec_id']
                .'&search='.$_POST['search']);
        if( is_wp_error( $response ) ) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        wp_cache_set( $cache_key, $data, '', 30 );
    }

    $wrklst_data = [];
    if(isset($data['hits']) && count($data['hits']))
    {
        $check_ids = [];
        $wrklst_data = $data;
        foreach($data['hits'] as $dataset)
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


add_action('admin_init', 'wl_bio_admin_init');
add_action('save_post_wlbiography', 'wl_save_post_admin', 10, 3);

function wl_bio_admin_init() {
    add_action('add_meta_boxes_wlbiography','wl_bio_create_metaboxes');
}

function wl_bio_create_metaboxes() {
    add_meta_box('wl_bio_options_mb', 'WrkLst Biography Options', 'wl_bio_options_mb', 'wlbiography', 'normal', 'high');
}

function wl_bio_options_mb($post) {
    $wl_biography_data = get_post_meta($post->ID, 'wl_biography_data', true);

    if(empty($wl_biography_data['artist_id']))
        $wl_biography_data = array('artist_id' => '');
    ?>
    Artist ID:<br />
    <input type="text" name="wl_input_artist_id" value="<?php echo $wl_biography_data['artist_id']; ?>" />
    <?php
}

function wl_save_post_admin($post_id, $post, $update) {
    if(!$update) {
        return;
    }
    $wl_biography_data = [];
    $wl_biography_data['artist_id'] = sanitize_text_field($_POST['wl_input_artist_id']);

    update_post_meta($post_id, 'wl_biography_data', $wl_biography_data);
}

add_shortcode('wrklst_bio_content', 'wl_biography_shortcode');

function wl_biography_shortcode($atts) {
    $a = shortcode_atts( array(
        'id' => 0,
        'newspage' => false,
    ), $atts );
    $id = false;
    if(isset($a['id']) && is_numeric($a['id']))
        $id = (int)$a['id'];
    else if($a['newspage'])
        $id = 'news';

    if(!$id)
        return '';

    //get the corresponding bio or newspage and return the html here.
    $post_query = new WP_Query(array(
        'post_type' => 'wlbiography',
        'posts_per_page' => 1,
        'meta_query' => array( // wl_biography_data
            array(
                'key' => 'wl_biography_data',
                'value' => (serialize(array("artist_id"=>(string)$id))),
                'compare' => '=',
            )
        )
    ));

    if($post_query->post_count>0)
    {
        return ($post_query->posts[0]->post_content);
    }
    return '';
}

if(!is_admin()) {
    function wl_biography_listener() {
        //webhook can be called from thisblog.com/?webhook-listener=wl-biography
        if(isset($_GET['webhook-listener']) && $_GET['webhook-listener'] == 'wl-biography')
        {
            // retrieve the request's body and parse it as JSON
            $body = @file_get_contents('php://input');
            // grab the event information
            $webhook_input = json_decode($body, true);
            if($webhook_input)
            {
                $wrklst_options = get_option('wrklst_options');

                //authenticate token
                if(isset($webhook_input['token']) &&
    				$webhook_input['token']==$wrklst_options['whapikey'] && strlen($wrklst_options['whapikey'])>30)
    			{
                    $wrklst_options['musformatbio'];
                    $wrklst_options['musformatnews'];

                    $m = new \Mustache_Engine(array('escape' => function($value) {
                        if(str_replace('*[[DONOTESCAPE]]*','',$value)!=$value)
                            return str_replace('*[[DONOTESCAPE]]*','',$value);
                        //\Log::info($value);
                        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
                    }));

                    if(isset($webhook_input['artist']) && isset($webhook_input['categories']))
    				{
                        $post_id = 0;

                        $post_query = new WP_Query(array(
                            'post_type' => 'wlbiography',
                            'posts_per_page' => 1,
                            'meta_query' => array( // wl_biography_data
                                array(
                                    'key' => 'wl_biography_data',
                                    'value' => (serialize(array("artist_id"=>(string)$webhook_input['artist']['id']))),
                                    'compare' => '=',
                                )
                            )
                        ));

                        if($post_query->post_count>0)
                        {
                            $post_id = $post_query->posts[0]->ID;
                        }

                        $content_bio = $m->render($wrklst_options['musformatbio'], $webhook_input);

                        $wl_biography_data = [];
                        $wl_biography_data['artist_id'] = sanitize_text_field($webhook_input['artist']['id']);

                        $post_id = wp_insert_post([
                            'ID' => $post_id,
                            'post_content' => wp_kses_post($content_bio),
                            'post_title' => sanitize_text_field($webhook_input['artist']['display']),
                            'post_name' => sanitize_text_field("WrkLst ".$webhook_input['artist']['display']." ".$webhook_input['artist']['id']),
                            'post_status' => 'publish',
                            'post_type' => 'wlbiography',
                        ]);

                        update_post_meta($post_id, 'wl_biography_data', $wl_biography_data);

                        if(isset($webhook_input['news']))
                        {
                            $content_news = $m->render($wrklst_options['musformatnews'], $webhook_input);

                            $post_query = new WP_Query(array(
                                'post_type' => 'wlbiography',
                                'posts_per_page' => 1,
                                'meta_query' => array( // wl_biography_data
                                    array(
                                        'key' => 'wl_biography_data',
                                        'value' => (serialize(array("artist_id"=>"news"))),
                                        'compare' => '=',
                                    )
                                )
                            ));
                            $post_id = 0;
                            if($post_query->post_count>0)
                            {
                                $post_id = $post_query->posts[0]->ID;
                            }

                            $wl_biography_data = [];
                            $wl_biography_data['artist_id'] = 'news';

                            $post_id = wp_insert_post([
                                'ID' => $post_id,
                                'post_content' => wp_kses_post($content_news),
                                'post_title' => 'News',
                                'post_name' => 'WrkLst News',
                                'post_status' => 'publish',
                                'post_type' => 'wlbiography',
                            ]);

                            update_post_meta($post_id, 'wl_biography_data', $wl_biography_data);
                        }

                        //don't show webpage, to save resources, as the webhook was successfull
                        die('1');
    				}
    			}
            }
        }
    }
    add_action('init', 'wl_biography_listener');
}
