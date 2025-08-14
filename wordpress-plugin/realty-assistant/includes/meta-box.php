<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Meta box showing backend ID, last sync, regenerate AI description button
function rai_add_property_meta_box() {
    add_meta_box(
        'rai_property_meta',
        'Realty Assistant',
        'rai_render_property_meta_box',
        'rai_property',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'rai_add_property_meta_box' );

function rai_render_property_meta_box( $post ) {
    $backend_id = get_post_meta( $post->ID, RAI_META_BACKEND_ID, true );
    $cover_src = get_post_meta( $post->ID, '_rai_cover_image_url', true );
    $last_status = get_option( RAI_STATUS_OPTION, [] );
    $last_sync = isset( $last_status['last_success'] ) ? intval( $last_status['last_success'] ) : 0;
    $last_error = isset( $last_status['last_error'] ) ? $last_status['last_error'] : '';
    echo '<p><strong>Backend ID:</strong> ' . ( $backend_id ? esc_html( $backend_id ) : '—' ) . '</p>';
    if ( $cover_src ) {
        echo '<p><img src="' . esc_url( $cover_src ) . '" style="max-width:100%;height:auto;border:1px solid #ccc;" /></p>';
    }
    if ( $last_sync ) {
        echo '<p><strong>Last Sync:</strong> ' . esc_html( date_i18n( 'Y-m-d H:i', $last_sync ) ) . '</p>';
    }
    if ( $last_error ) {
        echo '<p style="color:#b32d2e"><strong>Last Error:</strong> ' . esc_html( $last_error ) . '</p>';
    }
    wp_nonce_field( 'rai_ai_regen', 'rai_ai_regen_nonce' );
    echo '<p><button class="button" name="rai_ai_regen" value="1">Regenerate AI Description</button></p>';
}

// Handle AI regenerate on save_post
function rai_handle_ai_regen( $post_id, $post, $update ) {
    if ( $post->post_type !== 'rai_property' ) return;
    if ( empty( $_POST['rai_ai_regen'] ) ) return;
    if ( ! isset( $_POST['rai_ai_regen_nonce'] ) || ! wp_verify_nonce( $_POST['rai_ai_regen_nonce'], 'rai_ai_regen' ) ) return;

    // Build prompt from meta / content
    $base_text = get_post_meta( $post_id, '_rai_address', true );
    if ( ! $base_text ) { $base_text = $post->post_title; }

    $settings = rai_get_settings();
    $endpoint = trailingslashit( $settings['api_base_url'] ) . 'ai/draft';
    $args = [
        'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
        'method' => 'POST',
        'timeout' => 20,
        'body' => wp_json_encode( [ 'raw_text' => $base_text ] ),
    ];
    if ( ! empty( $settings['auth_token'] ) ) {
        $args['headers']['Authorization'] = 'Bearer ' . $settings['auth_token'];
    } else {
        $args['headers']['X-Tenant-ID'] = $settings['tenant_id'];
    }
    $response = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $response ) ) {
        return;
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return;
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( isset( $data['description'] ) ) {
        remove_action( 'save_post', 'rai_handle_ai_regen', 20 );
        wp_update_post( [ 'ID' => $post_id, 'post_content' => wp_kses_post( $data['description'] ) ] );
        add_action( 'save_post', 'rai_handle_ai_regen', 20, 3 );
    }
}
add_action( 'save_post', 'rai_handle_ai_regen', 20, 3 );

?>
