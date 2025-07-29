import Alpine from 'alpinejs';

// Make Alpine available globally for WordPress
window.Alpine = Alpine;

// WrkLst Media Selector Component
Alpine.data('wrkLstMediaSelector', (wrklstNonce) => ({
    // State
    loading: false,
    works: [],
    inventories: [],
    uploadingIds: [],
    page: 1,
    totalHits: 0,
    maxRendered: 0,
    lastCall: '',
    worksData: {}, // Store original work data
    
    // Filters
    work_status_type: "1",
    work_status: "available",
    inv_sec_id: "all",
    search_query: "",
    
    // Initialize
    init() {
        this.loadInventories();
    },
    
    // Get cookie
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return "";
    },
    
    // Set cookie
    setCookie(name, value) {
        document.cookie = `${name}=${value}; path=/; max-age=31536000`;
    },
    
    // Load inventories
    async loadInventories() {
        const self = this;
        window.WrkLstAjax.getInventories(wrklstNonce, function(data) {
            if (data && data.inventories) {
                self.inventories = data.inventories;
                
                // Restore saved filters
                const savedInventory = self.getCookie('wrklst_filter_inventory');
                if (savedInventory) {
                    self.inv_sec_id = savedInventory;
                }
                
                const savedStatus = self.getCookie('wrklst_filter_work_status');
                if (savedStatus) {
                    self.work_status_type = savedStatus;
                    // Update work_status based on type
                    const statusMap = {
                        '1': 'available',
                        '2': 'reserved', 
                        '3': 'sold',
                        '4': 'not_for_sale',
                        '5': 'no_longer_available',
                        '0': 'all'
                    };
                    self.work_status = statusMap[savedStatus] || 'available';
                }
            }
            self.fetchWorks();
        }, function(error) {
            console.error('Failed to load inventories:', error);
            self.fetchWorks();
        });
    },
    
    // Handle status change
    onStatusChange(event) {
        this.work_status_type = event.target.value;
        this.work_status = event.target.options[event.target.selectedIndex].text.toLowerCase().replace(' ', '_');
        this.setCookie('wrklst_filter_work_status', this.work_status_type);
        this.resetAndFetch();
    },
    
    // Handle inventory change
    onInventoryChange(event) {
        this.inv_sec_id = event.target.value;
        this.setCookie('wrklst_filter_inventory', this.inv_sec_id);
        this.resetAndFetch();
    },
    
    // Handle search
    onSearch(event) {
        this.search_query = event.target.value;
        this.resetAndFetch();
    },
    
    // Reset and fetch
    resetAndFetch() {
        this.page = 1;
        this.works = [];
        this.maxRendered = 0;
        this.lastCall = '';
        this.fetchWorks();
    },
    
    // Fetch works
    async fetchWorks() {
        const callKey = `${this.work_status}|30|${this.page}|${this.inv_sec_id}|${encodeURIComponent(this.search_query)}`;
        if (this.lastCall === callKey || this.loading) return;
        
        this.loading = true;
        this.lastCall = callKey;
        const self = this;
        
        window.WrkLstAjax.getInventoryItems({
            work_status: this.work_status,
            per_page: 30,
            page: this.page,
            inv_sec_id: this.inv_sec_id,
            search: encodeURIComponent(this.search_query),
            wpnonce: wrklstNonce
        }, function(data) {
            self.loading = false;
            
            if (data && data.hits) {
                self.totalHits = data.totalHits || 0;
                
                // Store original work data and create reactive copies
                const processedWorks = data.hits.map(work => {
                    // Store original in worksData
                    self.worksData[work.import_source_id] = work;
                    
                    // Create a shallow reactive copy
                    const reactiveCopy = {
                        ...work,
                        import_source_id: work.import_source_id,
                        title: work.title || '',
                        artist: work.name_artist || work.artist || '',
                        inv_nr: work.inv_nr || work.invnr || '',
                        url_thumb: work.url_thumb || '',
                        url_full: work.url_full || '',
                        exists: work.exists || false,
                        multi_img: work.multi_img || '0',
                        inv_id: work.inv_id || 0,
                        inv_title: work.inv_title || '',
                        description: work.description || '',
                        fulltext_desc: work.fulltext_desc || '',
                        categories: work.categories || [],
                        imgs: work.imgs ? work.imgs.map(img => ({
                            id: img.id,
                            url_thumb: img.url_thumb || img.previewURL || '',
                            url_full: img.url_full || '',
                            previewURL: img.previewURL || img.url_thumb || '',
                            exists: img.exists || false
                        })) : []
                    };
                    
                    return reactiveCopy;
                });
                
                if (self.page === 1) {
                    self.works = processedWorks;
                } else {
                    self.works = [...self.works, ...processedWorks];
                }
                self.maxRendered = self.works.length;
            }
        }, function(error) {
            self.loading = false;
            console.error('Failed to load works:', error);
        });
    },
    
    // Handle scroll
    handleScroll(event) {
        const scrollPercent = 100 * window.scrollY / (document.documentElement.scrollHeight - window.innerHeight);
        if (scrollPercent > 80 && this.totalHits > this.maxRendered && !this.loading) {
            this.page++;
            this.fetchWorks();
        }
    },
    
    // Check if uploading
    isUploading(id) {
        return this.uploadingIds.includes(id);
    },
    
    // Select image
    selectImage(work, img = null) {
        if ((img && img.exists) || (!img && work.exists)) {
            alert('This artwork has already been imported.');
            return;
        }
        
        const uploadId = img ? `${work.import_source_id}_${img.id}` : work.import_source_id.toString();
        
        if (this.isUploading(uploadId)) {
            return;
        }
        
        this.uploadingIds.push(uploadId);
        
        // Get original work data to avoid reactivity issues
        const originalWork = this.worksData[work.import_source_id] || work;
        
        const imageUrl = img ? 
            (originalWork.imgs && originalWork.imgs.find(i => i.id === img.id)?.url_full || img.url_full) : 
            originalWork.url_full;
        const imageId = img ? img.id : 0;
        
        const self = this;
        
        window.WrkLstAjax.uploadImage({
            image_url: imageUrl,
            invnr: originalWork.inv_nr || originalWork.invnr || '',
            artist: originalWork.name_artist || originalWork.artist || '',
            title: originalWork.title || '',
            image_caption: this.buildCaption(originalWork),
            image_description: originalWork.fulltext_desc || '',
            image_alt: originalWork.title || '',
            import_source_id: originalWork.import_source_id,
            image_id: imageId,
            import_inventory_id: originalWork.inv_id || 0,
            search_query: this.search_query,
            wpnonce: originalWork.wpnonce || wrklstNonce
        }, function(data) {
            self.uploadingIds = self.uploadingIds.filter(id => id !== uploadId);
            
            // Mark as exists in reactive copy
            if (img) {
                img.exists = true;
            } else {
                work.exists = true;
            }
            
            // Also mark in original data
            if (originalWork) {
                if (img && originalWork.imgs) {
                    const originalImg = originalWork.imgs.find(i => i.id === img.id);
                    if (originalImg) originalImg.exists = 1;
                } else {
                    originalWork.exists = 1;
                }
            }
            
            // Force Alpine to re-render
            self.works = [...self.works];
            
            // Create attachment and update selection
            const attachment = wp.media.model.Attachment.create({ id: data.id });
            attachment.fetch();
            wp.media.frame.state().get('selection').add(attachment);
            
            // Switch back to browse mode
            wp.media.frame.content.mode('browse');
        }, function(error) {
            self.uploadingIds = self.uploadingIds.filter(id => id !== uploadId);
            alert('Failed to upload image: ' + (error.message || 'Unknown error'));
        });
    },
    
    // Build caption
    buildCaption(work) {
        let caption = [];
        if (work.inv_title) {
            caption.push(work.inv_title + '<br/>');
        }
        if (work.description) {
            caption.push('<i>' + work.description + '</i><br/>');
        }
        caption.push('Created by ' + (work.name_artist || work.artist || 'Unknown') + '<br/><br/>');
        if (work.categories && work.categories.length > 0) {
            caption.push(work.categories.join(' | '));
        }
        return caption.join('');
    }
}));

// Start Alpine when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    // DOM already loaded
    Alpine.start();
}

// Export for use in media extension
export default Alpine;