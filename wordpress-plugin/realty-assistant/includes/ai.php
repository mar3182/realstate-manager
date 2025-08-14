<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Internal: fetch and prune global regen timestamps (60s window)
function rai_ai_get_recent_events() {
    $events = get_transient( 'rai_ai_events' );
    if ( ! is_array( $events ) ) { $events = []; }
    $cutoff = time() - 60;
    $events = array_values( array_filter( $events, function( $t ) use ( $cutoff ) { return $t >= $cutoff; } ) );
    return $events;
}

function rai_ai_record_event() {
    $events = rai_ai_get_recent_events();
    $events[] = time();
    set_transient( 'rai_ai_events', $events, 120 );
}

// Rate limit enforcement
function rai_ai_rate_limit_check( $post_id ) {
    // Global: max 5 per 60s
    $events = rai_ai_get_recent_events();
    if ( count( $events ) >= 5 ) {
        return [ 'ok' => false, 'reason' => 'Global rate limit reached (5/min). Try again shortly.' ];
    }
    // Per post: 1 every 5 minutes
    $last = intval( get_post_meta( $post_id, '_rai_ai_last_regen', true ) );
    if ( $last && ( time() - $last ) < 300 ) {
        $eta = 300 - ( time() - $last );
        return [ 'ok' => false, 'reason' => 'This property was regenerated recently. Wait ' . intval( $eta ) . 's.' ];
    }
    return [ 'ok' => true, 'reason' => '' ];
}

// Helper to regenerate AI description for a property post.
// Returns array( 'success' => bool, 'message' => string, 'rate_limited' => bool )
function rai_regenerate_ai_description( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'rai_property' ) {
        return [ 'success' => false, 'message' => 'Invalid post', 'rate_limited' => false ];
    }
    // Rate limit check
    $rl = rai_ai_rate_limit_check( $post_id );
    if ( ! $rl['ok'] ) {
        return [ 'success' => false, 'message' => $rl['reason'], 'rate_limited' => true ];
    }
    $base_text = get_post_meta( $post_id, '_rai_address', true );
    if ( ! $base_text ) { $base_text = $post->post_title; }

    $settings = rai_get_settings();
    if ( empty( $settings['api_base_url'] ) ) {
    return [ 'success' => false, 'message' => 'API base URL not configured', 'rate_limited' => false ];
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
        return [ 'success' => false, 'message' => $response->get_error_message(), 'rate_limited' => false ];
    }
    if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
        return [ 'success' => false, 'message' => 'HTTP ' . wp_remote_retrieve_response_code( $response ), 'rate_limited' => false ];
    }
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['description'] ) ) {
        return [ 'success' => false, 'message' => 'No description returned', 'rate_limited' => false ];
    }
    wp_update_post( [ 'ID' => $post_id, 'post_content' => wp_kses_post( $data['description'] ) ] );
    update_post_meta( $post_id, '_rai_ai_last_regen', time() );
    rai_ai_record_event();
    return [ 'success' => true, 'message' => 'Updated', 'rate_limited' => false ];
}

?>
