<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Helper to regenerate AI description for a property post.
// Returns array( 'success' => bool, 'message' => string )
function rai_regenerate_ai_description( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'rai_property' ) {
        return [ 'success' => false, 'message' => 'Invalid post' ];
    }
    $base_text = get_post_meta( $post_id, '_rai_address', true );
    if ( ! $base_text ) { $base_text = $post->post_title; }

    $settings = rai_get_settings();
    if ( empty( $settings['api_base_url'] ) ) {
        return [ 'success' => false, 'message' => 'API base URL not configured' ];
    }
    $endpoint = trailingslashit( $settings['api_base_url'] ) . 'ai/draft';
    $args = [
        'headers' => [ 'Accept' => 'application/json', 'Content-Type' => 'application/json' ],
        'method' => 'POST',
        'timeout' => 25,
        'body' => wp_json_encode( [ 'raw_text' => $base_text ] ),
    ];
    if ( ! empty( $settings['auth_token'] ) ) {
        $args['headers']['Authorization'] = 'Bearer ' . $settings['auth_token'];
    } else {
        $args['headers']['X-Tenant-ID'] = $settings['tenant_id'];
    }
    $response = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $response ) ) {
        return [ 'success' => false, 'message' => $response->get_error_message() ];
    }
    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return [ 'success' => false, 'message' => 'HTTP ' . wp_remote_retrieve_response_code( $response ) ];
    }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['description'] ) ) {
        return [ 'success' => false, 'message' => 'No description returned' ];
    }
    wp_update_post( [ 'ID' => $post_id, 'post_content' => wp_kses_post( $data['description'] ) ] );
    return [ 'success' => true, 'message' => 'Updated' ];
}

?>
