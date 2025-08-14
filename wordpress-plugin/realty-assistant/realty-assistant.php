<?php
/**
 * Plugin Name: Realty Assistant Integration
 * Description: Sync properties & AI descriptions from the AI Realty Assistant backend.
 * Version: 0.1.0
 * Author: Your Company
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
define( 'RAI_VERSION', '0.1.0' );
define( 'RAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'RAI_OPTION_KEY', 'rai_settings' );
define( 'RAI_META_BACKEND_ID', '_rai_backend_id' );

require_once RAI_PLUGIN_DIR . 'includes/settings.php';
require_once RAI_PLUGIN_DIR . 'includes/cpt-property.php';
require_once RAI_PLUGIN_DIR . 'includes/sync.php';

// Activation: ensure CPT registered then flush permalinks
function rai_activate() {
    rai_register_property_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rai_activate' );

function rai_admin_menu() {
    add_menu_page(
        'Realty Assistant',
        'Realty Assistant',
        'manage_options',
        'rai-dashboard',
        'rai_render_dashboard',
        'dashicons-admin-home'
    );
    add_submenu_page(
        'rai-dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'rai-settings',
        'rai_render_settings_page'
    );
}
add_action( 'admin_menu', 'rai_admin_menu' );

function rai_render_dashboard() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    echo '<div class="wrap"><h1>Realty Assistant</h1>';
    echo '<p>Use the buttons below to sync properties or generate AI descriptions.</p>';
    echo '<form method="post">';
    wp_nonce_field( 'rai_sync_now', 'rai_sync_nonce' );
    submit_button( 'Sync Properties Now', 'primary', 'rai_sync_now' );
    echo '</form>';
    if ( isset( $_POST['rai_sync_now'] ) && check_admin_referer( 'rai_sync_now', 'rai_sync_nonce' ) ) {
        $result = rai_sync_properties();
        if ( is_wp_error( $result ) ) {
            echo '<div class="error"><p>Sync failed: ' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="updated"><p>Synced ' . intval( $result['count'] ) . ' properties.</p></div>';
        }
    }
    echo '</div>';
}
