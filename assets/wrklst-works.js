/**
 * WrkLst Works Page
 * Uses the shared base class for consistent functionality
 */
(function($) {
    'use strict';
    
    // Only proceed if wp.media is available
    if (typeof wp.media === 'undefined' || typeof wp.media.view.WrkLstBase === 'undefined') {
        // Fallback to jQuery implementation if base class isn't available
        $(document).ready(function() {
            console.warn('WrkLst base class not available, using legacy implementation');
        });
        return;
    }
    
    /**
     * Works page view extending the base class
     */
    wp.media.view.WrkLstWorks = wp.media.view.WrkLstBase.extend({
        el: '#wrklst-works-container',
        
        initialize: function() {
            var self = this;
            this.setupView();
        },
        
        setupView: function() {
            var self = this;
            
            // Cache elements
            this.$form = $('#wrklst_form');
            this.$results = $('#wrklst_results');
            this.$searchQuery = $('#search_query', this.$form);
            this.$filterInventory = $('#filter_inventory', this.$form);
            this.$filterAvailable = $('#filter_available', this.$form);
            
            // Setup variables
            this.perPage = 30;
            this.page = 1;
            this.searchQuery = '';
            this.workStatus = '';
            this.lastCall = '';
            this.wrklstSecurityNonce = false;
            this.hits = null;
            
            // Restore saved values
            if (this.getCookie('wrklst_search_query')) {
                this.$searchQuery.val(this.getCookie('wrklst_search_query'));
            }
            
            $("input[id^='filter_']", this.$form).each(function() {
                if (self.getCookie('wrklst_filter_' + this.id) && self.getCookie('wrklst_filter_' + this.id) != '0') {
                    this.checked = true;
                }
            });
            
            // Bind events
            this.bindEvents();
            
            // Initialize
            this.getApiCredentials(function(nonce) {
                self.wrklstSecurityNonce = nonce;
                self.loadInventories();
            });
        },
        
        bindEvents: function() {
            var self = this;
            
            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.page = 1;
                self.searchQuery = self.$searchQuery.val();
                self.workStatus = self.$filterAvailable.is(':checked') ? 'available' : '';
                self.$results.html('');
                $(window).off('scroll', self.scrollHandler);
                self.requestApi();
            });
            
            // Filter changes
            this.$filterAvailable.on('change', function() {
                self.setCookie('wrklst_filter_' + this.id, this.checked ? 1 : 0, 365);
                self.$form.submit();
            });
            
            this.$searchQuery.on('keyup', $.debounce(600, function(e) {
                self.setCookie('wrklst_search_query', self.$searchQuery.val(), 365);
                if (self.searchQuery !== self.$searchQuery.val()) {
                    self.$form.submit();
                }
            }));
            
            this.$filterInventory.on('change', function() {
                self.setCookie('wrklst_filter_inventory', self.$filterInventory.val(), 365);
                self.$form.submit();
            });
            
            // Multi-image handlers
            this.$results.on('click', '.upload.multiimg', function() {
                var id = $(this).data('import_source_id');
                $(".subitemid" + id).each(function(index) {
                    $(this).toggleClass("hidden");
                });
                $(".itemid" + id).each(function(index) {
                    $(this).toggleClass("open");
                });
            });
            
            this.$results.on('click', '.item.ender', function(e) {
                e.stopPropagation();
                var import_source_id = $(this).data('import_source_id');
                $(".subitemid" + import_source_id).addClass("hidden");
                $(".itemid" + import_source_id).removeClass("open");
            });
            
            // Upload handler
            this.$results.on('click', '.upload:not(.doneuploading)', function() {
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
                    
                    self.uploadImage(uploadData, function(data) {
                        $this.addClass('doneuploading').find('.dlimg img').replaceWith('<img src="' + self.getIconPath('baseline-check-24px.svg') + '" style="height:50px !important">');
                    });
                }
                return false;
            });
        },
        
        loadInventories: function() {
            var self = this;
            
            this.getInventories(this.wrklstSecurityNonce, function(data) {
                if (data && data.inventories) {
                    $.each(data.inventories, function(k, v) {
                        self.$filterInventory.append($('<option></option>').val(v.inv_sec_id).html(v.display_lnf));
                    });
                    if (self.getCookie('wrklst_filter_inventory')) {
                        self.$filterInventory.val(self.getCookie('wrklst_filter_inventory'));
                    }
                }
                self.$form.submit();
            });
        },
        
        requestApi: function() {
            var self = this;
            
            // Prevent double page loading
            var urlCall = this.workStatus + '|' + this.perPage + '|' + this.page + '|' + 
                         this.$filterInventory.val() + '|' + encodeURIComponent(this.searchQuery) + '|' + 
                         this.wrklstSecurityNonce;
            
            if (this.lastCall === urlCall) {
                return false;
            }
            
            this.getInventoryItems({
                work_status: this.workStatus,
                per_page: this.perPage,
                page: this.page,
                inv_sec_id: this.$filterInventory.val(),
                search: encodeURIComponent(this.searchQuery),
                wpnonce: this.wrklstSecurityNonce
            }, function(data) {
                if (!(data.totalHits > 0)) {
                    self.$results.html('<div style="color:#bbb;font-size:24px;text-align:center;margin:40px 0">—— No matches ——</div>');
                    $('#show_animation').remove();
                    return false;
                }
                self.renderResults(data);
                self.lastCall = urlCall;
            });
            
            return false;
        },
        
        renderResults: function(data) {
            var self = this;
            this.hits = data.hits;
            var pages = Math.ceil(data.totalHits / this.perPage);
            var imageHtml = '';
            
            // Use base class rendering
            $.each(data.hits, function(k, v) {
                imageHtml += self.renderWorkItem(v);
            });
            
            this.$results.html(this.$results.html() + imageHtml);
            $('#show_animation').remove();
            
            if (this.page < pages) {
                this.$results.after('<div id="show_animation" style="clear:both;padding:15px 0 0;text-align:center"><img style="width:60px" src="' + 
                                   this.getIconPath('baseline-autorenew-24px.svg') + '" class="loading-rotator"></div>');
                $(window).scroll(this.scrollHandler.bind(this));
            }
        },
        
        scrollHandler: function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 400) {
                $(window).off('scroll', this.scrollHandler);
                this.page = this.page + 1;
                this.requestApi();
            }
        }
    });
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the works page
        if ($('#wrklst-works-container').length || $('#wrklst_form').length) {
            new wp.media.view.WrkLstWorks();
        }
    });
    
})(jQuery);