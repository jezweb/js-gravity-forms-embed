<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    GF_JS_Embed
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the uninstall class
require_once plugin_dir_path(__FILE__) . 'includes/class-gf-js-embed-uninstall.php';

// Execute uninstall
GF_JS_Embed_Uninstall::uninstall();