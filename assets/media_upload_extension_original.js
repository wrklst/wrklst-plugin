//based on https://wordpress.stackexchange.com/questions/130065/add-item-to-media-library-from-blob-or-dataurl
(function($){
if(typeof wp.media === "undefined") return false;
var media = wp.media,
    frame,
    l10n = media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;

var THUMB_SIZE = 500;
var UPLOAD_SIZE = 2500;
// Must match the imgproxy URL wrklst-app uses for overview thumbnails so previews
// hit the existing cache and imgproxy does not generate extra variants. wrklst-app
// builds overview thumbs as `rs:fit:500:0/plain/.../<path>@webp` via ImgproxyService.
var PREVIEW_FORMAT = 'webp';
// 1x1 transparent GIF, used when an item has no preview image so we don't emit
// <img src=""> (which makes the browser refetch the current URL → 404).
var BLANK_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
function imgproxyFormat() {
    return (typeof wrklst_image_config !== 'undefined' && wrklst_image_config.format) ? wrklst_image_config.format : 'jpg';
}
function imgproxyUrl(url, width, fmt) {
    if (!url) return url;
    var i = url.indexOf('/plain/');
    if (i === -1) return url.replace('_150', '_' + width);
    var rest = url.slice(i);
    rest = /@[a-zA-Z0-9]+$/.test(rest) ? rest.replace(/@[a-zA-Z0-9]+$/, '@' + fmt) : rest + '@' + fmt;
    return url.slice(0, i) + '/rs:fit:' + width + ':0' + rest;
}
function imgproxyPreview(url) {
    return url ? imgproxyUrl(url, THUMB_SIZE, PREVIEW_FORMAT) : BLANK_PIXEL;
}
function imgproxyThumb(url, width) {
    return imgproxyUrl(url, width, imgproxyFormat());
}

// override router creation
media.view.MediaFrame.Select.prototype.browseRouter = function( view ) {
    view.set({
        upload: {
            text:     l10n.uploadFilesTitle,
            priority: 20
        },
        wlwork: {
            text:     'WrkLst Inventory',
            priority: 30
        },
        wlexhibition: {
            text:     'WrkLst Exhibition',
            priority: 35
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
    this.on( 'content:create:wlexhibition', this.wlexhibitionContent, this );
    frame = this;
};
media.view.MediaFrame.Select.prototype.wlworkContent = function( content ){
    // generate test content
    var state = this.state();
    this.$el.removeClass('hide-toolbar');
    wlWork = new media.view.wlWork({});
    content.view = wlWork;
}

media.view.MediaFrame.Select.prototype.wlexhibitionContent = function( content ){
    var state = this.state();
    this.$el.removeClass('hide-toolbar');
    content.view = new media.view.wlExhibition({});
}


media.view.wlWork = media.view.WrkLstBase.extend({
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
        
        // Use cookie functions from base class

        var form = this.$('#wrklst_form');
        var $results = this.$('#wrklst_results');
        
        if (self.getCookie('wrklst_search_query'))
            $('#search_query', form).val(self.getCookie('wrklst_search_query'));

        $("input[id^='filter_']", form).each(function(){
            if (self.getCookie('wrklst_filter_'+this.id) && self.getCookie('wrklst_filter_'+this.id) != '0') this.checked = true;
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
            
        // Simple template helper for icon paths
        var getIconPath = function(iconName) {
            var base = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/');
            return base + 'assets/img/' + iconName;
        };

        //get api credentials and nonce
        function get_api_cred(){
            self.getApiCredentials(function(nonce) {
                wrklst_security_nonce = nonce;
                get_inventories();
            });
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
            $results.html('');
            $('.media-frame-content').first().off('scroll', scrollHandler);
            request_api();
        });

        //handle filter changes
        $("#filter_available", form).change(function() {
            self.setCookie('wrklst_filter_'+this.id, this.checked ? 1 : 0, 365);
            form.submit();
        });
        $("#search_query", form).keyup($.debounce(600, function(e) {
            self.setCookie('wrklst_search_query', $('#search_query', form).val(), 365);
            if(search_query!==$('#search_query', form).val())
                form.submit();
        }));
        $("#filter_inventory", form).change(function() {
            self.setCookie('wrklst_filter_inventory', $('#filter_inventory', form).val(), 365);
            form.submit();
        });

        //get inventories from wrklst
        function get_inventories() {
            self.getInventories(wrklst_security_nonce, function(data){
                if (data && data.inventories) {
                    $.each(data.inventories, function(k, v) {
                        $('#filter_inventory', form).append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                    });
                    if (self.getCookie('wrklst_filter_inventory'))
                        $('#filter_inventory', form).val(self.getCookie('wrklst_filter_inventory'));
                }
                form.submit();
            });
        }

        //get images for one page from wrklst
        function request_api() {
            //prevent double page loading
            url_call = work_status+'|'+per_page+'|'+page+'|'+$('#filter_inventory', form).val()+'|'+encodeURIComponent(search_query)+'|'+wrklst_security_nonce;
            if(last_call===url_call)
            {
                return false;
            }

            self.getInventoryItems({
                work_status: work_status,
                per_page: per_page,
                page: page,
                inv_sec_id: $('#filter_inventory', form).val(),
                search: encodeURIComponent(search_query),
                wpnonce: wrklst_security_nonce
            }, function(data){
                if (!(data.totalHits > 0)) {
                    $results.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                    $('#show_animation', $container).remove();
                    return false;
                }
                render_results(data);
                last_call = url_call;
            });
            return false;
        }

        //render image results into dom
        function render_results(data){
            hits = data['hits'];
            var pages = Math.ceil(data.totalHits/per_page);
            var image_item = '';
            
            // Use the base class rendering if available
            if (self.renderWorkItem) {
                $.each(data.hits, function(k, v) {
                    image_item += self.renderWorkItem(v);
                });
            } else {
                // Fallback to original rendering
                $.each(data.hits, function(k, v) {
                var i=0;
                if(v.multi_img=="1") {
                    
                    image_item += '<div class="item itemid'+v.import_source_id+' upload multiimg'+(v.exists===2?' exists':(v.exists?' existsp':''))+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+imgproxyThumb(v.largeImageURL || v.url_full, UPLOAD_SIZE)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+(v.imageId || 0)+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.photocredit || '')+'" data-w="'+(v.webformatWidth || 0)+'" data-h="'+(v.webformatHeight || 0)+'">'
                        +'<img src="'+imgproxyPreview(v.previewURL || v.url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                        +'<div class="dlimg">'
                            +'<img src="'+getIconPath('baseline-more_horiz-24px.svg')+'" class="more">'
                            +'<img src="'+getIconPath('baseline-arrow_forward_ios-24px.svg')+'" class="open">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="'+getIconPath('baseline-more_horiz-24px.svg')+'"><br />'+(v.exists?'<b>'+(v.exists===2?'all':'partly')+' downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                        +'</div>';
                    var iconBase = getIconPath('');
                    for(i=0;i<v.imgs.length;i++) {
                        image_item += '<div class="subitem hidden subitemid'+v.import_source_id+' item upload'+(v.imgs[i].exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+imgproxyThumb(v.imgs[i].largeImageURL || v.imgs[i].url_full, UPLOAD_SIZE)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imgs[i].id+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.imgs[i].photocredit || '')+'" data-w="'+(v.imgs[i].webformatWidth || 0)+'" data-h="'+(v.imgs[i].webformatHeight || 0)+'">'
                            +'<img src="'+imgproxyPreview(v.imgs[i].previewURL || v.imgs[i].url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                            +'<div class="dlimg">'
                                +'<img src="'+iconBase+'round-cloud_download-24px.svg">'
                                +'<div class="caption">'+v.title+'</div>'
                            +'</div>'
                            +'<div class="wrktitle"><img src="'+iconBase+'round-cloud_download-24px.svg"><br />'+(v.imgs[i].exists?'<b>downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                            +'</div>';
                    }
                    image_item += '<div class="item subitemid'+v.import_source_id+' hidden itemid'+v.import_source_id+' ender" data-w="165" data-h="1000">'
                        +'<img src="'+imgproxyPreview(v.previewURL || v.url_thumb)+'" style="display:none !important;">'
                        +'<div class="dlimg">'
                            +'<img src="'+iconBase+'baseline-arrow_back_ios-24px.svg" class="open">'
                        +'</div>'
                        +'</div>';
                }
                else {
                    var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                    image_item += '<div class="item upload'+(v.exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+imgproxyThumb(v.largeImageURL || v.url_full, UPLOAD_SIZE)+'" data-invnr="'+(v.inv_nr || v.invnr)+'" data-artist="'+(v.name_artist || v.artist)+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+(v.imageId || 0)+'" data-import_inventory_id="'+(v.import_inventory_id || v.inv_id)+'" data-caption="'+(v.caption || '')+(v.photocredit || '')+'" data-w="'+(v.webformatWidth || 0)+'" data-h="'+(v.webformatHeight || 0)+'">'
                        +'<img src="'+imgproxyPreview(v.previewURL || v.url_thumb)+'" title="#'+(v.inv_nr || v.invnr)+'" alt="#'+(v.inv_nr || v.invnr)+'">'
                        +'<div class="dlimg">'
                            +'<img src="'+iconBase+'round-cloud_download-24px.svg">'
                            +'<div class="caption">'+v.title+'</div>'
                        +'</div>'
                        +'<div class="wrktitle"><img src="'+iconBase+'round-cloud_download-24px.svg"><br />'+(v.exists?'<b>downloaded</b><br />':'')+'#'+(v.inv_nr || v.invnr)+'</div>'
                        +'</div>';
                }

            });
            }
            $results.html($results.html()+image_item);
            $('#show_animation', $container).remove();
            if (page < pages) {
                var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';
                $results.after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="'+iconBase+'baseline-autorenew-24px.svg" class="loading-rotator"></div>');
                $('.media-frame-content').first().scroll(scrollHandler);
            }
        }

        //trigger multi image display to show subimages
        $results.on('click', '.upload.multiimg', function() {
            var id = $(this).data('import_source_id');
            $( ".subitemid"+id ).each(function( index ) {
                $( this ).toggleClass( "hidden" );
            });
            $( ".itemid"+id ).each(function( index ) {
                $( this ).toggleClass( "open" );
            });
        });
        
        //trigger collapse when clicking the ender arrow
        $results.on('click', '.item.ender', function(e) {
            e.stopPropagation();
            var import_source_id = $(this).attr('class').match(/itemid(\d+)/)[1];
            $( ".subitemid"+import_source_id ).addClass( "hidden" );
            $( ".itemid"+import_source_id ).removeClass( "open" );
        });

        //trigger upload of images
        $results.on('click', '.upload:not(.doneuploading)', function() {
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
                
                self.uploadImage(uploadData, function(data){
                    that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="'+iconBase+'baseline-check-24px.svg" style="height:50px !important">');
                    var selection = frame.state().get('selection');
                    attachment = wp.media.attachment(data.id);
                    attachment.fetch();
                    selection.add( attachment ? [ attachment ] : [] );
                });
            }
            return false;
        });
    }
});

media.view.wlExhibition = media.view.WrkLstBase.extend({
    tagName:   'div',
    className: 'wl-exhibition',
    id: 'wlexhibitioncontainer',
    initialize: function() {
        _.defaults(this.options, {});
        this.buildInterface();
    },
    buildInterface: function() {
        var self = this;

        if (!$('#wrklst-media-picker-styles').length) {
            // Reuse the work tab's stylesheet by triggering wlWork's build implicitly is not possible,
            // so duplicate the minimum needed styles for the exhibition picker grid.
            $('head').append('<style id="wrklst-media-picker-styles-exh">' +
                '.wlexh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:12px}' +
                '.wlexh-card{background:#fff;border:1px solid #ddd;border-radius:4px;overflow:hidden;cursor:pointer;display:flex;flex-direction:column}' +
                '.wlexh-card:hover{border-color:#888;box-shadow:0 2px 6px rgba(0,0,0,.08)}' +
                '.wlexh-thumb{aspect-ratio:4/3;background:#f3f3f3;display:flex;align-items:center;justify-content:center;overflow:hidden}' +
                '.wlexh-thumb img{width:100%;height:100%;object-fit:cover}' +
                '.wlexh-thumb-empty{color:#bbb;font-size:13px}' +
                '.wlexh-meta{padding:8px 10px}' +
                '.wlexh-artists{font-size:11px;color:#666;text-transform:uppercase;letter-spacing:.03em}' +
                '.wlexh-title{font-size:14px;font-weight:600;line-height:1.3;margin-top:2px}' +
                '.wlexh-sub,.wlexh-counts{font-size:11px;color:#777;margin-top:3px}' +
                '.wlexh-back{display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#555;font-size:13px;margin:8px 0}' +
                '.wlexh-back:hover{color:#000}' +
                '.wlexh-pr-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}' +
                '.wlexh-pr-btn{display:inline-flex;align-items:center;gap:8px;cursor:pointer}' +
                '.wlexh-pr-btn .wlexh-pr-label{font-weight:600}' +
                '.wlexh-pr-btn .wlexh-pr-hint{color:#666;font-size:11px;text-transform:uppercase;letter-spacing:.04em}' +
                '.wlexh-bulk-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}' +
                '.wlexh-bulk-btn .wlexh-bulk-count{color:#666;margin-left:4px;font-variant-numeric:tabular-nums}' +
                '.wlexh-bulk-btn.button-primary .wlexh-bulk-count{color:rgba(255,255,255,.85)}' +
                '.wrklst-confirmed-toggle{display:inline-flex;align-items:center;gap:6px;margin:6px 0 4px;font-size:13px;cursor:pointer;user-select:none}' +
                '.wrklst-confirmed-toggle input{margin:0 4px 0 0}' +
                '.wrklst-confirmed-toggle-counts{color:#888;font-variant-numeric:tabular-nums}' +
                '.wrklst-confirmed-badge{display:inline-block;padding:1px 6px;margin-bottom:4px;background:#2c8a3a;color:#fff;border-radius:8px;font-size:10px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}' +
                '.wrklst-show-confirmed-only .item.unconfirmed{display:none !important}' +
                '.hidden{display:none !important}' +
                '</style>');
        }

        var html = ''
            + '<div style="padding:10px 10px 10px 10px">'
            +   '<div class="wlexh-picker">'
            +     '<form class="wlexh-form" style="margin:0">'
            +       '<div style="line-height:1.5;margin:1em 0;max-width:500px;position:relative">'
            +         '<input class="wlexh-search" type="text" value="" style="width:100%;padding:7px 32px 7px 9px" placeholder="Search exhibitions">'
            +         '<button type="submit" style="background:#fff;border:0;cursor:pointer;position:absolute;right:0px;top:1px;outline:0" title="Search">🔍</button>'
            +       '</div>'
            +     '</form>'
            +     '<div class="wlexh-grid wlexh-results"></div>'
            +   '</div>'
            +   '<div class="wlexh-detail hidden">'
            +     '<a href="#" class="wlexh-back">← Back to exhibitions</a>'
            +     '<div class="wlexh-header" style="margin:1em 0"></div>'
            +     '<div class="wlexh-items flex-images" style="margin-top:15px;"></div>'
            +   '</div>'
            + '</div>';

        this.$el.html(html);

        var $picker = this.$('.wlexh-picker');
        var $detail = this.$('.wlexh-detail');
        var $form = this.$('.wlexh-form');
        var $search = this.$('.wlexh-search');
        var $results = this.$('.wlexh-results');
        var $items = this.$('.wlexh-items');
        var $header = this.$('.wlexh-header');
        var $back = this.$('.wlexh-back');

        var perPage = 30,
            page = 1,
            searchQuery = '',
            lastCall = '',
            wrklstSecurityNonce = false,
            currentExhibitionId = null,
            pressReleases = {};

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function fallbackCopy(t) {
            var ta = document.createElement('textarea');
            ta.value = t;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            return ok;
        }

        function countImportable(hits, opts) {
            var confirmedOnly = !!(opts && opts.confirmedOnly);
            var installs = 0;
            var artworks = 0;
            var artworksFirst = 0;
            $.each(hits, function(k, hit) {
                var isInstall = hit.item_kind === 'installimage' || (typeof hit.import_source_id === 'string' && hit.import_source_id.indexOf('exh-') === 0);
                if (!isInstall && confirmedOnly && hit.confirmed !== true) return;
                if (hit.multi_img) {
                    if (isInstall) return;
                    $.each(hit.imgs || [], function(i, img) {
                        if (!img.exists) artworks++;
                    });
                    var firstImg = (hit.imgs || [])[0];
                    if (firstImg && !firstImg.exists) artworksFirst++;
                } else {
                    if (hit.exists) return;
                    if (isInstall) {
                        installs++;
                    } else {
                        artworks++;
                        artworksFirst++;
                    }
                }
            });
            return { installs: installs, artworks: artworks, artworksFirst: artworksFirst };
        }

        var currentExh = null;
        var currentHits = [];
        var currentPressReleases = [];
        var confirmedOnly = false;

        function renderDetailHeader() {
            var bits = [];
            var exh = currentExh || {};
            if (exh.artists && exh.artists.length) bits.push('<div style="color:#666;font-size:13px">' + exh.artists.join(', ') + '</div>');
            bits.push('<h2 style="margin:0">' + (exh.title || exh.display || '') + '</h2>');
            var sub = [];
            if (exh.date_display) sub.push(exh.date_display);
            if (exh.venues && exh.venues.length) sub.push(exh.venues.join(', '));
            if (sub.length) bits.push('<div style="color:#666;font-size:13px;margin-top:4px">' + sub.join(' · ') + '</div>');

            pressReleases = {};
            if (currentPressReleases.length) {
                var prRow = '<div class="wlexh-pr-row">';
                $.each(currentPressReleases, function(k, pr) {
                    pressReleases[pr.id] = pr.text || '';
                    var label = pr.title && pr.title.length ? pr.title : 'Press Release';
                    prRow += '<button type="button" class="button wlexh-pr-btn" data-pr-id="' + pr.id + '">' +
                                '<span class="wlexh-pr-label">' + escapeHtml(label) + '</span>' +
                                '<span class="wlexh-pr-hint">copy HTML</span>' +
                             '</button>';
                });
                prRow += '</div>';
                bits.push(prRow);
            }

            var totalConfirmed = currentHits.filter(function(h) { return h.confirmed === true; }).length;
            var totalUnconfirmed = currentHits.filter(function(h) { return h.confirmed === false; }).length;
            if (totalConfirmed + totalUnconfirmed > 0) {
                bits.push(
                    '<label class="wrklst-confirmed-toggle">' +
                        '<input type="checkbox" class="wlexh-confirmed-only"' + (confirmedOnly ? ' checked' : '') + '> ' +
                        'Confirmed only ' +
                        '<span class="wrklst-confirmed-toggle-counts">(' + totalConfirmed + ' confirmed / ' + totalUnconfirmed + ' unconfirmed)</span>' +
                    '</label>'
                );
            }

            var counts = countImportable(currentHits, { confirmedOnly: confirmedOnly });
            if (counts.installs > 0 || counts.artworks > 0) {
                var bulkRow = '<div class="wlexh-bulk-row">';
                if (counts.installs > 0) {
                    bulkRow += '<button type="button" class="button wlexh-bulk-btn" data-scope="installs">Download all installation views <span class="wlexh-bulk-count">(' + counts.installs + ')</span></button>';
                }
                if (counts.artworksFirst > 0) {
                    bulkRow += '<button type="button" class="button wlexh-bulk-btn" data-scope="artworks-first">Download first image of each artwork <span class="wlexh-bulk-count">(' + counts.artworksFirst + ')</span></button>';
                }
                if (counts.artworks > 0) {
                    bulkRow += '<button type="button" class="button wlexh-bulk-btn" data-scope="artworks">Download all artworks <span class="wlexh-bulk-count">(' + counts.artworks + ')</span></button>';
                }
                if (counts.installs > 0 && counts.artworks > 0) {
                    bulkRow += '<button type="button" class="button button-primary wlexh-bulk-btn" data-scope="all">Download all <span class="wlexh-bulk-count">(' + (counts.installs + counts.artworks) + ')</span></button>';
                }
                bulkRow += '</div>';
                bits.push(bulkRow);
            }

            $header.html(bits.join(''));
        }

        if (self.getCookie('wrklst_exh_search_query')) {
            $search.val(self.getCookie('wrklst_exh_search_query'));
        }

        var iconBase = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/') + 'assets/img/';

        var scrollHandler = function() {
            if ($('.media-frame-content').first().scrollTop() + $('.media-frame-content').first().height() > $('.wl-exhibition').first().height() - 400) {
                $('.media-frame-content').first().off('scroll', scrollHandler);
                page = page + 1;
                requestExhibitions();
            }
        };

        $form.submit(function(e) {
            e.preventDefault();
            page = 1;
            searchQuery = $search.val();
            $results.html('');
            $('.media-frame-content').first().off('scroll', scrollHandler);
            requestExhibitions();
        });

        $search.keyup($.debounce(600, function() {
            self.setCookie('wrklst_exh_search_query', $search.val(), 365);
            if (searchQuery !== $search.val()) $form.submit();
        }));

        function requestExhibitions() {
            var urlCall = perPage + '|' + page + '|' + encodeURIComponent(searchQuery) + '|' + wrklstSecurityNonce;
            if (lastCall === urlCall) return false;

            WrkLstAjax.getExhibitions({
                per_page: perPage,
                page: page,
                search: searchQuery,
                wpnonce: wrklstSecurityNonce
            }, function(data) {
                if (!data || !(data.totalHits > 0)) {
                    $results.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No exhibitions found ——</div>');
                    $('#wlexh_show_animation').remove();
                    return;
                }
                renderExhibitions(data);
                lastCall = urlCall;
            });
            return false;
        }

        function renderExhibitions(data) {
            var pages = Math.ceil(data.totalHits / perPage);
            var html = '';
            $.each(data.hits, function(k, exh) {
                var meta = [];
                if (exh.date_display) meta.push(exh.date_display);
                if (exh.venues && exh.venues.length) meta.push(exh.venues.join(', '));
                var counts = [];
                if (exh.installimage_count) counts.push(exh.installimage_count + ' install');
                if (exh.artwork_count) {
                    var artworkLabel = exh.artwork_count + ' artwork' + (exh.artwork_count === 1 ? '' : 's');
                    if (typeof exh.artwork_count_confirmed === 'number' && exh.artwork_count_confirmed > 0 && exh.artwork_count_confirmed < exh.artwork_count) {
                        artworkLabel += ' (' + exh.artwork_count_confirmed + ' confirmed)';
                    }
                    counts.push(artworkLabel);
                }
                if (exh.pressrelease_count) counts.push(exh.pressrelease_count + ' press release' + (exh.pressrelease_count === 1 ? '' : 's'));
                var thumb = exh.thumbURL ? self.imgproxyPreview(exh.thumbURL) : '';
                var artistsLine = exh.artists && exh.artists.length ? exh.artists.join(', ') : '';

                html += '<div class="wlexh-card" data-exhibition-id="' + exh.id + '">'
                     +   '<div class="wlexh-thumb">'
                     +     (thumb ? '<img src="' + thumb + '" alt="">' : '<div class="wlexh-thumb-empty">No image</div>')
                     +   '</div>'
                     +   '<div class="wlexh-meta">'
                     +     (artistsLine ? '<div class="wlexh-artists">' + artistsLine + '</div>' : '')
                     +     '<div class="wlexh-title">' + (exh.title || exh.display || '') + '</div>'
                     +     (meta.length ? '<div class="wlexh-sub">' + meta.join(' · ') + '</div>' : '')
                     +     (counts.length ? '<div class="wlexh-counts">' + counts.join(' · ') + '</div>' : '')
                     +   '</div>'
                     + '</div>';
            });
            $results.html($results.html() + html);
            $('#wlexh_show_animation').remove();
            if (page < pages) {
                $results.after('<div id="wlexh_show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="' + iconBase + 'baseline-autorenew-24px.svg" class="loading-rotator"></div>');
                $('.media-frame-content').first().scroll(scrollHandler);
            }
        }

        $results.on('click', '.wlexh-card', function() {
            var id = $(this).data('exhibition-id');
            if (id) openExhibition(id);
        });

        $back.on('click', function(e) {
            e.preventDefault();
            closeExhibition();
        });

        function openExhibition(id) {
            currentExhibitionId = id;
            $picker.addClass('hidden');
            $detail.removeClass('hidden');
            $header.html('<div style="color:#888">Loading…</div>');
            $items.html('');

            WrkLstAjax.getExhibitionItems({
                exhibition_id: id,
                wpnonce: wrklstSecurityNonce
            }, function(data) {
                renderExhibitionDetail(data);
            });
        }

        function closeExhibition() {
            currentExhibitionId = null;
            $detail.addClass('hidden');
            $picker.removeClass('hidden');
            $items.html('');
            $header.html('');
        }

        function renderExhibitionDetail(data) {
            // Hide artworks with no uploaded image — bulk-download counts and the
            // rendered grid should both ignore placeholder inventory rows.
            if (data.hits && data.hits.length && self.hasImportableImage) {
                data.hits = data.hits.filter(function(hit) {
                    return self.hasImportableImage(hit);
                });
            }

            currentExh = data.exhibition || {};
            currentHits = data.hits || [];
            currentPressReleases = data.pressreleases || [];

            // Default to "confirmed only" when at least one confirmed artwork
            // exists — usually the unconfirmed roster is still being curated.
            confirmedOnly = currentHits.some(function(h) { return h.confirmed === true; });

            renderDetailHeader();
            $items.toggleClass('wrklst-show-confirmed-only', !!confirmedOnly);

            if (!currentHits.length) {
                $items.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No images or artworks ——</div>');
                return;
            }
            var html = '';
            $.each(currentHits, function(k, hit) {
                html += self.renderWorkItem(hit);
            });
            $items.html(html);
        }

        $header.on('change', '.wlexh-confirmed-only', function() {
            confirmedOnly = $(this).is(':checked');
            $items.toggleClass('wrklst-show-confirmed-only', !!confirmedOnly);
            renderDetailHeader();
        });

        $header.on('click', '.wlexh-bulk-btn', function(e) {
            e.preventDefault();
            var scope = $(this).data('scope');
            var $candidates;

            // Treat unconfirmed inventory items as out of scope when the filter
            // is on. Install images never get the unconfirmed class.
            var applyConfirmedFilter = function($set) {
                return confirmedOnly ? $set.not('.unconfirmed') : $set;
            };

            if (scope === 'artworks-first') {
                var seen = {};
                var picks = [];
                applyConfirmedFilter($items.find('.item.upload')
                    .not('.multiimg')
                    .not('.uploading')
                    .not('.doneuploading')
                    .not('.ender'))
                    .each(function() {
                        var importId = String($(this).data('import_source_id') || '');
                        if (importId.indexOf('exh-') === 0) return;
                        if (seen[importId]) return;
                        seen[importId] = true;
                        if (!$(this).hasClass('exists')) picks.push(this);
                    });
                $candidates = $(picks);
            } else {
                $candidates = applyConfirmedFilter($items.find('.item.upload')
                    .not('.multiimg')
                    .not('.exists')
                    .not('.uploading')
                    .not('.doneuploading')
                    .not('.ender'))
                    .filter(function() {
                        var importId = String($(this).data('import_source_id') || '');
                        var isInstall = importId.indexOf('exh-') === 0;
                        if (scope === 'installs') return isInstall;
                        if (scope === 'artworks') return !isInstall;
                        return true;
                    });
            }

            var i = 0;
            $candidates.each(function() {
                var $el = $(this);
                setTimeout(function() { $el.trigger('click'); }, i * 120);
                i++;
            });
        });

        $header.on('click', '.wlexh-pr-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var prId = $btn.data('pr-id');
            var text = pressReleases[prId];
            if (typeof text !== 'string') return;

            var $hint = $btn.find('.wlexh-pr-hint');
            var revert = function(msg) {
                $hint.text(msg);
                setTimeout(function() { $hint.text('copy HTML'); }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    revert('copied!');
                }, function() {
                    revert(fallbackCopy(text) ? 'copied!' : 'copy failed');
                });
            } else {
                revert(fallbackCopy(text) ? 'copied!' : 'copy failed');
            }
        });

        $items.on('click', '.upload.multiimg', function() {
            var id = $(this).data('import_source_id');
            $('.subitemid' + id).each(function() { $(this).toggleClass('hidden'); });
            $('.itemid' + id).each(function() { $(this).toggleClass('open'); });
        });

        $items.on('click', '.item.ender', function(e) {
            e.stopPropagation();
            var importSourceId = $(this).data('import_source_id');
            $('.subitemid' + importSourceId).addClass('hidden');
            $('.itemid' + importSourceId).removeClass('open');
        });

        $items.on('click', '.upload:not(.doneuploading)', function() {
            if (!$(this).hasClass('uploading') && !$(this).hasClass('doneuploading') && !$(this).hasClass('multiimg')) {
                $(this).addClass('uploading').find('.dlimg img').replaceWith('<img src="' + iconBase + 'baseline-autorenew-24px.svg" class="loading-rotator" style="height:80px !important">');

                var that = $(this);
                var uploadData = {
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
                    search_query: searchQuery,
                    wpnonce: $(this).data('wpnonce')
                };

                self.uploadImage(uploadData, function(data) {
                    that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="' + iconBase + 'baseline-check-24px.svg" style="height:50px !important">');
                    var selection = frame.state().get('selection');
                    var attachment = wp.media.attachment(data.id);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                });
            }
            return false;
        });

        // Kick off the initial fetch only after every handler above is bound.
        // self.getApiCredentials's callback can fire synchronously when
        // wrklst_ajax.nonce is already localized, so doing this earlier would
        // call $form.submit() before the submit handler exists and silently
        // skip the first request — the picker would only populate once the
        // user typed into the search box.
        self.getApiCredentials(function(nonce) {
            wrklstSecurityNonce = nonce;
            $form.submit();
        });
    }
});

return;

})(jQuery);