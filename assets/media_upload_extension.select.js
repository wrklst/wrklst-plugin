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
        this.$el.load( "/../wp-content/plugins/wrklst-plugin/templates/ajax_media_uploader_works.php", function() {

            //cookie control of forms
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

            //setup base variables for this method
            var per_page = 30,
                form = $('#wrklst_form'),
                hits,
                search_query = '',
                page = 1,
                work_status = false,
                api = false,
                account = false,
                last_call = '',
                wrklst_security_nonce = false,
                wrklst_url = 'https://app.wrklst.com';

            //get api credentials and nonce
            function get_api_cred(){
                $.post('.', {
                    wrklst_api_cred: "1",
                }, function(data){
                    api = data.wrklst_settings.api;
                    account = data.wrklst_settings.account;
                    wrklst_url = 'https://'+account+'.wrklst.com';
                    wrklst_security_nonce = data.wrklst_nonce;

                    //initiate inventory and first api call
                    get_inventories();
                });
            }
            get_api_cred();

            //trigger laod more when page is scrolled further down and there are more records to get
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
                if(!api) return false;
                search_query = $('#search_query', form).val();
                if ($('#filter_available', form).is(':checked')) work_status = 'available';
                $('#wrklst_results').html('');
                $('.media-frame-content').first().off('scroll', scrollHandler);
                request_api();
            });

            //handle filter chnages
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

            //get inventories from wrklst
            function get_inventories() {
                url_call = wrklst_url+'/ext/api/wordpress/inventories?token='+api;
                var req = new XMLHttpRequest();
                req.open('GET', url_call);
                req.onreadystatechange = function(){
                    if (this.status == 200 && this.readyState == 4) {
                        var data = JSON.parse(this.responseText);
                        $.each(data.inventories, function(k, v) {
                            $('#filter_inventory').append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                        });
                        if (getCookie('wrklst_filter_inventory'))
                            $('#filter_inventory', form).val(getCookie('wrklst_filter_inventory'));
                        form.submit();
                    }
                };
                req.send();
            }

            //get images for one page from wrklst
            function request_api(){
                //prevent double page loading
                url_call = wrklst_url+'/ext/api/wordpress/?token='+api
                    +'&work_status='+work_status
                    +'&per_page='+per_page
                    +'&page='+page
                    +'&inv_sec_id='+$('#filter_inventory', form).val()
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
                            $('#wrklst_results').html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                            $('#show_animation').remove();
                            return false;
                        }
                        check_existing(data);
                    }
                };
                req.send();
                return false;
            }

            //funnel wrklst image results through existing decorator, to mark works as already downloaded, if they have been
            function check_existing(wrklst_data) {
                $.post('.', {
                    wrklst_check_existing: "1",
                    wrklst_data: wrklst_data,
                    wpnonce: wrklst_security_nonce
                }, function(return_data){
                    render_results(return_data);
                });
            }

            //render image results into dom
            function render_results(data){
                hits = data['hits'];
                pages = Math.ceil(data.totalHits/per_page);
                var image_item = '';
                $.each(data.hits, function(k, v) {
                    var i=0;
                    if(v.multi_img) {
                        image_item += '<div class="item itemid'+v.import_source_id+' upload multiimg'+(v.exists===2?' exists':(v.exists?' existsp':''))+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
                            +'<img src="'+v.previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                            +'<div class="dlimg">'
                                +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-more_horiz-24px.svg" class="more">'
                                +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-arrow_forward_ios-24px.svg" class="open hide-img">'
                                +'<div class="caption">'+v.title+'</div>'
                            +'</div>'
                            +'<div class="wrktitle"><img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-more_horiz-24px.svg"><bR />'+(v.exists?'<b>'+(v.exists===2?'all':'partly')+' downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                            +'</div>';
                        for(i=0;i<v.imgs.length;i++) {
                            image_item += '<div class="subitem hidden subitemid'+v.import_source_id+' item upload'+(v.imgs[i].exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.imgs[i].largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imgs[i].id+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.imgs[i].photocredit+'" data-w="'+v.imgs[i].webformatWidth+'" data-h="'+v.imgs[i].webformatHeight+'">'
                                +'<img src="'+v.imgs[i].previewURL.replace('_150', '_340')+'" title="#'+v.inv_nr+'" alt="#'+v.inv_nr+'">'
                                +'<div class="dlimg">'
                                    +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg">'
                                    +'<div class="caption">'+v.title+'</div>'
                                +'</div>'
                                +'<div class="wrktitle"><img src="/../wp-content/plugins/wrklst-plugin/assets/img/round-cloud_download-24px.svg"><bR />'+(v.imgs[i].exists?'<b>downloaded</b><br />':'')+'#'+v.inv_nr+'</div>'
                                +'</div>';
                        }
                        image_item += '<div class="item subitemid'+v.import_source_id+' hidden itemid'+v.import_source_id+' ender" data-w="165" data-h="1000">'
                            +'<img src="'+v.previewURL.replace('_150', '_340')+'" style="display:none !important;">'
                            +'<div class="dlimg">'
                                +'<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-arrow_back_ios-24px.svg" class="open hide-img">'
                            +'</div>'
                            +'</div>';
                    }
                    else {
                        image_item += '<div class="item upload'+(v.exists?' exists':'')+'" data-title="'+v.title+'" data-wpnonce="'+v.wpnonce+'" data-url="'+v.largeImageURL+'" data-invnr="'+v.inv_nr+'" data-artist="'+v.name_artist+'" data-import_source_id="'+v.import_source_id+'" data-image_id="'+v.imageId+'" data-import_inventory_id="'+v.import_inventory_id+'" data-caption="'+v.caption+v.photocredit+'" data-w="'+v.webformatWidth+'" data-h="'+v.webformatHeight+'">'
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
                    $('.media-frame-content').first().scroll(scrollHandler);
                }

                setTimeout(function(){
                    $('.flex-images').flexImages({rowHeight: 260,listenContainer:'#wlworkcontainer'});
                }, 100);
            }

            //trigger multi image display to show subimages
            $("#wrklst_results").on('click', '.upload.multiimg', function() {
                $( ".subitemid"+$(this).data('import_source_id') ).each(function( index ) {
                    $( this ).toggleClass( "hidden" );
                });
                $( ".itemid"+$(this).data('import_source_id')+" .dlimg>img" ).each(function( index ) {
                    $( this ).toggleClass( "hide-img" );
                });
                $( ".itemid"+$(this).data('import_source_id') ).each(function( index ) {
                    $( this ).toggleClass( "open" );
                });

                setTimeout(function(){
                    $('.flex-images').flexImages({rowHeight: 260,listenContainer:'#wlworkcontainer'});
                }, 100);
            });

            //trigger upload of images
            $("#wrklst_results").on('click', '.upload:not(.doneuploading)', function() {
                if(!$(this).hasClass('uploading')&&!$(this).hasClass('doneuploading')&&!$(this).hasClass('multiimg'))
                {
                    $(this).addClass('uploading').find('.dlimg img').replaceWith('<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-autorenew-24px.svg" class="loading-rotator" style="height:80px !important">');

                    var that = $(this);
                    $.post('.', {
                        wrklst_upload: "1",
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
                    }, function(data){
                        that.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="/../wp-content/plugins/wrklst-plugin/assets/img/baseline-check-24px.svg" style="height:50px !important">');
                        var selection = frame.state().get('selection');
                        attachment = wp.media.attachment(data.id);
                        attachment.fetch();
                        selection.add( attachment ? [ attachment ] : [] );
                    });
                }
                return false;
            });
        });
    },
});

return;

})(jQuery);
