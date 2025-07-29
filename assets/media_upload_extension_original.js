//based on https://wordpress.stackexchange.com/questions/130065/add-item-to-media-library-from-blob-or-dataurl
(function($){
if(typeof wp.media === "undefined") return false;
var media = wp.media,
    frame,
    l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

// override router creation
media.view.MediaFrame.Select.prototype.browseRouter = function( view ) {
    view.set({
        upload: {
            text:     l10n.uploadFilesTitle,
            priority: 20
        },
        wlwork: {
            text:     'Import WrkLst Work',
            priority: 30
        },
        browse: {
            text:     l10n.mediaLibraryTitle,
            priority: 40
        }
    });
};

var bindHandlers = media.view.MediaFrame.Select.prototype.bindHandlers,
    wlWork, frame;

media.view.MediaFrame.Select.prototype.bindHandlers = function() {
    // bind parent object handlers
    bindHandlers.apply( this, arguments );
    // bind our create handler.
    this.on( 'content:create:wlwork', this.wlworkContent, this );
    frame = this;
};
media.view.MediaFrame.Select.prototype.wlworkContent = function( content ){
    // generate test content
    var state = this.state();
    this.$el.removeClass('hide-toolbar');
    wlWork = new media.view.wlWork({});
    content.view = wlWork;
}


media.view.wlWork = media.View.extend({
    tagName:   'div',
    className: 'wl-work',
    id: 'wlworkcontainer',
    initialize: function() {
        _.defaults( this.options, {});
        var self = this;
        
        // Build the interface directly instead of loading external PHP
        this.buildInterface();
    },
    
    buildInterface: function() {
        var self = this;
        var $container = this.$el;
        
        // Add the styles
        if (!$('#wrklst-media-picker-styles').length) {
            $('head').append(`
                <style id="wrklst-media-picker-styles">
                    ::-webkit-input-placeholder { color: #aaa !important; }
                    ::-moz-placeholder { color: #aaa !important; }
                    :-ms-input-placeholder { color: #aaa !important; }
                    [placeholder] { text-overflow: ellipsis; }

                    .flex-images { overflow: hidden; }
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
                    /* Multi-image group styling - cleaner approach */
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
                        background: rgba(248, 248, 248, .90);
                    }
                    
                    .flex-images .item.subitem:hover .dlimg {
                        background: rgba(255, 255, 255, .95);
                    }
                    
                    /* End marker - collapse indicator */
                    .flex-images .item.ender {
                        background: #e8e8e8 !important;
                        flex: 0 0 60px;
                        height: 240px;
                        margin: 4px 4px 4px 0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        transition: all 0.2s;
                        overflow: visible;
                        border: 2px solid #999;
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
                        align-content: flex-start;
                    }
                    .flex-images .item {
                        flex: 1 1 260px;
                        height: 260px;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                    }
                    
                    /* Create visual grouping with subtle background */
                    .flex-images .item.multiimg.open ~ .subitem:not(.hidden),
                    .flex-images .item.multiimg.open ~ .ender:not(.hidden) {
                        position: relative;
                    }
                    
                    /* Remove any margins between expanded items */
                    .flex-images .item.multiimg.open,
                    .flex-images .item.subitem:not(.hidden),
                    .flex-images .item.ender:not(.hidden) {
                        border-radius: 0;
                    }
                    
                    /* Create a continuous visual block */
                    .flex-images .item.multiimg.open ~ .subitem:not(.hidden) + .subitem,
                    .flex-images .item.multiimg.open ~ .subitem:not(.hidden) + .ender {
                        margin-left: -1px;
                    }
                    
                    /* Add subtle background to unify the group */
                    .flex-images .item.multiimg.open {
                        position: relative;
                    }
                    
                    /* Alternative: use box-shadow to create depth without gaps */
                    .flex-images .item.multiimg.open,
                    .flex-images .item.subitem:not(.hidden),
                    .flex-images .item.ender:not(.hidden) {
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }

                </style>
            `);
        }
        
        // Create the HTML structure
        var html = `
            <div style="padding:10px 10px 10px 10px">
                <form id="wrklst_form" style="margin:0">
                    <div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">
                        <input id="search_query" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" autofocus placeholder="Search for e.g. Agnes Martin AM102">
                        <button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:1px;outline:0" title="Search">🔍</button>
                    </div>
                    <div style="margin:1em 0;padding-left:2px;line-height:2">
                        <select id="filter_inventory" style="display:inline-block;">
                            <option value="all">Any Inventory</option>
                        </select>
                        <label style="margin-left:15px;margin-right:15px;white-space:nowrap"><input type="checkbox" id="filter_available">Available only</label>
                    </div>
                </form>
                <div id="show_animation" style="clear:both;text-align:center;margin: 80px 0"><span class="spinner is-active" style="float:none"></span></div>
                <div id="wrklst_results" class="flex-images" style="margin-top:15px;"></div>
            </div>
        `;
        
        this.$el.html(html);
        
        //cookie control of forms
        function getCookie(key) {
            return (document.cookie.match('(^|; )' + key + '=([^;]*)') || 0)[2];
        }

        function setCookie(n, v, d, s) {
            var date = new Date;
            date.setTime(date.getTime() + 864e5 * d + 1000 * (s || 0)), document.cookie = n + "=" + v + ";path=/;expires=" + date.toGMTString();
        }

        var form = $('#wrklst_form', this.$el);
        
        if (getCookie('wrklst_search_query'))
            $('#search_query', form).val(getCookie('wrklst_search_query'));

        $("input[id^='filter_']", form).each(function(){
            if (getCookie('wrklst_filter_'+this.id) && getCookie('wrklst_filter_'+this.id) != '0') this.checked = true;
        });

        //setup base variables for this method
        var per_page = 30,
            hits,
            search_query = '',
            page = 1,
            work_status = '',
            last_call = '',
            wrklst_security_nonce = false,
            wrklst_url = 'https://app.wrklst.com';

        //get api credentials and nonce
        function get_api_cred(){
            // Check if nonce is already available from localized script
            if (typeof wrklst_ajax !== 'undefined' && wrklst_ajax.nonce) {
                wrklst_security_nonce = wrklst_ajax.nonce;
                get_inventories();
                return;
            }
            
            // Fallback to AJAX call
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getApiCredentials(function(data){
                    wrklst_security_nonce = data.wrklst_nonce;
                    get_inventories();
                });
            } else {
                // Direct jQuery AJAX fallback
                $.post(ajaxurl || '.', {
                    action: 'wrklst_api_cred',
                }, function(data){
                    if (data.success) {
                        wrklst_security_nonce = data.data.wrklst_nonce;
                    }
                    get_inventories();
                });
            }
        }
        get_api_cred();

        //trigger load more when page is scrolled further down and there are more records to get
        var scrollHandler = function(){
            if($('.media-frame-content').first().scrollTop() + $('.media-frame-content').first().height() > $('.wl-work').first().height() - 400) {
                $('.media-frame-content').first().off('scroll', scrollHandler);
                page = page+1;
                request_api();
            }
        };

        //what to do on form submit, prevent default and handle submission
        form.submit(function(e){
            page = 1;
            e.preventDefault();
            search_query = $('#search_query', form).val();
            work_status = $('#filter_available', form).is(':checked') ? 'available' : '';
            $('#wrklst_results', $container).html('');
            $('.media-frame-content').first().off('scroll', scrollHandler);
            request_api();
        });

        //handle filter changes
        $("#filter_available", form).change(function() {
            setCookie('wrklst_filter_'+this.id, this.checked ? 1 : 0, 365);
            form.submit();
        });
        $("#search_query", form).keyup($.debounce(600, function(e) {
            setCookie('wrklst_search_query', $('#search_query', form).val(), 365);
            if(search_query!==$('#search_query', form).val())
                form.submit();
        }));
        $("#filter_inventory", form).change(function() {
            setCookie('wrklst_filter_inventory', $('#filter_inventory', form).val(), 365);
            form.submit();
        });

        //get inventories from wrklst
        function get_inventories() {
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getInventories(wrklst_security_nonce, function(data){
                    if (data && data.inventories) {
                        $.each(data.inventories, function(k, v) {
                            $('#filter_inventory', form).append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                        });
                        if (getCookie('wrklst_filter_inventory'))
                            $('#filter_inventory', form).val(getCookie('wrklst_filter_inventory'));
                    }
                    form.submit();
                });
            } else {
                // Direct jQuery AJAX fallback
                $.post(ajaxurl || '.', {
                    action: 'wrklst_get_inventories',
                    wpnonce: wrklst_security_nonce
                }, function(response){
                    if (response.success && response.data && response.data.inventories) {
                        $.each(response.data.inventories, function(k, v) {
                            $('#filter_inventory', form).append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                        });
                        if (getCookie('wrklst_filter_inventory'))
                            $('#filter_inventory', form).val(getCookie('wrklst_filter_inventory'));
                    }
                    form.submit();
                });
            }
        }

        //get images for one page from wrklst
        function request_api() {
            //prevent double page loading
            url_call = work_status+'|'+per_page+'|'+page+'|'+$('#filter_inventory', form).val()+'|'+encodeURIComponent(search_query)+'|'+wrklst_security_nonce;
            if(last_call===url_call)
            {
                return false;
            }

            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getInventoryItems({
                    work_status: work_status,
                    per_page: per_page,
                    page: page,
                    inv_sec_id: $('#filter_inventory', form).val(),
                    search: encodeURIComponent(search_query),
                    wpnonce: wrklst_security_nonce
                }, function(data){
                    if (!(data.totalHits > 0)) {
                        $('#wrklst_results', $container).html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                        $('#show_animation', $container).remove();
                        return false;
                    }
                    render_results(data);
                    last_call = url_call;
                });
            } else {
                // Direct jQuery AJAX fallback
                $.post(ajaxurl || '.', {
                    action: 'wrklst_get_inv_items',
                    work_status: work_status,
                    per_page: per_page,
                    page: page,
                    inv_sec_id: $('#filter_inventory', form).val(),
                    search: encodeURIComponent(search_query),
                    wpnonce: wrklst_security_nonce
                }, function(response){
                    if (response.success) {
                        var data = response.data;
                        if (!(data.totalHits > 0)) {
                            $('#wrklst_results', $container).html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                            $('#show_animation', $container).remove();
                            return false;
                        }
                        render_results(data);
                        last_call = url_call;
                    }
                });
            }
            return false;
        }

        //render image results into dom
        function render_results(data){
            hits = data['hits'];
            pages = Math.ceil(data.totalHits/per_page);
            var image_item = '';
            $.each(data.hits, function(k, v) {
                var i=0;
                if(v.multi_img=="1") {
                    // Get the base URL for icons - using plugin URL
                    var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                    
                    image_item += '<div class="item itemid'+v.import_source_id+' upload multiimg'+(v.exists===2?' exists':(v.exists?' existsp':''))+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+(v.largeImageURL || v.url_full)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+(v.imageId || 0)+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.photocredit || '')+'" data-w="'+(v.webformatWidth || 0)+'" data-h="'+(v.webformatHeight || 0)+'">'
                        +'<img src="'+(v.previewURL || v.url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                        +'<div class="dlimg">'
                            +'<img src="'+iconBase+'baseline-more_horiz-24px.svg" class="more">'
                            +'<img src="'+iconBase+'baseline-arrow_forward_ios-24px.svg" class="open">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="'+iconBase+'baseline-more_horiz-24px.svg"><br />'+(v.exists?'<b>'+(v.exists===2?'all':'partly')+' downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                        +'</div>';
                    for(i=0;i<v.imgs.length;i++) {
                        image_item += '<div class="subitem hidden subitemid'+v.import_source_id+' item upload'+(v.imgs[i].exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+(v.imgs[i].largeImageURL || v.imgs[i].url_full)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imgs[i].id+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.imgs[i].photocredit || '')+'" data-w="'+(v.imgs[i].webformatWidth || 0)+'" data-h="'+(v.imgs[i].webformatHeight || 0)+'">'
                            +'<img src="'+(v.imgs[i].previewURL || v.imgs[i].url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                            +'<div class="dlimg">'
                                +'<img src="'+iconBase+'round-cloud_download-24px.svg">'
                                +'<div class="caption">'+v.title+'</div>'
                            +'</div>'
                            +'<div class="wrktitle"><img src="'+iconBase+'round-cloud_download-24px.svg"><br />'+(v.imgs[i].exists?'<b>downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                            +'</div>';
                    }
                    image_item += '<div class="item subitemid'+v.import_source_id+' hidden itemid'+v.import_source_id+' ender" data-w="165" data-h="1000">'
                        +'<img src="'+(v.previewURL || v.url_thumb)+'" style="display:none !important;">'
                        +'<div class="dlimg">'
                            +'<img src="'+iconBase+'baseline-arrow_back_ios-24px.svg" class="open">'
                        +'</div>'
                        +'</div>';
                }
                else {
                    var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                    image_item += '<div class="item upload'+(v.exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+(v.largeImageURL || v.url_full)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+(v.imageId || 0)+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.photocredit || '')+'" data-w="'+(v.webformatWidth || 0)+'" data-h="'+(v.webformatHeight || 0)+'">'
                        +'<img src="'+(v.previewURL || v.url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                        +'<div class="dlimg">'
                            +'<img src="'+iconBase+'round-cloud_download-24px.svg">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="'+iconBase+'round-cloud_download-24px.svg"><br />'+(v.exists?'<b>downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                        +'</div>';
                }

            });
            $('#wrklst_results', $container).html($('#wrklst_results', $container).html()+image_item);
            $('#show_animation', $container).remove();
            if (page < pages) {
                var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                $('#wrklst_results', $container).after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="'+iconBase+'baseline-autorenew-24px.svg" class="loading-rotator"></div>');
                $('.media-frame-content').first().scroll(scrollHandler);
            }
        }

        //trigger multi image display to show subimages
        $("#wrklst_results", this.$el).on('click', '.upload.multiimg', function() {
            var id = $(this).data('import_source_id');
            $( ".subitemid"+id ).each(function( index ) {
                $( this ).toggleClass( "hidden" );
            });
            $( ".itemid"+id ).each(function( index ) {
                $( this ).toggleClass( "open" );
            });
        });
        
        //trigger collapse when clicking the ender arrow
        $("#wrklst_results", this.$el).on('click', '.item.ender', function(e) {
            e.stopPropagation();
            var import_source_id = $(this).attr('class').match(/itemid(\d+)/)[1];
            $( ".subitemid"+import_source_id ).addClass( "hidden" );
            $( ".itemid"+import_source_id ).removeClass( "open" );
        });

        //trigger upload of images
        $("#wrklst_results", this.$el).on('click', '.upload:not(.doneuploading)', function() {
            if(!$(this).hasClass('uploading')&&!$(this).hasClass('doneuploading')&&!$(this).hasClass('multiimg'))
            {
                var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                $(this).addClass('uploading').find('.dlimg img').replaceWith('<img src="'+iconBase+'baseline-autorenew-24px.svg" class="loading-rotator" style="height:80px !important">');

                var that = $(this);
                var uploadData = {
                    image_url: $(this).data('url'),
                    image_caption: $(this).data('caption'),
                    title: $(this).data('title'),
                    invnr: $(this).data('invnr'),
                    artist: $(this).data('artist'),
                    import_source_id: $(this).data('import_source_id'),
                    image_id: $(this).data('image_id'),
                    import_inventory_id: $(this).data('import_inventory_id'),
                    search_query: search_query,
                    wpnonce: $(this).data('wpnonce')
                };
                
                if (typeof WrkLstAjax !== 'undefined') {
                    WrkLstAjax.uploadImage(uploadData, function(data){
                        that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="'+iconBase+'baseline-check-24px.svg" style="height:50px !important">');
                        var selection = frame.state().get('selection');
                        attachment = wp.media.attachment(data.id);
                        attachment.fetch();
                        selection.add( attachment ? [ attachment ] : [] );
                    });
                } else {
                    // Direct jQuery AJAX fallback
                    uploadData.action = 'wrklst_upload';
                    $.post(ajaxurl || '.', uploadData, function(response){
                        if (response.success) {
                            that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="'+iconBase+'baseline-check-24px.svg" style="height:50px !important">');
                            var selection = frame.state().get('selection');
                            attachment = wp.media.attachment(response.data.id);
                            attachment.fetch();
                            selection.add( attachment ? [ attachment ] : [] );
                        }
                    });
                }
            }
            return false;
        });
    }
});

return;

})(jQuery);