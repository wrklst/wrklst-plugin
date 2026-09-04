<?php
/**
* @package WrkLstPlugin
*/
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die();
}

global $wpdb;
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $wpdb->esc_like('wrklst_') . '%'));
delete_option('wrklst_options');
