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
    initialize: function() {
        _.defaults( this.options, {});
        var self = this;
        this.$el.load( "/../wp-content/plugins/wrklst-plugin/templates/ajax_media_uploader_works.php", function() {
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
