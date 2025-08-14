<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rai_sync_properties() {
    $s = rai_get_settings();
    $endpoint = trailingslashit( $s['api_base_url'] ) . 'properties/';

    $args = [
        'headers' => [
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ];
    if ( ! empty( $s['auth_token'] ) ) {
        $args['headers']['Authorization'] = 'Bearer ' . $s['auth_token'];
    } else {
        $args['headers']['X-Tenant-ID'] = $s['tenant_id'];
    }

    $response = wp_remote_get( $endpoint, $args );
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return new WP_Error( 'rai_sync_failed', 'Unexpected status: ' . $code );
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'rai_invalid_json', 'Invalid JSON response' );
    }
    $count = 0;
    foreach ( $data as $prop ) {
        $title = isset( $prop['title'] ) ? sanitize_text_field( $prop['title'] ) : 'Untitled Property';
        $existing = get_page_by_title( $title, OBJECT, 'rai_property' );
        if ( $existing ) {
            // Update content
            wp_update_post( [
                'ID' => $existing->ID,
                'post_content' => isset( $prop['description'] ) ? wp_kses_post( $prop['description'] ) : '',
            ] );
        } else {
            wp_insert_post( [
                'post_type' => 'rai_property',
                'post_title' => $title,
                'post_status' => 'publish',
                'post_content' => isset( $prop['description'] ) ? wp_kses_post( $prop['description'] ) : '',
            ] );
        }
        $count++;
    }
    return [ 'count' => $count ];
}
