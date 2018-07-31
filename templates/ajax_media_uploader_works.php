<?php
if ( !isset($wp_did_header) ) {

    $wp_did_header = true;

    require_once( dirname(__FILE__) . '/../../../../wp-load.php' );

    wp();

}

if(!is_user_logged_in())die('Not logged in.');
if( !current_user_can('editor') && !current_user_can('administrator') )die('No succifient rights.');


$wrklst_settings = get_option('wrklst_options');

?>
<style scope>
    ::-webkit-input-placeholder { color: #aaa !important; }
    ::-moz-placeholder { color: #aaa !important; }
    :-ms-input-placeholder { color: #aaa !important; }
    [placeholder] { text-overflow: ellipsis; }

    .flex-images { overflow: hidden; }
    .flex-images .item { float: left; margin: 4px; background: #f3f3f3; box-sizing: content-box; overflow: hidden; position: relative; }
    .flex-images .item > img { display: block; width: auto; height: 100%; }
    .flex-images .item > .wrktitle { display: block; position:absolute; text-align: left; left:0; top: 0px; background: rgba(255,255,255,.80); color: #000; padding: 3px 5px 3px 5px;}

    .flex-images .item.exists > .wrktitle { background: rgba(242, 150, 150,.80); }
    .flex-images .item.existsp > .wrktitle { background: rgba(255, 220, 150,.80); }

    .flex-images .dlimg {
            opacity: 0; transition: opacity .3s; position: absolute; top: 0; right: 0; bottom: 0; left: 0;
            cursor: pointer; background: rgba(255,255,255,.80); color: #000;
            text-align: center; font-size: 14px; line-height: 1.5;
    }
    .hidden {display:none !important;}
    .flex-images .item:hover .dlimg, .flex-images .item.uploading .item.doneuploading .dlimg { opacity: 1; }
    .flex-images .item.uploading .dlimg { opacity: 1; }
    .flex-images .item.open .dlimg { opacity: 1; }
    .flex-images .item.doneuploading .dlimg { background: rgba(242, 254, 242,.80); }
    .flex-images .dlimg img { position: absolute; top: 30%; left: 0; right: 0; margin: auto; height: 70px; opacity: .2; }
    .flex-images .dlimg .caption { position: absolute; left: 0; right: 0; bottom: 15px; padding: 0 5px; text-align: left;}
    .flex-images .dlimg a { color: #eee; }
    .flex-images div.subitem > img { width:90% !important;height:90% !important;display: inline-block !important;position: relative;top:5%;}
    .flex-images div.subitem { background: #ccc !important; text-align: center;}
    .flex-images div.breaker {
        background: rgba(242, 150, 150,1);
        height: 1px !important;
        min-width: 100% !important;
    }
    .flex-images .item.subitem .dlimg {
        border: 15px solid #ccc;
    }

    .flex-images .ender .dlimg {
        background: rgba(255, 255, 255,.0) !important;
    }

    img.hide-img {
        display:none;
    }

    #wrklst_settings_icon { opacity: .65; transition: .3s; box-shadow: none; }
    #wrklst_settings_icon:hover { opacity: 1; }

    .loading-rotator{
        transition-property: transform;
        animation-name: rotate;
        animation-duration: 1s;
        animation-iteration-count: infinite;
        animation-timing-function: linear;
    }

    @keyframes rotate {
        from {transform: rotate(0deg);}
        to {transform: rotate(360deg);}
    }
</style>

<div style="padding:10px 10px 10px 10px">
    <form id="wrklst_form" style="margin:0">
        <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
            <input id="search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search for e.g. Agnes Martin AM102">
            <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:1px;outline:0" title="Search"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-search-24px.svg' ?>" width="20px"></button>
        </div>
        <div style="margin:1em 0;padding-left:2px;line-height:2">
            <select id="filter_inventory" tsyle="display:inline-block;">
                <option value="all">
                    Any Inventory
                </option>
            </select>
            <label style="margin-left:15px;margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available">Available only</label>
        </div>
    </form>
    <div id="wrklst_results" class="flex-images" style="margin-top:20px;padding-top:25px;border-top:1px solid #ddd"></div>
</div>
<script>
    function getCookie(key) {
        return (document.cookie.match('(^|; )' + key + '=([^;]*)') || 0)[2]
    }

    function setCookie(n, v, d, s) {
        var date = new Date;
        date.setTime(date.getTime() + 864e5 * d + 1000 * (s || 0)), document.cookie = n + "=" + v + ";path=/;expires=" + date.toGMTString()
    }

    if (getCookie('wrklst_search_query'))
        jQuery('#search_query', form).val(getCookie('wrklst_search_query'));

    jQuery("input[id^='filter_']").each(function(){
        if (getCookie('wrklst_filter_'+this.id) && getCookie('wrklst_filter_'+this.id) != '0') this.checked = true;
    });

    var per_page = 30,
        form = jQuery('#wrklst_form'),
        hits,
        search_query = '',
        page = 1,
        work_status = false,
        api = '<?= $wrklst_settings['api'] ?>',
        account = '<?= $wrklst_settings['account'] ?>',
        last_call = '',
        wrklst_url = '<?PHP
        echo 'https://'.$wrklst_settings['account'].'.wrklst.com';
        ?>';

    var scrollHandler = function(){
        if(jQuery('.media-frame-content').first().scrollTop() + jQuery('.media-frame-content').first().height() > jQuery('.wl-work').first().height() - 400) {
            jQuery('.media-frame-content').first().off('scroll', scrollHandler);
            page = page+1;
            request_api();
        }
    };

    form.submit(function(e){
        page = 1;
        e.preventDefault();
        search_query = jQuery('#search_query', form).val();
        if (jQuery('#filter_available', form).is(':checked')) work_status = 'available';
        jQuery('#wrklst_results').html('');
        jQuery('.media-frame-content').first().off('scroll', scrollHandler);
        request_api();
    });

    (function($) {
        $("#filter_available").change(function() {
            setCookie('wrklst_filter_'+this.id, this.checked ? 1 : 0, 365);
            form.submit();
        });
        $("#search_query").keyup($.debounce(600, function(e) {
            setCookie('wrklst_search_query', jQuery('#search_query', form).val(), 365);
            if(search_query!==jQuery('#search_query', form).val())
                form.submit();
        }));
        $("#filter_inventory").change(function() {
            setCookie('wrklst_filter_inventory', jQuery('#filter_inventory', form).val(), 365);
            form.submit();
        });
    })( jQuery );

    function get_inventories(){
        url_call = wrklst_url+'/ext/api/wordpress/inventories?token='+api;
        var req = new XMLHttpRequest();
        req.open('GET', url_call);
        req.onreadystatechange = function(){
            if (this.status == 200 && this.readyState == 4) {
                var data = JSON.parse(this.responseText);
                jQuery.each(data.inventories, function(k, v) {
                    jQuery('#filter_inventory').append(jQuery('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                });
                if (getCookie('wrklst_filter_inventory'))
                    jQuery('#filter_inventory', form).val(getCookie('wrklst_filter_inventory'));
                form.submit();
            }
        };
        req.send();
    }get_inventories();

    function request_api(){
        //prevent double page loading
        url_call = wrklst_url+'/ext/api/wordpress/?token='+api
            +'&work_status='+work_status
            +'&per_page='+per_page
            +'&page='+page
            +'&inv_sec_id='+jQuery('#filter_inventory', form).val()
            +'&search='+encodeURIComponent(search_query);
        if(last_call===url_call)
        {
            return false;
        }
        var req = new XMLHttpRequest();
        req.open('GET', url_call);
        req.onreadystatechange = function(){
            if (this.status == 200 && this.readyState == 4) {
                var data = JSON.parse(this.responseText);
                if (!(data.totalHits > 0)) {
                    jQuery('#wrklst_results').html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                    jQuery('#show_animation').remove();
                    return false;
                }
                check_existing(data);
            }
        };
        req.send();
        return false;
    }

    function check_existing(wrklst_data) {
        jQuery.post('.', {
            wrklst_check_existing: "1",
            wrklst_data: wrklst_data,
            wpnonce: '<?= wp_create_nonce('wrklst_security_nonce'); ?>'
        }, function(return_data){
            render_results(return_data);
        });
    }

    function render_results(data){
        hits = data['hits'];
        pages = Math.ceil(data.totalHits/per_page);
        var image_item = '';
        jQuery.each(data.hits, function(k, v) {
            var i=0;
            if(v.multi_img) {
                image_item += '<div class="item itemid'+v.import_source_id+' upload multiimg'+(v.exists===2?' exists':(v.exists?' existsp':''))+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
                    +'<img src="'+v.previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                    +'<div class="dlimg">'
                        +'<img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-more_horiz-24px.svg' ?>" class="more">'
                        +'<img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-arrow_forward_ios-24px.svg' ?>" class="open hide-img">'
                        +'<div class="caption">'+v.title+'</div>'
                    +'</div>'
                    +'<div class="wrktitle"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-more_horiz-24px.svg' ?>"><bR />'+(v.exists?'<b>'+(v.exists===2?'all':'partly')+' downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                    +'</div>';
                for(i=0;i<v.imgs.length;i++) {
                    image_item += '<div class="subitem hidden subitemid'+v.import_source_id+' item upload'+(v.imgs[i].exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.imgs[i].largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imgs[i].id+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.imgs[i].photocredit+'" data-w="'+v.imgs[i].webformatWidth+'" data-h="'+v.imgs[i].webformatHeight+'">'
                        +'<img src="'+v.imgs[i].previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                        +'<div class="dlimg">'
                            +'<img src="<?= plugin_dir_url(__FILE__).'../assets/img/round-cloud_download-24px.svg' ?>">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/round-cloud_download-24px.svg' ?>"><bR />'+(v.imgs[i].exists?'<b>downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                        +'</div>';
                }
                image_item += '<div class="item subitemid'+v.import_source_id+' hidden itemid'+v.import_source_id+' ender" data-w="165" data-h="1000">'
                    +'<img src="'+v.previewURL.replace('_150', '_340')+'" style="display:none !important;">'
                    +'<div class="dlimg">'
                        +'<img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-arrow_back_ios-24px.svg' ?>" class="open hide-img">'
                    +'</div>'
                    +'</div>';
            }
            else {
                image_item += '<div class="item upload'+(v.exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
                    +'<img src="'+v.previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                    +'<div class="dlimg">'
                        +'<img src="<?= plugin_dir_url(__FILE__).'../assets/img/round-cloud_download-24px.svg' ?>">'
                        +'<div class="caption">'+v.title+'</div>'
                    +'</div>'
                    +'<div class="wrktitle"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/round-cloud_download-24px.svg' ?>"><bR />'+(v.exists?'<b>downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                    +'</div>';
            }

        });
        jQuery('#wrklst_results').html(jQuery('#wrklst_results').html()+image_item);
        jQuery('#show_animation').remove();
        if (page < pages) {
            jQuery('#wrklst_results').after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-autorenew-24px.svg' ?>" class="loading-rotator"></div>');
            jQuery(window).scroll(scrollHandler);
        }

        jQuery('.flex-images').flexImages({rowHeight: 260});
    }

    jQuery("#wrklst_results").on('click', '.upload.multiimg', function() {
        jQuery( ".subitemid"+jQuery(this).data('import_source_id') ).each(function( index ) {
            jQuery( this ).toggleClass( "hidden" );
        });
        jQuery( ".itemid"+jQuery(this).data('import_source_id')+" .dlimg>img" ).each(function( index ) {
            jQuery( this ).toggleClass( "hide-img" );
        });
        jQuery( ".itemid"+jQuery(this).data('import_source_id') ).each(function( index ) {
            jQuery( this ).toggleClass( "open" );
        });
        jQuery('.flex-images').flexImages({rowHeight: 260});
    });
</script>
