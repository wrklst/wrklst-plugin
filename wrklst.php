<?php

/**
* Plugin Name: WrkLst
* Plugin URI: https://wrklst.art/
* Description: Integrate your WrkLst Database with your Wordpress Website.
* Version: 0.1
* Author: Tobias Vielmetter-Diekmann
* Author URI: https://wrklst.art/
* License: GPL12
*/


//putenv("APP_ENV=local");

// i18n
function wrklst_images_load_textdomain() { load_plugin_textdomain('wrklst_images', false, dirname(plugin_basename(__FILE__ )).'/langs/'); }
add_action('plugins_loaded', 'wrklst_images_load_textdomain');


// add settings
include(plugin_dir_path(__FILE__).'settings.php');


function wrklst_images_enqueue_jquery() { wp_enqueue_script('jquery'); }
add_action('admin_enqueue_scripts', 'wrklst_images_enqueue_jquery');


// add tab to media upload window
function media_upload_tabs_handler($tabs) { $tabs['wrklsttab'] = __('WrkLst', 'wrklst_images'); return $tabs; }
add_filter('media_upload_tabs', 'media_upload_tabs_handler');


// add button next to "Add Media"
$wrklst_images_settings = get_option('wrklst_images_options');
if (!$wrklst_images_settings['button'] | $wrklst_images_settings['button']=='true') {
    function media_buttons_context_handler($editor_id='') { return '<a href="'.add_query_arg('tab', 'wrklsttab', esc_url(get_upload_iframe_src())).'" id="'.esc_attr($editor_id).'-add_media" class="thickbox button" title="'.esc_attr__('WrkLst', 'wrklst_images').'"><img style="position:relative;top:-2px;height:20px;" src="'.plugin_dir_url(__FILE__).'img/apple-touch-icon.png'.'">WrkLst</a>'; }
    add_filter('media_buttons_context', 'media_buttons_context_handler');
}


// media tab action
// function must begin with "media_" so wp_iframe() adds media css styles
function media_wrklst_images_tab() {
    media_upload_header();
    $wrklst_images_settings = get_option('wrklst_images_options');
	?>
        <style scope>
            html, body { background: #fff; }

            ::-webkit-input-placeholder { color: #aaa !important; }
            ::-moz-placeholder { color: #aaa !important; }
            :-ms-input-placeholder { color: #aaa !important; }
            [placeholder] { text-overflow: ellipsis; }

            .flex-images { overflow: hidden; }
            .flex-images .item { float: left; margin: 4px; background: #f3f3f3; box-sizing: content-box; overflow: hidden; position: relative; }
            .flex-images .item > img { display: block; width: auto; height: 100%; }

            .flex-images .download {
                    opacity: 0; transition: opacity .3s; position: absolute; top: 0; right: 0; bottom: 0; left: 0;
                    cursor: pointer; background: rgba(0,0,0,.65); color: #eee;
                    text-align: center; font-size: 14px; line-height: 1.5;
            }
            .flex-images .item:hover .download, .flex-images .item.uploading .download { opacity: 1; }
            .flex-images .download img { position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: auto; height: 32px; opacity: .7; }
            .flex-images .download div { position: absolute; left: 0; right: 0; bottom: 15px; padding: 0 5px; }
            .flex-images .download a { color: #eee; }

            #wrklst_settings_icon { opacity: .65; transition: .3s; box-shadow: none; }
            #wrklst_settings_icon:hover { opacity: 1; }
        </style>
        <div style="padding:10px 15px 25px">
            <form id="wrklst_images_form" style="margin:0">
                <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
                    <input id="q" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="<?= htmlspecialchars(__('Search for e.g. Agnes Martin AM102', 'wrklst_images')); ?>">
                    <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:3px;outline:0" title="<?= _e('Search', 'wrklst_images'); ?>"><img src="<?= plugin_dir_url(__FILE__).'img/ic_search_3x.png' ?>" width="20px"></button>
                </div>
                <div style="margin:1em 0;padding-left:2px;line-height:2">
                    <label style="margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available"><?= _e('Available only', 'wrklst_images'); ?></label>
                    <a id="wrklst_settings_icon" href="options-general.php?page=wrklst_images_settings" target="_blank"><img style="position:relative;top:5px" src="<?= plugin_dir_url(__FILE__).'img/ic_settings_3x.png' ?>" title="<?= _e('Settings', 'wrklst_images'); ?>" width="20px"></a>
                </div>
            </form>
            <div id="wrklst_results" class="flex-images" style="margin-top:20px;padding-top:25px;border-top:1px solid #ddd"></div>
        </div>
        <script>
            // flexImages
            !function(t){function e(t,a,r,n){function o(t){r.maxRows&&d>r.maxRows||r.truncate&&t&&d>1?w[g][0].style.display="none":(w[g][4]&&(w[g][3].attr("src",w[g][4]),w[g][4]=""),w[g][0].style.width=l+"px",w[g][0].style.height=u+"px",w[g][0].style.display="block")}var g,l,s=1,d=1,f=t.width()-2,w=[],c=0,u=r.rowHeight;for(f||(f=t.width()-2),i=0;i<a.length;i++)if(w.push(a[i]),c+=a[i][2]+r.margin,c>=f){var m=w.length*r.margin;for(s=(f-m)/(c-m),u=Math.ceil(r.rowHeight*s),exact_w=0,l,g=0;g<w.length;g++)l=Math.ceil(w[g][2]*s),exact_w+=l+r.margin,exact_w>f&&(l-=exact_w-f),o();w=[],c=0,d++}for(g=0;g<w.length;g++)l=Math.floor(w[g][2]*s),h=Math.floor(r.rowHeight*s),o(!0);n||f==t.width()||e(t,a,r,!0)}t.fn.flexImages=function(a){var i=t.extend({container:".item",object:"img",rowHeight:180,maxRows:0,truncate:0},a);return this.each(function(){var a=t(this),r=t(a).find(i.container),n=[],o=(new Date).getTime(),h=window.getComputedStyle?getComputedStyle(r[0],null):r[0].currentStyle;for(i.margin=(parseInt(h.marginLeft)||0)+(parseInt(h.marginRight)||0)+(Math.round(parseFloat(h.borderLeftWidth))||0)+(Math.round(parseFloat(h.borderRightWidth))||0),j=0;j<r.length;j++){var g=r[j],l=parseInt(g.getAttribute("data-w")),s=l*(i.rowHeight/parseInt(g.getAttribute("data-h"))),d=t(g).find(i.object);n.push([g,l,s,d,d.data("src")])}e(a,n,i),t(window).off("resize.flexImages"+a.data("flex-t")),t(window).on("resize.flexImages"+o,function(){e(a,n,i)}),a.data("flex-t",o)})}}(jQuery);
            function getCookie(k){return(document.cookie.match('(^|; )'+k+'=([^;]*)')||0)[2]}
            function setCookie(n,v,d,s){var o=new Date;o.setTime(o.getTime()+864e5*d+1000*(s||0)),document.cookie=n+"="+v+";path=/;expires="+o.toGMTString()}
            function escapejs(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,"\\'");}

            // set checkbox filters
            jQuery("input[id^='filter_']").each(function(){
                if (getCookie('px_'+this.id) != '0') this.checked = true;
                jQuery(this).change(function(){ setCookie('px_'+this.id, this.checked ? 1 : 0, 365); });
            });

            var post_id = <?=absint($_REQUEST['post_id']) ?>,
                lang = '<?= $wrklst_images_settings['language']?$wrklst_images_settings['language']:substr(get_locale(), 0, 2) ?>',
                per_page = 30, form = jQuery('#wrklst_images_form'), hits, q, work_status,
                api = '<?= $wrklst_images_settings['api'] ?>',
                account = '<?= $wrklst_images_settings['account'] ?>',
                wrklst_url = '<?PHP
                if(getenv(APP_ENV, false)=='local')
                    echo 'http://'.$wrklst_images_settings['account'].'.wrklst.dev:3000';
                else
                    echo 'https://'.$wrklst_images_settings['account'].'.wrklst.com';
                ?>';
            form.submit(function(e){
                e.preventDefault();
                q = jQuery('#q', form).val();
                if (jQuery('#filter_available', form).is(':checked')) work_status = 'available';
                jQuery('#wrklst_results').html('');
                call_api(q, 1);
            });

            function call_api(q, p){
                var xhr = new XMLHttpRequest();
                xhr.open('GET', wrklst_url+'/ext/api/wordpress/?token='+api+'&work_status='+work_status+'&per_page='+per_page+'&page='+p+'&search='+encodeURIComponent(q));
                xhr.onreadystatechange = function(){
                    if (this.status == 200 && this.readyState == 4) {
                        var data = JSON.parse(this.responseText);
                        if (!(data.totalHits > 0)) {
                            jQuery('#wrklst_results').html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— <?= _e('No matches', 'wrklst_images') ?> ——</div>');
                            return false;
                        }
                        render_px_results(q, p, data);
                    }
                };
                xhr.send();
                return false;
            }

            function render_px_results(q, p, data){
                hits = data['hits']; // store for upload click
                pages = Math.ceil(data.totalHits/per_page);
                var s = '';
                jQuery.each(data.hits, function(k, v) {
                    s += '<div class="item upload" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-caption="'+v.caption+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'"><img src="'+v.previewURL.replace('_150', '_340')+'"><div class="download"><img src="<?= plugin_dir_url(__FILE__).'img/ic_file_download_3x.png' ?>"><div>'+(v.webformatWidth*2)+'×'+(v.webformatHeight*2)+'<br>'+v.caption+'</div></div></div>';
                });
                jQuery('#wrklst_results').html(jQuery('#wrklst_results').html()+s);
                jQuery('#load_animation').remove();
                if (p < pages) {
                    jQuery('#wrklst_results').after('<div id="load_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="<?= plugin_dir_url(__FILE__).'img/loading.gif' ?>"></div>');
                    jQuery(window).scroll(function() {
                       if(jQuery(window).scrollTop() + jQuery(window).height() > jQuery(document).height() - 400) {
                           jQuery(window).off('scroll');
                           call_api(q, p+1);
                       }
                    });
                }

                jQuery('.flex-images').flexImages({rowHeight: 260});
            }

            jQuery(document).on('click', '.upload', function() {
                jQuery(document).off('click', '.upload');
                // loading animation
                jQuery(this).addClass('uploading').find('.download img').replaceWith('<img src="<?= plugin_dir_url(__FILE__).'img/loading.gif' ?>" style="height:80px !important">');
                jQuery.post('.', { wrklst_upload: "1", image_url: jQuery(this).data('url'), image_caption: jQuery(this).data('caption'), invnr: jQuery(this).data('invnr'), artist: jQuery(this).data('artist'), q: q, wpnonce: '<?= wp_create_nonce('wrklst_images_security_nonce'); ?>' }, function(data){
                    if (parseInt(data) == data)
                        window.location = 'media-upload.php?type=image&tab=library&post_id='+post_id+'&attachment_id='+data;
                    else
                        alert(data);
                });
                return false;
            });
        </script>
    <?php
}
function media_upload_wrklsttab_handler() { wp_iframe('media_wrklst_images_tab'); }
add_action('media_upload_wrklsttab', 'media_upload_wrklsttab_handler');


if (isset($_POST['wrklst_upload'])) {
    # "pluggable.php" is required for wp_verify_nonce() and other upload related helpers
    if (!function_exists('wp_verify_nonce'))
        require_once(ABSPATH.'wp-includes/pluggable.php');

	$nonce = $_POST['wpnonce'];
	if (!wp_verify_nonce($nonce, 'wrklst_images_security_nonce')) {
        die('Error: Invalid request.');
		exit;
	}

    $post_id = absint($_REQUEST['post_id']);
    $wrklst_images_settings = get_option('wrklst_images_options');

    // parse image_url
    $url = str_replace('https:', 'http:', $_POST['image_url']);
    $invnr = $_POST['invnr'];
    $artist = $_POST['artist'];
    $parsed_url = parse_url($url);
    if(strcmp($parsed_url['host'], 'img.wrklst.com')) {
        die('Error: Invalid host in URL (must be img.wrklst.com) '+$parsed_url['host']);
    }

    // get image file
	$response = wp_remote_get($url);
	if (is_wp_error($response)) die('Error: '.$response->get_error_message());

	$q_tags = explode(' ' , $_POST['q']);
    array_unshift($q_tags,$artist,$invnr);
    array_splice($q_tags, 2);
    foreach ($q_tags as $k=>$v) {
		// remove ../../../..
		$v = str_replace("..", "", $v);
		$v = str_replace("/", "", $v);
		$q_tags[$k] = trim($v);
	}
    $path_info = pathinfo($url);
	$file_name = sanitize_file_name(implode('_', $q_tags).'_'.time().'.jpg');

	$wp_upload_dir = wp_upload_dir();
	$image_upload_path = $wp_upload_dir['path'];

	if (!is_dir($image_upload_path)) {
		if (!@mkdir($image_upload_path, 0777, true)) die('Error: Failed to create upload folder '.$image_upload_path);
	}

	$target_file_name = $image_upload_path . '/' . $file_name;
	$result = @file_put_contents($target_file_name, $response['body']);
	unset($response['body']);
	if ($result === false) die('Error: Failed to write file '.$target_file_name);

	// are we dealing with an image
    require_once(ABSPATH.'wp-admin/includes/image.php');
	if (!wp_read_image_metadata($target_file_name)) {
		unlink($target_file_name);
		die('Error: File is not an image.');
	}

	$image_title = ucwords(implode(', ', $q_tags));
    $attachment_caption = '';
    if (!$wrklst_images_settings['attribution'] | $wrklst_images_settings['attribution']=='true')
        $attachment_caption = ''.($_POST['image_caption']).'';

    // insert attachment
	$wp_filetype = wp_check_filetype(basename($target_file_name), null);
	$attachment = array(
        'guid' => $wp_upload_dir['url'].'/'.basename($target_file_name),
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $image_title),
        'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment($attachment, $target_file_name, $post_id);
	if ($attach_id == 0) die('Error: File attachment error');

	$attach_data = wp_generate_attachment_metadata($attach_id, $target_file_name);
	$result = wp_update_attachment_metadata($attach_id, $attach_data);
	if ($result === false) die('Error: File attachment metadata error');

	$image_data = array();
	$image_data['ID'] = $attach_id;
	$image_data['post_excerpt'] = $attachment_caption;
	wp_update_post($image_data);

	echo $attach_id;
    exit;
}

?>
