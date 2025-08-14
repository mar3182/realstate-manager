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
define( 'RAI_STATUS_OPTION', 'rai_sync_status' );

require_once RAI_PLUGIN_DIR . 'includes/settings.php';
require_once RAI_PLUGIN_DIR . 'includes/cpt-property.php';
require_once RAI_PLUGIN_DIR . 'includes/sync.php';
require_once RAI_PLUGIN_DIR . 'includes/meta-box.php';

// Admin list columns: thumbnail + price + address
function rai_property_columns( $columns ) {
    $new = [];
    $new['cb'] = $columns['cb'];
    $new['thumbnail'] = 'Image';
    $new['price'] = 'Price';
    $new['address'] = 'Address';
    foreach ( $columns as $key => $label ) {
        if ( in_array( $key, ['cb','date'] ) ) continue;
        $new[$key] = $label;
    }
    if ( isset( $columns['date'] ) ) {
        $new['date'] = $columns['date'];
    }
    return $new;
}
add_filter( 'manage_rai_property_posts_columns', 'rai_property_columns' );

function rai_property_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'thumbnail':
            if ( has_post_thumbnail( $post_id ) ) {
                echo get_the_post_thumbnail( $post_id, [60,60] );
            } else {
                echo '<span style="opacity:.5">—</span>';
            }
            break;
        case 'price':
            $price = get_post_meta( $post_id, '_rai_price', true );
            echo $price ? esc_html( $price ) : '<span style="opacity:.5">—</span>';
            break;
        case 'address':
            $addr = get_post_meta( $post_id, '_rai_address', true );
            echo $addr ? esc_html( $addr ) : '<span style="opacity:.5">—</span>';
            break;
    }
}
add_action( 'manage_rai_property_posts_custom_column', 'rai_property_column_content', 10, 2 );

// Activation / Deactivation
function rai_activate() {
    rai_register_property_cpt();
    flush_rewrite_rules();
    if ( ! wp_next_scheduled( 'rai_hourly_sync' ) ) {
        wp_schedule_event( time() + 60, 'hourly', 'rai_hourly_sync' );
    }
}
register_activation_hook( __FILE__, 'rai_activate' );

function rai_deactivate() {
    $timestamp = wp_next_scheduled( 'rai_hourly_sync' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'rai_hourly_sync' );
    }
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rai_deactivate' );

add_action( 'rai_hourly_sync', function() {
    rai_sync_properties();
} );

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
    echo '<p>Use the button below to sync properties (automatic hourly cron enabled after activation).</p>';
    $status = get_option( RAI_STATUS_OPTION, [] );
    if ( ! empty( $status['last_success'] ) ) {
        echo '<p><strong>Last Sync:</strong> ' . esc_html( date_i18n( 'Y-m-d H:i', intval( $status['last_success'] ) ) ) . '</p>';
    }
    if ( ! empty( $status['last_error'] ) ) {
        echo '<p style="color:#b32d2e"><strong>Last Error:</strong> ' . esc_html( $status['last_error'] ) . '</p>';
    }
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
