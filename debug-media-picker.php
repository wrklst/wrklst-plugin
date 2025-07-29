<?php
/**
 * Debug page for WrkLst Media Picker
 * 
 * This file helps diagnose issues with the media picker integration.
 * To use: Navigate to /wp-admin/admin.php?page=wrklst-debug-media
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get plugin settings
$wrklst_settings = get_option('wrklst_options');
$api_configured = !empty($wrklst_settings['api']) && !empty($wrklst_settings['account']);
?>

<div class="wrap">
    <h1>WrkLst Media Picker Debug</h1>
    
    <div class="notice notice-info">
        <p>This page helps diagnose issues with the WrkLst media picker integration.</p>
    </div>

    <h2>Configuration Status</h2>
    <table class="widefat">
        <tbody>
            <tr>
                <td><strong>API Key Configured:</strong></td>
                <td><?php echo $api_configured ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td><strong>Account Name:</strong></td>
                <td><?php echo !empty($wrklst_settings['account']) ? esc_html($wrklst_settings['account']) : 'Not set'; ?></td>
            </tr>
            <tr>
                <td><strong>AJAX URL:</strong></td>
                <td><?php echo admin_url('admin-ajax.php'); ?></td>
            </tr>
            <tr>
                <td><strong>Current User Can Upload:</strong></td>
                <td><?php echo current_user_can('upload_files') ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Test AJAX Endpoints</h2>
    <div id="ajax-test-results"></div>
    
    <p>
        <button class="button button-primary" id="test-api-cred">Test API Credentials</button>
        <button class="button button-primary" id="test-inventories">Test Get Inventories</button>
        <button class="button button-primary" id="test-inventory-items">Test Get Inventory Items</button>
    </p>

    <h2>Media Modal Test</h2>
    <p>Click the button below to open the WordPress media modal with WrkLst integration:</p>
    <button class="button button-primary" id="open-media-modal">Open Media Modal</button>
    
    <h2>Selected Media</h2>
    <div id="selected-media"></div>

    <h2>Debug Console</h2>
    <div style="background: #f0f0f0; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto;">
        <pre id="debug-console" style="margin: 0; font-size: 12px;"></pre>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var debugLog = function(message, data) {
        var timestamp = new Date().toLocaleTimeString();
        var logEntry = timestamp + ' - ' + message;
        if (data) {
            logEntry += '\n' + JSON.stringify(data, null, 2);
        }
        $('#debug-console').append(logEntry + '\n\n');
        console.log(message, data || '');
    };

    // Test API Credentials
    $('#test-api-cred').on('click', function() {
        debugLog('Testing API credentials...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wrklst_api_cred'
            },
            success: function(response) {
                debugLog('API Credentials Response:', response);
                if (response.success) {
                    $('#ajax-test-results').append('<div class="notice notice-success"><p>✅ API Credentials endpoint working! Nonce: ' + response.data.wrklst_nonce + '</p></div>');
                } else {
                    $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ API Credentials failed: ' + (response.data.message || 'Unknown error') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                debugLog('API Credentials Error:', {status: status, error: error, response: xhr.responseText});
                $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ API Credentials request failed: ' + error + '</p></div>');
            }
        });
    });

    // Test Get Inventories
    $('#test-inventories').on('click', function() {
        debugLog('Getting nonce first...');
        
        // First get the nonce
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wrklst_api_cred'
            },
            success: function(credResponse) {
                if (credResponse.success) {
                    var nonce = credResponse.data.wrklst_nonce;
                    debugLog('Got nonce, testing inventories...', {nonce: nonce});
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wrklst_get_inventories',
                            wpnonce: nonce
                        },
                        success: function(response) {
                            debugLog('Inventories Response:', response);
                            if (response.success) {
                                var count = response.data.inventories ? response.data.inventories.length : 0;
                                $('#ajax-test-results').append('<div class="notice notice-success"><p>✅ Inventories endpoint working! Found ' + count + ' inventories</p></div>');
                            } else {
                                $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ Inventories failed: ' + (response.data.message || 'Unknown error') + '</p></div>');
                            }
                        },
                        error: function(xhr, status, error) {
                            debugLog('Inventories Error:', {status: status, error: error, response: xhr.responseText});
                            $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ Inventories request failed: ' + error + '</p></div>');
                        }
                    });
                }
            }
        });
    });

    // Test Get Inventory Items
    $('#test-inventory-items').on('click', function() {
        debugLog('Getting nonce first...');
        
        // First get the nonce
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wrklst_api_cred'
            },
            success: function(credResponse) {
                if (credResponse.success) {
                    var nonce = credResponse.data.wrklst_nonce;
                    debugLog('Got nonce, testing inventory items...', {nonce: nonce});
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wrklst_get_inv_items',
                            wpnonce: nonce,
                            work_status: 'available',
                            per_page: 10,
                            page: 1,
                            inv_sec_id: 'all',
                            search: ''
                        },
                        success: function(response) {
                            debugLog('Inventory Items Response:', response);
                            if (response.success) {
                                var count = response.data.hits ? response.data.hits.length : 0;
                                var total = response.data.totalHits || 0;
                                $('#ajax-test-results').append('<div class="notice notice-success"><p>✅ Inventory Items endpoint working! Showing ' + count + ' of ' + total + ' items</p></div>');
                            } else {
                                $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ Inventory Items failed: ' + (response.data.message || 'Unknown error') + '</p></div>');
                            }
                        },
                        error: function(xhr, status, error) {
                            debugLog('Inventory Items Error:', {status: status, error: error, response: xhr.responseText});
                            $('#ajax-test-results').append('<div class="notice notice-error"><p>❌ Inventory Items request failed: ' + error + '</p></div>');
                        }
                    });
                }
            }
        });
    });

    // Media Modal Test
    $('#open-media-modal').on('click', function(e) {
        e.preventDefault();
        debugLog('Opening media modal...');

        // Check if WrkLstAjax is available
        if (typeof window.WrkLstAjax === 'undefined') {
            debugLog('ERROR: WrkLstAjax is not defined!');
            alert('WrkLstAjax is not loaded. Please check that wrklst-ajax.js is properly enqueued.');
            return;
        }

        // Create media frame
        var frame = wp.media({
            title: 'Select or Upload Media',
            button: {
                text: 'Use this media'
            },
            multiple: false
        });

        // Log frame creation
        debugLog('Media frame created', {
            frameId: frame.id,
            state: frame.state()
        });

        // When an image is selected
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            debugLog('Media selected:', attachment);
            
            $('#selected-media').html(
                '<div class="notice notice-success">' +
                '<p><strong>Selected:</strong> ' + attachment.title + '</p>' +
                '<p><strong>ID:</strong> ' + attachment.id + '</p>' +
                '<p><strong>URL:</strong> ' + attachment.url + '</p>' +
                '<img src="' + attachment.url + '" style="max-width: 200px;">' +
                '</div>'
            );
        });

        // Monitor router changes
        frame.on('content:create:wlwork', function() {
            debugLog('WrkLst Work tab activated');
        });

        frame.on('content:render', function() {
            debugLog('Content rendered', {
                mode: frame.content.mode()
            });
        });

        // Open the modal
        frame.open();
        
        // Check if WrkLst tab exists
        setTimeout(function() {
            var hasWrkLstTab = frame.$el.find('.media-menu-item:contains("Import WrkLst Work")').length > 0;
            debugLog('WrkLst tab exists:', hasWrkLstTab);
            
            if (!hasWrkLstTab) {
                debugLog('WARNING: WrkLst tab not found in media modal!');
            }
        }, 500);
    });

    // Initial checks
    debugLog('Page loaded, checking environment...');
    debugLog('jQuery version:', $.fn.jquery);
    debugLog('WP Media available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
    debugLog('WrkLstAjax available:', typeof window.WrkLstAjax !== 'undefined');
    debugLog('Ajax URL:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'Not defined');
});
</script>

<?php