/**
 * WrkLst Base Class
 * Shared functionality for WrkLst media views
 */
(function($) {
    'use strict';
    
    // Only proceed if wp.media is available
    if (typeof wp.media === 'undefined') return;
    
    /**
     * Base view for WrkLst functionality
     * Provides shared methods for API calls, utilities, and common UI patterns
     */
    wp.media.view.WrkLstBase = wp.media.View.extend({
        
        // Shared utilities
        getCookie: function(key) {
            return (document.cookie.match('(^|; )' + key + '=([^;]*)') || 0)[2];
        },
        
        setCookie: function(n, v, d, s) {
            var date = new Date;
            date.setTime(date.getTime() + 864e5 * d + 1000 * (s || 0));
            document.cookie = n + "=" + v + ";path=/;expires=" + date.toGMTString();
        },
        
        getIconPath: function(iconName) {
            var base = (typeof wrklst_plugin_url !== 'undefined' ? wrklst_plugin_url : '/wp-content/plugins/wrklst-plugin/');
            return base + 'assets/img/' + iconName;
        },

        THUMB_SIZE: 500,
        UPLOAD_SIZE: 2500,
        // Must match the imgproxy URL wrklst-app uses for overview thumbnails so the
        // preview hits the existing cache and imgproxy does not generate extra variants.
        PREVIEW_FORMAT: 'webp',
        // 1x1 transparent GIF, used when an item has no preview image so we don't emit
        // <img src=""> (which makes the browser refetch the current URL → 404).
        BLANK_PIXEL: 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=',

        imgproxyFormat: function() {
            return (typeof wrklst_image_config !== 'undefined' && wrklst_image_config.format) ? wrklst_image_config.format : 'jpg';
        },

        imgproxyUrl: function(url, width, fmt) {
            if (!url) return url;
            var i = url.indexOf('/plain/');
            if (i === -1) return url.replace('_150', '_' + width);
            var rest = url.slice(i);
            rest = /@[a-zA-Z0-9]+$/.test(rest) ? rest.replace(/@[a-zA-Z0-9]+$/, '@' + fmt) : rest + '@' + fmt;
            return url.slice(0, i) + '/rs:fit:' + width + ':0' + rest;
        },

        imgproxyPreview: function(url) {
            return url ? this.imgproxyUrl(url, this.THUMB_SIZE, this.PREVIEW_FORMAT) : this.BLANK_PIXEL;
        },

        imgproxyThumb: function(url, width) {
            return this.imgproxyUrl(url, width, this.imgproxyFormat());
        },
        
        // API Methods
        getApiCredentials: function(callback) {
            var self = this;
            
            // Check if nonce is already available from localized script
            if (typeof wrklst_ajax !== 'undefined' && wrklst_ajax.nonce) {
                callback(wrklst_ajax.nonce);
                return;
            }
            
            // Use WrkLstAjax if available
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getApiCredentials(function(data) {
                    callback(data.wrklst_nonce);
                });
            } else {
                // Direct jQuery AJAX fallback
                $.post(ajaxurl || '.', {
                    action: 'wrklst_api_cred',
                }, function(data) {
                    if (data.success) {
                        callback(data.data.wrklst_nonce);
                    }
                });
            }
        },
        
        getInventories: function(nonce, callback) {
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getInventories(nonce, callback);
            } else {
                // Direct jQuery AJAX fallback
                $.post(ajaxurl || '.', {
                    action: 'wrklst_get_inventories',
                    wpnonce: nonce
                }, function(response) {
                    if (response.success && response.data) {
                        callback(response.data);
                    }
                });
            }
        },
        
        getInventoryItems: function(params, callback) {
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.getInventoryItems(params, callback);
            } else {
                // Direct jQuery AJAX fallback
                params.action = 'wrklst_get_inv_items';
                $.post(ajaxurl || '.', params, function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                });
            }
        },
        
        uploadImage: function(params, callback) {
            if (typeof WrkLstAjax !== 'undefined') {
                WrkLstAjax.uploadImage(params, callback);
            } else {
                // Direct jQuery AJAX fallback
                params.action = 'wrklst_upload';
                $.post(ajaxurl || '.', params, function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                });
            }
        },
        
        // Common UI rendering methods
        hasImportableImage: function(work) {
            if (work.multi_img == "1") {
                if (!work.imgs || !work.imgs.length) return false;
                for (var i = 0; i < work.imgs.length; i++) {
                    if (work.imgs[i].largeImageURL || work.imgs[i].url_full) return true;
                }
                return false;
            }
            return !!(work.largeImageURL || work.url_full || work.previewURL || work.url_thumb);
        },

        renderWorkItem: function(work) {
            // Skip items with no image — they are not importable (e.g. placeholder
            // inventory works in an exhibition with no media uploaded yet) and
            // emitting <img src=""> makes the browser refetch the page → 404.
            if (!this.hasImportableImage(work)) return '';

            if (work.multi_img == "1") {
                return this.renderMultiImageWork(work);
            }
            return this.renderSingleImageWork(work);
        },

        // Return the confirmed/unconfirmed class for an exhibition inventory hit.
        // Backend sets `confirmed: true|false` on inventory hits inside an
        // exhibition; install images (and works outside an exhibition context)
        // have no `confirmed` field, so they get no class and are unaffected by
        // the "confirmed only" filter.
        confirmedClass: function(work) {
            if (work.confirmed === true) return ' confirmed';
            if (work.confirmed === false) return ' unconfirmed';
            return '';
        },

        confirmedBadge: function(work) {
            return work.confirmed === true ? '<b class="wrklst-confirmed-badge">confirmed</b><br />' : '';
        },
        
        renderMultiImageWork: function(work) {
            var self = this;
            var html = '';
            
            // Main multi-image item
            html += '<div class="item itemid' + work.import_source_id + ' upload multiimg' +
                    (work.exists === 2 ? ' exists' : (work.exists ? ' existsp' : '')) +
                    this.confirmedClass(work) + '" ' +
                    this.buildDataAttributes(work) + '>' +
                    '<img src="' + this.imgproxyPreview(work.previewURL || work.url_thumb) + '" title="#' +
                    (work.inv_nr || work.invnr) + '" alt="#' + (work.inv_nr || work.invnr) + '">' +
                    '<div class="dlimg">' +
                        '<img src="' + this.getIconPath('baseline-more_horiz-24px.svg') + '" class="more">' +
                        '<img src="' + this.getIconPath('baseline-arrow_forward_ios-24px.svg') + '" class="open">' +
                        '<div class="caption">' + work.title + '</div>' +
                    '</div>' +
                    '<div class="wrktitle"><img src="' + this.getIconPath('baseline-more_horiz-24px.svg') + '"><br />' +
                    this.confirmedBadge(work) +
                    (work.exists ? '<b>' + (work.exists === 2 ? 'all' : 'partly') + ' downloaded</b><br />' : '') +
                    '#' + (work.inv_nr || work.invnr) + '</div>' +
                '</div>';
            
            // Sub-images
            if (work.imgs && work.imgs.length) {
                for (var i = 0; i < work.imgs.length; i++) {
                    html += this.renderSubImage(work, work.imgs[i]);
                }
            }
            
            // End marker
            html += '<div class="item itemid' + work.import_source_id + 
                    ' subitemid' + work.import_source_id + ' hidden ender" ' +
                    'data-w="165" data-h="1000" data-import_source_id="' + work.import_source_id + '">' +
                    '<div class="dlimg">' +
                        '<img src="' + this.getIconPath('baseline-arrow_back_ios-24px.svg') + '">' +
                    '</div>' +
                '</div>';
            
            return html;
        },
        
        renderSingleImageWork: function(work) {
            var html = '<div class="item upload' + (work.exists ? ' exists' : '') +
                      this.confirmedClass(work) + '" ' +
                      this.buildDataAttributes(work) + '>' +
                      '<img src="' + this.imgproxyPreview(work.previewURL || work.url_thumb) + '" title="#' +
                      (work.inv_nr || work.invnr) + '" alt="#' + (work.inv_nr || work.invnr) + '">' +
                      '<div class="dlimg">' +
                          '<img src="' + this.getIconPath('round-cloud_download-24px.svg') + '">' +
                          '<div class="caption">' + work.title + '</div>' +
                      '</div>' +
                      '<div class="wrktitle"><img src="' + this.getIconPath('round-cloud_download-24px.svg') + '"><br />' +
                      this.confirmedBadge(work) +
                      (work.exists ? '<b>downloaded</b><br />' : '') + '#' + (work.inv_nr || work.invnr) + '</div>' +
                  '</div>';

            return html;
        },
        
        renderSubImage: function(work, img) {
            // Sub-images inherit the parent work's confirmed status — the pivot
            // confirmed flag belongs to the inventory record, not the individual
            // image — so they hide together when "Confirmed only" is on.
            var html = '<div class="subitem hidden subitemid' + work.import_source_id +
                      ' item upload' + (img.exists ? ' exists' : '') +
                      this.confirmedClass(work) + '" ' +
                      'data-title="' + work.title + '" ' +
                      'data-wpnonce="' + work.wpnonce + '" ' +
                      'data-url="' + this.imgproxyThumb(img.largeImageURL || img.url_full, this.UPLOAD_SIZE) + '" ' +
                      'data-invnr="' + (work.inv_nr || work.invnr) + '" ' +
                      'data-artist="' + (work.name_artist || work.artist) + '" ' +
                      'data-import_source_id="' + work.import_source_id + '" ' +
                      'data-image_id="' + img.id + '" ' +
                      'data-import_inventory_id="' + (work.import_inventory_id || work.inv_id) + '" ' +
                      'data-caption="' + (work.caption || '') + (img.photocredit || '') + '" ' +
                      'data-w="' + (img.webformatWidth || 0) + '" ' +
                      'data-h="' + (img.webformatHeight || 0) + '">' +
                      '<img src="' + this.imgproxyPreview(img.previewURL || img.url_thumb) + '" title="#' +
                      (work.inv_nr || work.invnr) + '" alt="#' + (work.inv_nr || work.invnr) + '">' +
                      '<div class="dlimg">' +
                          '<img src="' + this.getIconPath('round-cloud_download-24px.svg') + '">' +
                          '<div class="caption">' + work.title + '</div>' +
                      '</div>' +
                      '<div class="wrktitle"><img src="' + this.getIconPath('round-cloud_download-24px.svg') + '"><br />' +
                      (img.exists ? '<b>downloaded</b><br />' : '') + '#' + (work.inv_nr || work.invnr) + '</div>' +
                  '</div>';
            
            return html;
        },
        
        buildDataAttributes: function(work) {
            return 'data-title="' + work.title + '" ' +
                   'data-wpnonce="' + work.wpnonce + '" ' +
                   'data-url="' + this.imgproxyThumb(work.largeImageURL || work.url_full, this.UPLOAD_SIZE) + '" ' +
                   'data-invnr="' + (work.inv_nr || work.invnr) + '" ' +
                   'data-artist="' + (work.name_artist || work.artist) + '" ' +
                   'data-import_source_id="' + work.import_source_id + '" ' +
                   'data-image_id="' + (work.imageId || 0) + '" ' +
                   'data-import_inventory_id="' + (work.import_inventory_id || work.inv_id) + '" ' +
                   'data-caption="' + (work.caption || '') + (work.photocredit || '') + '" ' +
                   'data-description="' + (work.description || '') + '" ' +
                   'data-alt="' + (work.alt || '') + '" ' +
                   'data-w="' + (work.webformatWidth || 0) + '" ' +
                   'data-h="' + (work.webformatHeight || 0) + '"';
        }
    });
    
})(jQuery);