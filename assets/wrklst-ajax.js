/**
 * WrkLst Plugin AJAX Handler
 */
(function($) {
    'use strict';

    window.WrkLstAjax = {
        /**
         * Make AJAX request to WordPress
         */
        request: function(action, data, successCallback, errorCallback) {
            data = data || {};
            data.action = action;
            
            // Ensure ajaxurl is defined
            var ajax_url = typeof ajaxurl !== 'undefined' ? ajaxurl : 
                          (typeof wrklst_ajax !== 'undefined' && wrklst_ajax.ajax_url ? wrklst_ajax.ajax_url : 
                          '/wp-admin/admin-ajax.php');
            
            return $.ajax({
                url: ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (typeof response === 'object' && response.success) {
                        if (typeof successCallback === 'function') {
                            successCallback(response.data);
                        }
                    } else if (typeof response === 'object' && !response.success) {
                        if (typeof errorCallback === 'function') {
                            errorCallback(response.data || { message: 'Unknown error' });
                        } else {
                            console.error('WrkLst Ajax Error:', response.data || 'Unknown error');
                        }
                    } else {
                        // Response is not JSON - likely HTML error
                        console.error('WrkLst Ajax Error: Invalid response format. Check for PHP errors.');
                        if (typeof errorCallback === 'function') {
                            errorCallback({ message: 'Invalid response format', response: response });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = error || 'Unknown error';
                    var responseText = '';
                    
                    try {
                        if (xhr.responseText) {
                            // Try to parse as JSON first
                            var jsonResponse = JSON.parse(xhr.responseText);
                            if (jsonResponse.data && jsonResponse.data.message) {
                                errorMessage = jsonResponse.data.message;
                            }
                        }
                    } catch(e) {
                        // Not JSON, probably HTML error
                        responseText = xhr.responseText;
                        if (responseText.indexOf('<') === 0) {
                            errorMessage = 'Server returned HTML instead of JSON. Check for PHP errors.';
                        }
                    }
                    
                    if (typeof errorCallback === 'function') {
                        errorCallback({ message: errorMessage, status: status, responseText: responseText });
                    } else {
                        console.error('WrkLst Ajax Error:', errorMessage);
                        if (responseText) {
                            console.error('Response:', responseText);
                        }
                    }
                }
            });
        },

        /**
         * Get API credentials
         */
        getApiCredentials: function(callback) {
            this.request('wrklst_api_cred', {}, callback);
        },

        /**
         * Get inventories
         */
        getInventories: function(nonce, callback) {
            this.request('wrklst_get_inventories', {
                wpnonce: nonce
            }, callback);
        },

        /**
         * Get inventory items
         */
        getInventoryItems: function(params, callback) {
            this.request('wrklst_get_inv_items', params, callback);
        },

        /**
         * Upload image
         */
        uploadImage: function(params, callback) {
            this.request('wrklst_upload', params, callback);
        }
    };

})(jQuery);