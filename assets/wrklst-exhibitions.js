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
            this.pressReleases = {};

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

            this.$detailHeader.on('click', '.wrklst-pr-btn', function(e) {
                e.preventDefault();
                self.copyPressRelease($(this));
            });

            this.$detailHeader.on('click', '.wrklst-bulk-btn', function(e) {
                e.preventDefault();
                self.bulkDownload($(this).data('scope'));
            });

            this.$detailHeader.on('change', '.wrklst-confirmed-only', function() {
                self.confirmedOnly = $(this).is(':checked');
                self.applyConfirmedFilter();
                self.renderDetailHeader();
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

            // Hide artworks with no uploaded image — the bulk-download counts and
            // the rendered grid should both ignore placeholder inventory rows.
            if (data.hits && data.hits.length) {
                data.hits = data.hits.filter(function(hit) {
                    return self.hasImportableImage(hit);
                });
            }

            this._currentExhibition = exh;
            this._currentHits = data.hits || [];
            this._currentPressReleases = data.pressreleases || [];

            // Default to "confirmed only" when at least one confirmed artwork exists,
            // since the unconfirmed roster is usually still being curated.
            var hasConfirmed = this._currentHits.some(function(h) { return h.confirmed === true; });
            this.confirmedOnly = hasConfirmed;

            this.renderDetailHeader();
            this.renderDetailHits();
            this.applyConfirmedFilter();
        },

        renderDetailHeader: function() {
            var self = this;
            var exh = this._currentExhibition || {};
            var headerBits = [];
            if (exh.artists && exh.artists.length) headerBits.push('<div style="color:#666;font-size:13px">' + exh.artists.join(', ') + '</div>');
            headerBits.push('<h2 style="margin:0">' + (exh.title || exh.display || '') + '</h2>');
            var sub = [];
            if (exh.date_display) sub.push(exh.date_display);
            if (exh.venues && exh.venues.length) sub.push(exh.venues.join(', '));
            if (sub.length) headerBits.push('<div style="color:#666;font-size:13px;margin-top:4px">' + sub.join(' · ') + '</div>');

            this.pressReleases = {};
            if (this._currentPressReleases.length) {
                var prButtons = '<div class="wrklst-pr-row">';
                $.each(this._currentPressReleases, function(k, pr) {
                    self.pressReleases[pr.id] = pr.text || '';
                    var label = pr.title && pr.title.length ? pr.title : 'Press Release';
                    prButtons += '<button type="button" class="button wrklst-pr-btn" data-pr-id="' + pr.id + '">' +
                                    '<span class="wrklst-pr-label">' + self.escapeHtml(label) + '</span>' +
                                    '<span class="wrklst-pr-hint">copy HTML</span>' +
                                 '</button>';
                });
                prButtons += '</div>';
                headerBits.push(prButtons);
            }

            var totalConfirmed = this._currentHits.filter(function(h) { return h.confirmed === true; }).length;
            var totalUnconfirmed = this._currentHits.filter(function(h) { return h.confirmed === false; }).length;
            if (totalConfirmed + totalUnconfirmed > 0) {
                headerBits.push(
                    '<label class="wrklst-confirmed-toggle">' +
                        '<input type="checkbox" class="wrklst-confirmed-only"' + (this.confirmedOnly ? ' checked' : '') + '> ' +
                        'Confirmed only ' +
                        '<span class="wrklst-confirmed-toggle-counts">(' + totalConfirmed + ' confirmed / ' + totalUnconfirmed + ' unconfirmed)</span>' +
                    '</label>'
                );
            }

            var counts = self.countImportable(this._currentHits, { confirmedOnly: this.confirmedOnly });
            if (counts.installs > 0 || counts.artworks > 0) {
                var bulkRow = '<div class="wrklst-bulk-row">';
                if (counts.installs > 0) {
                    bulkRow += '<button type="button" class="button wrklst-bulk-btn" data-scope="installs">Download all installation views <span class="wrklst-bulk-count">(' + counts.installs + ')</span></button>';
                }
                if (counts.artworksFirst > 0) {
                    bulkRow += '<button type="button" class="button wrklst-bulk-btn" data-scope="artworks-first">Download first image of each artwork <span class="wrklst-bulk-count">(' + counts.artworksFirst + ')</span></button>';
                }
                if (counts.artworks > 0) {
                    bulkRow += '<button type="button" class="button wrklst-bulk-btn" data-scope="artworks">Download all artworks <span class="wrklst-bulk-count">(' + counts.artworks + ')</span></button>';
                }
                if (counts.installs > 0 && counts.artworks > 0) {
                    bulkRow += '<button type="button" class="button button-primary wrklst-bulk-btn" data-scope="all">Download all <span class="wrklst-bulk-count">(' + (counts.installs + counts.artworks) + ')</span></button>';
                }
                bulkRow += '</div>';
                headerBits.push(bulkRow);
            }

            this.$detailHeader.html(headerBits.join(''));
        },

        renderDetailHits: function() {
            var self = this;
            if (!this._currentHits.length) {
                this.$detailResults.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No images or artworks ——</div>');
                return;
            }
            var html = '';
            $.each(this._currentHits, function(k, hit) {
                html += self.renderWorkItem(hit);
            });
            this.$detailResults.html(html);
        },

        applyConfirmedFilter: function() {
            this.$detailResults.toggleClass('wrklst-show-confirmed-only', !!this.confirmedOnly);
        },

        escapeHtml: function(s) {
            return String(s).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        },

        countImportable: function(hits, opts) {
            var confirmedOnly = !!(opts && opts.confirmedOnly);
            var installs = 0;
            var artworks = 0;
            var artworksFirst = 0;
            $.each(hits, function(k, hit) {
                var isInstall = hit.item_kind === 'installimage' || (typeof hit.import_source_id === 'string' && hit.import_source_id.indexOf('exh-') === 0);
                // Install images don't carry a confirmed flag, so the toggle only
                // narrows the artwork side.
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
        },

        bulkDownload: function(scope) {
            var $items;
            var confirmedOnly = !!this.confirmedOnly;

            // Treat unconfirmed inventory items as out of scope when the filter
            // is on. Install images never get the unconfirmed class, so the
            // .not('.unconfirmed') chain is a no-op for them.
            var applyConfirmedFilter = function($set) {
                return confirmedOnly ? $set.not('.unconfirmed') : $set;
            };

            if (scope === 'artworks-first') {
                var seen = {};
                var picks = [];
                applyConfirmedFilter(this.$detailResults.find('.item.upload')
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
                $items = $(picks);
            } else {
                $items = applyConfirmedFilter(this.$detailResults.find('.item.upload')
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
            $items.each(function() {
                var $el = $(this);
                setTimeout(function() { $el.trigger('click'); }, i * 120);
                i++;
            });
        },

        copyPressRelease: function($btn) {
            var prId = $btn.data('pr-id');
            var text = this.pressReleases[prId];
            if (typeof text !== 'string') return;

            var $label = $btn.find('.wrklst-pr-hint');
            var revert = function(msg) {
                $label.text(msg);
                setTimeout(function() { $label.text('copy HTML'); }, 1600);
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
        }
    });

    $(document).ready(function() {
        if ($('#wrklst-exhibitions-container').length) {
            new wp.media.view.WrkLstExhibitions();
        }
    });

})(jQuery);
