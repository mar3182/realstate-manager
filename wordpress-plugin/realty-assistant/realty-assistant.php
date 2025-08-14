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
require_once RAI_PLUGIN_DIR . 'includes/ai.php';

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

// Row action: Regenerate AI
function rai_row_actions_ai_regen( $actions, $post ) {
    if ( $post->post_type !== 'rai_property' ) {
        return $actions;
    }
    $nonce = wp_create_nonce( 'rai_ai_row_regen_' . $post->ID );
    $url = add_query_arg( [
        'rai_ai_regen' => 1,
        'post' => $post->ID,
        '_rai_nonce' => $nonce,
    ], admin_url( 'edit.php?post_type=rai_property' ) );
    $actions['rai_ai_regen'] = '<a href="' . esc_url( $url ) . '" aria-label="Regenerate AI Description">Regenerate AI</a>';
    return $actions;
}
add_filter( 'post_row_actions', 'rai_row_actions_ai_regen', 10, 2 );

function rai_handle_row_ai_regen() {
    if ( ! is_admin() ) return;
    if ( empty( $_GET['rai_ai_regen'] ) || empty( $_GET['post'] ) ) return;
    $post_id = intval( $_GET['post'] );
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( empty( $_GET['_rai_nonce'] ) || ! wp_verify_nonce( $_GET['_rai_nonce'], 'rai_ai_row_regen_' . $post_id ) ) return;

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'rai_property' ) return;

    $result = rai_regenerate_ai_description( $post_id );
    if ( $result['success'] ) {
        add_filter( 'redirect_post_location', function( $location ) use ( $post_id ) {
            return add_query_arg( 'rai_ai_regen_success', 1, $location );
        } );
    } else {
        $err_param = $result['rate_limited'] ? 'rai_ai_regen_rl' : 'rai_ai_regen_error';
        add_filter( 'redirect_post_location', function( $location ) use ( $err_param ) {
            return add_query_arg( $err_param, 1, $location );
        } );
    }
    wp_safe_redirect( admin_url( 'edit.php?post_type=rai_property' ) );
    exit;
}
add_action( 'admin_init', 'rai_handle_row_ai_regen' );

function rai_admin_notices_ai_regen() {
    if ( isset( $_GET['rai_ai_regen_success'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>AI description regenerated.</p></div>';
    } elseif ( isset( $_GET['rai_ai_regen_error'] ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>AI regeneration failed.</p></div>';
    } elseif ( isset( $_GET['rai_ai_regen_rl'] ) ) {
        echo '<div class="notice notice-warning is-dismissible"><p>Rate limit: please wait before regenerating again.</p></div>';
    }
}
add_action( 'admin_notices', 'rai_admin_notices_ai_regen' );

// Bulk action skeleton for future (will be completed in next step)
add_filter( 'bulk_actions-edit-rai_property', function( $bulk_actions ) {
    $bulk_actions['rai_bulk_ai_regen'] = 'Regenerate AI Descriptions';
    return $bulk_actions;
} );

add_filter( 'handle_bulk_actions-edit-rai_property', function( $redirect, $doaction, $post_ids ) {
    if ( $doaction === 'rai_bulk_ai_regen' ) {
        $success = 0; $fail = 0;
        foreach ( $post_ids as $pid ) {
            $res = rai_regenerate_ai_description( $pid );
            if ( $res['success'] ) { $success++; } else { $fail++; }
        }
        $redirect = add_query_arg( [
            'rai_bulk_ai_done' => 1,
            'rai_bulk_ai_success' => $success,
            'rai_bulk_ai_fail' => $fail,
        ], $redirect );
    }
    return $redirect;
}, 10, 3 );

add_action( 'admin_notices', function() {
    if ( isset( $_GET['rai_bulk_ai_done'] ) ) {
        $s = intval( $_GET['rai_bulk_ai_success'] ?? 0 );
        $f = intval( $_GET['rai_bulk_ai_fail'] ?? 0 );
        echo '<div class="notice notice-info is-dismissible"><p>AI regeneration complete: ' . esc_html( $s ) . ' success, ' . esc_html( $f ) . ' failed.</p></div>';
    }
} );

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
