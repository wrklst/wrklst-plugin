/**
 * WrkLst Exhibitions Page
 * Two-step UX: pick an exhibition, then see install images + artworks
 * rendered by the shared WrkLstBase work renderer.
 */
(function($) {
    'use strict';

    if (typeof wp.media === 'undefined' || typeof wp.media.view.WrkLstBase === 'undefined') {
        $(document).ready(function() {
            console.warn('WrkLst base class not available, exhibitions page cannot initialize');
        });
        return;
    }

    wp.media.view.WrkLstExhibitions = wp.media.view.WrkLstBase.extend({
        el: '#wrklst-exhibitions-container',

        initialize: function() {
            this.setupView();
        },

        setupView: function() {
            var self = this;

            this.$picker = $('#wrklst_exhibition_picker');
            this.$detail = $('#wrklst_exhibition_detail');
            this.$pickerForm = $('#wrklst_exh_form');
            this.$pickerSearch = $('#exh_search_query');
            this.$pickerResults = $('#wrklst_exh_results');
            this.$detailHeader = $('#wrklst_exh_header');
            this.$detailResults = $('#wrklst_results');
            this.$backButton = $('#wrklst_exh_back');

            this.perPage = 30;
            this.page = 1;
            this.searchQuery = '';
            this.lastCall = '';
            this.wrklstSecurityNonce = false;
            this.currentExhibitionId = null;

            if (this.getCookie('wrklst_exh_search_query')) {
                this.$pickerSearch.val(this.getCookie('wrklst_exh_search_query'));
            }

            this.bindEvents();

            this.getApiCredentials(function(nonce) {
                self.wrklstSecurityNonce = nonce;
                self.$pickerForm.submit();
            });
        },

        bindEvents: function() {
            var self = this;

            this.$pickerForm.on('submit', function(e) {
                e.preventDefault();
                self.page = 1;
                self.searchQuery = self.$pickerSearch.val();
                self.$pickerResults.html('');
                $(window).off('scroll', self.scrollHandler);
                self.requestExhibitions();
            });

            this.$pickerSearch.on('keyup', $.debounce(600, function() {
                self.setCookie('wrklst_exh_search_query', self.$pickerSearch.val(), 365);
                if (self.searchQuery !== self.$pickerSearch.val()) {
                    self.$pickerForm.submit();
                }
            }));

            this.$pickerResults.on('click', '.wrklst-exh-card', function(e) {
                e.preventDefault();
                var id = $(this).data('exhibition-id');
                if (id) {
                    self.openExhibition(id);
                }
            });

            this.$backButton.on('click', function(e) {
                e.preventDefault();
                self.closeExhibition();
            });

            this.$detailResults.on('click', '.upload.multiimg', function() {
                var id = $(this).data('import_source_id');
                $('.subitemid' + id).each(function() { $(this).toggleClass('hidden'); });
                $('.itemid' + id).each(function() { $(this).toggleClass('open'); });
            });

            this.$detailResults.on('click', '.item.ender', function(e) {
                e.stopPropagation();
                var importSourceId = $(this).data('import_source_id');
                $('.subitemid' + importSourceId).addClass('hidden');
                $('.itemid' + importSourceId).removeClass('open');
            });

            this.$detailResults.on('click', '.upload:not(.doneuploading)', function() {
                if (!$(this).hasClass('uploading') && !$(this).hasClass('doneuploading') && !$(this).hasClass('multiimg')) {
                    var $this = $(this);
                    $this.addClass('uploading').find('.dlimg img').replaceWith('<img src="' + self.getIconPath('baseline-autorenew-24px.svg') + '" class="loading-rotator" style="height:80px !important">');

                    var uploadData = {
                        image_url: $this.data('url'),
                        image_caption: $this.data('caption'),
                        image_description: $this.data('description'),
                        image_alt: $this.data('alt'),
                        title: $this.data('title'),
                        invnr: $this.data('invnr'),
                        artist: $this.data('artist'),
                        import_source_id: $this.data('import_source_id'),
                        image_id: $this.data('image_id'),
                        import_inventory_id: $this.data('import_inventory_id'),
                        search_query: self.searchQuery,
                        wpnonce: wrklst_ajax.nonce || self.wrklstSecurityNonce
                    };

                    self.uploadImage(uploadData, function() {
                        $this.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="' + self.getIconPath('baseline-check-24px.svg') + '" style="height:50px !important">');
                    });
                }
                return false;
            });
        },

        requestExhibitions: function() {
            var self = this;

            var urlCall = this.perPage + '|' + this.page + '|' + encodeURIComponent(this.searchQuery) + '|' + this.wrklstSecurityNonce;
            if (this.lastCall === urlCall) return false;

            if (typeof WrkLstAjax === 'undefined') return false;
            WrkLstAjax.getExhibitions({
                per_page: this.perPage,
                page: this.page,
                search: this.searchQuery,
                wpnonce: this.wrklstSecurityNonce
            }, function(data) {
                if (!data || !(data.totalHits > 0)) {
                    self.$pickerResults.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No exhibitions found ——</div>');
                    $('#show_animation').remove();
                    return;
                }
                self.renderExhibitions(data);
                self.lastCall = urlCall;
            });

            return false;
        },

        renderExhibitions: function(data) {
            var self = this;
            var pages = Math.ceil(data.totalHits / this.perPage);
            var html = '';

            $.each(data.hits, function(k, exh) {
                var meta = [];
                if (exh.date_display) meta.push(exh.date_display);
                if (exh.venues && exh.venues.length) meta.push(exh.venues.join(', '));
                var counts = [];
                if (exh.installimage_count) counts.push(exh.installimage_count + ' install image' + (exh.installimage_count === 1 ? '' : 's'));
                if (exh.artwork_count) counts.push(exh.artwork_count + ' artwork' + (exh.artwork_count === 1 ? '' : 's'));

                var thumb = exh.thumbURL ? self.imgproxyThumb(exh.thumbURL, self.THUMB_SIZE) : '';
                var artistsLine = exh.artists && exh.artists.length ? exh.artists.join(', ') : '';

                html += '<a href="#" class="wrklst-exh-card" data-exhibition-id="' + exh.id + '">' +
                            '<div class="wrklst-exh-thumb">' +
                                (thumb ? '<img src="' + thumb + '" alt="">' : '<div class="wrklst-exh-thumb-empty">No image</div>') +
                            '</div>' +
                            '<div class="wrklst-exh-meta">' +
                                (artistsLine ? '<div class="wrklst-exh-artists">' + artistsLine + '</div>' : '') +
                                '<div class="wrklst-exh-title">' + (exh.title || exh.display || '') + '</div>' +
                                (meta.length ? '<div class="wrklst-exh-sub">' + meta.join(' · ') + '</div>' : '') +
                                (counts.length ? '<div class="wrklst-exh-counts">' + counts.join(' · ') + '</div>' : '') +
                            '</div>' +
                        '</a>';
            });

            this.$pickerResults.html(this.$pickerResults.html() + html);
            $('#show_animation').remove();

            if (this.page < pages) {
                this.$pickerResults.after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="' +
                    this.getIconPath('baseline-autorenew-24px.svg') + '" class="loading-rotator"></div>');
                $(window).scroll(this.scrollHandler.bind(this));
            }
        },

        scrollHandler: function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 400) {
                $(window).off('scroll', this.scrollHandler);
                this.page = this.page + 1;
                this.requestExhibitions();
            }
        },

        openExhibition: function(id) {
            var self = this;
            this.currentExhibitionId = id;
            this.$picker.addClass('hidden');
            this.$detail.removeClass('hidden');
            this.$detailHeader.html('<div style="color:#888">Loading…</div>');
            this.$detailResults.html('');

            WrkLstAjax.getExhibitionItems({
                exhibition_id: id,
                wpnonce: this.wrklstSecurityNonce
            }, function(data) {
                self.renderExhibitionDetail(data);
            });
        },

        closeExhibition: function() {
            this.currentExhibitionId = null;
            this.$detail.addClass('hidden');
            this.$picker.removeClass('hidden');
            this.$detailResults.html('');
            this.$detailHeader.html('');
        },

        renderExhibitionDetail: function(data) {
            var self = this;
            var exh = data.exhibition || {};
            var headerBits = [];
            if (exh.artists && exh.artists.length) headerBits.push('<div style="color:#666;font-size:13px">' + exh.artists.join(', ') + '</div>');
            headerBits.push('<h2 style="margin:0">' + (exh.title || exh.display || '') + '</h2>');
            var sub = [];
            if (exh.date_display) sub.push(exh.date_display);
            if (exh.venues && exh.venues.length) sub.push(exh.venues.join(', '));
            if (sub.length) headerBits.push('<div style="color:#666;font-size:13px;margin-top:4px">' + sub.join(' · ') + '</div>');
            this.$detailHeader.html(headerBits.join(''));

            if (!data.hits || !data.hits.length) {
                this.$detailResults.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No images or artworks ——</div>');
                return;
            }

            var html = '';
            $.each(data.hits, function(k, hit) {
                html += self.renderWorkItem(hit);
            });
            this.$detailResults.html(html);
        }
    });

    $(document).ready(function() {
        if ($('#wrklst-exhibitions-container').length) {
            new wp.media.view.WrkLstExhibitions();
        }
    });

})(jQuery);
