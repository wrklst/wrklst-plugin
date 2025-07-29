<?php
/*
TODO:
Mark works already existing based out of combination of id and image id
show multiple images per work.

*/
$wrklst_settings = get_option('wrklst_options');

echo '<h1>WrkLst Works</h1>';
?>
<style scope>
::-webkit-input-placeholder { color: #aaa !important; }
::-moz-placeholder { color: #aaa !important; }
:-ms-input-placeholder { color: #aaa !important; }
[placeholder] { text-overflow: ellipsis; }

.flex-images { overflow: visible; }
.flex-images .item { margin: 4px; background: #f3f3f3; box-sizing: content-box; overflow: hidden; position: relative; }
.flex-images .item > img { width: auto; height: auto; max-width: 100%; max-height: 100%; }
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
/* Multi-image styling */
.flex-images .item.multiimg {
    position: relative;
}

/* Main artwork when expanded */
.flex-images .item.multiimg.open {
    background: #e8e8e8;
    border: 2px solid #999;
    border-right: none;
    margin-right: -2px;
    z-index: 5;
}

.flex-images .item.multiimg.open > .wrktitle {
    /* Keep original styling - no changes */
}

/* Icon visibility for multi-image items */
.flex-images .item.multiimg:not(.open) .dlimg .more {
    display: block;
}

.flex-images .item.multiimg:not(.open) .dlimg .open {
    display: none;
}

.flex-images .item.multiimg.open .dlimg .more {
    display: none !important;
}

.flex-images .item.multiimg.open .dlimg .open {
    display: block !important;
}

/* Sub-items - additional views of the same artwork */
.flex-images div.subitem {
    background: #e8e8e8 !important;
    text-align: center;
    flex: 1 1 220px;
    height: 240px;
    margin: 4px 0;
    position: relative;
    overflow: hidden;
    box-sizing: content-box;
    border: 2px solid #999;
    border-left: none;
    border-right: none;
}

.flex-images div.subitem > img {
    width: auto;
    height: auto;
    max-width: 95%;
    max-height: 80%;
    margin-top: 10px;
}

/* First subitem gets left border */
.flex-images .item.multiimg.open + .subitem {
    border-left: 2px solid #999;
}

/* Last subitem before ender gets right border */
.flex-images .subitem + .ender {
    margin-left: -2px;
}

.flex-images div.subitem > .wrktitle {
    /* Keep original styling - same as regular items */
}

.flex-images div.breaker {
    background: rgba(242, 150, 150,1);
    height: 1px !important;
    min-width: 100% !important;
}

.flex-images .item.subitem .dlimg {
    border: none;
    background: rgba(232, 232, 232, .90);
}

.flex-images .item.subitem:hover .dlimg {
    background: rgba(255, 255, 255, .95);
}

/* End marker - collapse indicator */
.flex-images .item.ender {
    background: #e8e8e8 !important;
    flex: 0 0 60px;
    height: 240px;
    margin: 4px 8px 4px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    overflow: visible;
    border: 2px solid #999;
    position: relative;
    z-index: 1;
}

.flex-images .item.ender:hover {
    background: #ddd !important;
}

.flex-images .ender .dlimg {
    background: transparent !important;
    opacity: 1 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.flex-images .ender .dlimg img {
    opacity: 0.2 !important;
    height: 40px !important;
    width: auto;
    position: static !important;
    margin: 0;
}

.flex-images .item.ender:hover .dlimg img {
    opacity: 0.3 !important;
    transform: scale(1.1);
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

.flex-images {
    display: flex;
    flex-wrap: wrap;
}
.flex-images .item {
    flex: 1 1 260px;
    height: 260px;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Force multi-image groups to stay on same row */
.flex-images .item.multiimg.open {
    flex: 0 0 260px;
}
.flex-images .item.subitem:not(.hidden) {
    flex: 0 0 220px;
}
.flex-images .item.ender:not(.hidden) {
    flex: 0 0 60px;
}

</style>

<div style="padding:10px 10px 10px 0px;">
    <form id="wrklst_form" style="margin:0">
        <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
            <input id="search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search for e.g. Agnes Martin AM102">
            <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:3px;outline:0" title="Search"><img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-search-24px.svg' ?>" width="20px"></button>
        </div>
        <div style="margin:1em 0;padding-left:2px;line-height:2">
            <select id="filter_inventory" tsyle="display:inline-block;">
                <option value="all">
                    Any Inventory
                </option>
            </select>
            <label style="margin-left:15px;margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available">Available only</label>
            <a id="wrklst_settings_icon" href="admin.php?page=wrklst_settings"><img style="position:relative;top:5px" src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-settings-20px.svg' ?>" title="Settings" width="20px"></a>
        </div>
    </form>
    <div id="wrklst_results" class="flex-images" style="margin-top:15px;"></div>
</div>
<script>
jQuery(document).ready(function($) {

    function getCookie(key) {
        return (document.cookie.match('(^|; )' + key + '=([^;]*)') || 0)[2]
    }

    function setCookie(n, v, d, s) {
        var date = new Date;
        date.setTime(date.getTime() + 864e5 * d + 1000 * (s || 0)), document.cookie = n + "=" + v + ";path=/;expires=" + date.toGMTString()
    }

    if (getCookie('wrklst_search_query'))
        $('#search_query', form).val(getCookie('wrklst_search_query'));

    $("input[id^='filter_']").each(function(){
        if (getCookie('wrklst_filter_'+this.id) && getCookie('wrklst_filter_'+this.id) != '0') this.checked = true;
    });

    var per_page = 30,
        form = $('#wrklst_form'),
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
        if($(window).scrollTop() + $(window).height() > $(document).height() - 400) {
            $(window).off('scroll', scrollHandler);
            page = page+1;
            request_api();
        }
    };

    form.submit(function(e){
        page = 1;
        e.preventDefault();
        search_query = $('#search_query', form).val();
        if ($('#filter_available', form).is(':checked')) work_status = 'available';
        $('#wrklst_results').html('');
        $(window).off('scroll', scrollHandler);
        request_api();
    });


    $("#filter_available").change(function() {
        setCookie('wrklst_filter_'+this.id, this.checked ? 1 : 0, 365);
        form.submit();
    });
    $("#search_query").keyup($.debounce(600, function(e) {
        setCookie('wrklst_search_query', $('#search_query', form).val(), 365);
        if(search_query!==$('#search_query', form).val())
            form.submit();
    }));
    $("#filter_inventory").change(function() {
        setCookie('wrklst_filter_inventory', $('#filter_inventory', form).val(), 365);
        form.submit();
    });


    function get_api_cred(){
        WrkLstAjax.getApiCredentials(function(data){
            wrklst_security_nonce = data.wrklst_nonce;
            get_inventories();
        });
    }
    get_api_cred();

    function get_inventories(){
        WrkLstAjax.getInventories(wrklst_security_nonce, function(data){
            $.each(data.inventories, function(k, v) {
                $('#filter_inventory').append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
            });
            if (getCookie('wrklst_filter_inventory'))
                $('#filter_inventory', form).val(getCookie('wrklst_filter_inventory'));
            form.submit();
        });
    }

    function request_api(){
        //prevent double page loading
        url_call = work_status+'|'+per_page+'|'+page+'|'+$('#filter_inventory', form).val()+'|'+encodeURIComponent(search_query)+'|'+wrklst_security_nonce;
        if(last_call===url_call)
        {
            return false;
        }

        WrkLstAjax.getInventoryItems({
            work_status: work_status,
            per_page: per_page,
            page: page,
            inv_sec_id: $('#filter_inventory', form).val(),
            search: encodeURIComponent(search_query),
            wpnonce: wrklst_security_nonce
        }, function(data){
            if (!(data.totalHits > 0)) {
                $('#wrklst_results').html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                $('#show_animation').remove();
                return false;
            }
            render_results(data);
            last_call = url_call;
        });
        return false;
    }

    function render_results(data){
        hits = data['hits'];
        pages = Math.ceil(data.totalHits/per_page);
        var image_item = '';
        $.each(data.hits, function(k, v) {
            var i=0;
            if(v.multi_img) {
                image_item += '<div class="item itemid'+v.import_source_id+' upload multiimg'+(v.exists===2?' exists':(v.exists?' existsp':''))+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-description="'+v.description+'" data-alt="'+v.alt+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
                    +'<img src="'+v.previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                    +'<div class="dlimg">'
                        +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-more_horiz-24px.svg" class="more">'
                        +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-arrow_forward_ios-24px.svg" class="open hide-img">'
                        +'<div class="caption">'+v.title+'</div>'
                    +'</div>'
                    +'<div class="wrktitle"><img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-more_horiz-24px.svg"><bR />'+(v.exists?'<b>'+(v.exists===2?'all':'partially')+' downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                    +'</div>';
                for(i=0;i<v.imgs.length;i++) {
                    image_item += '<div class="subitem hidden subitemid'+v.import_source_id+' item upload'+(v.imgs[i].exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.imgs[i].largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imgs[i].id+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.imgs[i].photocredit+'" data-description="'+v.description+'" data-alt="'+v.alt+'" data-w="'+v.imgs[i].webformatWidth+'" data-h="'+v.imgs[i].webformatHeight+'">'
                        +'<img src="'+v.imgs[i].previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                        +'<div class="dlimg">'
                            +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg"><bR />'+(v.imgs[i].exists?'<b>downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                        +'</div>';
                }
                image_item += '<div class="item itemid'+v.import_source_id+' subitemid'+v.import_source_id+' hidden ender" data-w="165" data-h="1000" data-import_source_id="'+v.import_source_id+'">'
                    +'<div class="dlimg">'
                        +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-arrow_back_ios-24px.svg">'
                    +'</div>'
                    +'</div>';
            }
            else {
                image_item += '<div class="item upload'+(v.exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-description="'+v.description+'" data-alt="'+v.alt+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
                    +'<img src="'+v.previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                    +'<div class="dlimg">'
                        +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg">'
                        +'<div class="caption">'+v.title+'</div>'
                    +'</div>'
                    +'<div class="wrktitle"><img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg"><bR />'+(v.exists?'<b>downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                    +'</div>';
            }

        });
        $('#wrklst_results').html($('#wrklst_results').html()+image_item);
        $('#show_animation').remove();
        if (page < pages) {
            $('#wrklst_results').after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-autorenew-24px.svg" class="loading-rotator"></div>');
            $(window).scroll(scrollHandler);
        }
    }

    $("#wrklst_results").on('click', '.upload.multiimg', function() {
        $( ".subitemid"+$(this).data('import_source_id') ).each(function( index ) {
            $( this ).toggleClass( "hidden" );
        });
        $( ".itemid"+$(this).data('import_source_id')+".multiimg .dlimg>img" ).each(function( index ) {
            $( this ).toggleClass( "hide-img" );
        });
        $( ".itemid"+$(this).data('import_source_id') ).each(function( index ) {
            $( this ).toggleClass( "open" );
        });
    });
    
    // Add click handler for ender element to close the group
    $("#wrklst_results").on('click', '.item.ender', function(e) {
        e.stopPropagation();
        var import_source_id = $(this).data('import_source_id');
        $( ".subitemid"+import_source_id ).addClass( "hidden" );
        $( ".itemid"+import_source_id ).removeClass( "open" );
        $( ".itemid"+import_source_id+".multiimg .dlimg>img" ).removeClass( "hide-img" );
    });
    $("#wrklst_results").on('click', '.upload:not(.doneuploading)', function() {
        if(!$(this).hasClass('uploading')&&!$(this).hasClass('doneuploading')&&!$(this).hasClass('multiimg'))
        {
            $(this).addClass('uploading').find('.dlimg img').replaceWith('<img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-autorenew-24px.svg' ?>" class="loading-rotator" style="height:80px !important">');
            var that = $(this);
            WrkLstAjax.uploadImage({
                image_url: $(this).data('url'),
                image_caption: $(this).data('caption'),
                image_description: $(this).data('description'),
                image_alt: $(this).data('alt'),
                title: $(this).data('title'),
                invnr: $(this).data('invnr'),
                artist: $(this).data('artist'),
                import_source_id: $(this).data('import_source_id'),
                image_id: $(this).data('image_id'),
                import_inventory_id: $(this).data('import_inventory_id'),
                search_query: search_query,
                wpnonce: '<?= wp_create_nonce('wrklst_security_nonce'); ?>'
            }, function(data){
                that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="<?= plugin_dir_url(__FILE__).'../assets/img/baseline-check-24px.svg' ?>" style="height:50px !important">');
            });
        }
        return false;
    });
});
</script>
