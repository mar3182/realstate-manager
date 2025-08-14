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
        rai_update_sync_status( false, $response->get_error_message() );
        return $response;
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        $err = new WP_Error( 'rai_sync_failed', 'Unexpected status: ' . $code );
        rai_update_sync_status( false, 'HTTP ' . $code );
        return $err;
    }
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    if ( ! is_array( $data ) ) {
        $err = new WP_Error( 'rai_invalid_json', 'Invalid JSON response' );
        rai_update_sync_status( false, 'Invalid JSON' );
        return $err;
    }
    $count = 0;
    foreach ( $data as $prop ) {
        if ( empty( $prop['id'] ) ) { continue; }
        $backend_id = intval( $prop['id'] );
        $title = isset( $prop['title'] ) ? sanitize_text_field( $prop['title'] ) : 'Untitled Property';

        // Query by meta to find existing mapping
        $existing_query = new WP_Query([
            'post_type' => 'rai_property',
            'meta_key' => RAI_META_BACKEND_ID,
            'meta_value' => $backend_id,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
        ]);
        $existing_id = $existing_query->have_posts() ? $existing_query->posts[0] : 0;
        $post_data = [
            'post_type' => 'rai_property',
            'post_title' => $title,
            'post_status' => 'publish',
            'post_content' => isset( $prop['description'] ) ? wp_kses_post( $prop['description'] ) : '',
        ];
        if ( $existing_id ) {
            $post_data['ID'] = $existing_id;
            wp_update_post( $post_data );
        } else {
            $existing_id = wp_insert_post( $post_data );
            if ( $existing_id && ! is_wp_error( $existing_id ) ) {
                update_post_meta( $existing_id, RAI_META_BACKEND_ID, $backend_id );
            }
        }
        // Optional extra fields
        if ( isset( $prop['price'] ) ) {
            update_post_meta( $existing_id, '_rai_price', sanitize_text_field( $prop['price'] ) );
        }
        if ( isset( $prop['address'] ) ) {
            update_post_meta( $existing_id, '_rai_address', sanitize_text_field( $prop['address'] ) );
        }
        // Cover image handling (download & set featured image if URL provided)
        if ( ! empty( $prop['cover_image_url'] ) && filter_var( $prop['cover_image_url'], FILTER_VALIDATE_URL ) ) {
            $cover_url = esc_url_raw( $prop['cover_image_url'] );
            $prev_url = get_post_meta( $existing_id, '_rai_cover_image_url', true );
            if ( $cover_url !== $prev_url ) {
                // Lazy-load media libs
                if ( ! function_exists( 'media_handle_sideload' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }
                $tmp = download_url( $cover_url );
                if ( ! is_wp_error( $tmp ) ) {
                    $filename = wp_basename( parse_url( $cover_url, PHP_URL_PATH ) );
                    if ( ! $filename ) { $filename = 'rai-cover-' . time() . '.jpg'; }
                    $file = [
                        'name' => $filename,
                        'tmp_name' => $tmp,
                    ];
                    $attach_id = media_handle_sideload( $file, $existing_id );
                    if ( is_wp_error( $attach_id ) ) {
                        @unlink( $tmp );
                    } else {
                        set_post_thumbnail( $existing_id, $attach_id );
                        update_post_meta( $existing_id, '_rai_cover_image_url', $cover_url );
                    }
                }
            }
        }

        // Gallery images: download & attach (with caching by URL)
        if ( isset( $prop['images'] ) && is_array( $prop['images'] ) ) {
            $raw_urls = []; // sanitized ordered urls from backend
            foreach ( $prop['images'] as $img_url ) {
                if ( is_string( $img_url ) && filter_var( $img_url, FILTER_VALIDATE_URL ) ) {
                    $raw_urls[] = esc_url_raw( $img_url );
                }
            }
            update_post_meta( $existing_id, '_rai_gallery_urls', $raw_urls );

            if ( $raw_urls ) {
                if ( ! function_exists( 'media_handle_sideload' ) ) {
                    require_once ABSPATH . 'wp-admin/includes/media.php';
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                }
                $url_map = get_post_meta( $existing_id, '_rai_gallery_url_map', true );
                if ( ! is_array( $url_map ) ) { $url_map = []; }
                $attachment_ids = [];
                $processed = 0;
                foreach ( $raw_urls as $gurl ) {
                    $processed++;
                    if ( $processed > 25 ) { // safety limit
                        break;
                    }
                    if ( isset( $url_map[ $gurl ] ) && get_post( $url_map[ $gurl ] ) ) {
                        $attachment_ids[] = intval( $url_map[ $gurl ] );
                        continue;
                    }
                    $tmp = download_url( $gurl );
                    if ( is_wp_error( $tmp ) ) { continue; }
                    $filename = wp_basename( parse_url( $gurl, PHP_URL_PATH ) );
                    if ( ! $filename ) { $filename = 'rai-gallery-' . time() . '.jpg'; }
                    $file = [ 'name' => $filename, 'tmp_name' => $tmp ];
                    $attach_id = media_handle_sideload( $file, $existing_id );
                    if ( is_wp_error( $attach_id ) ) {
                        @unlink( $tmp );
                        continue;
                    }
                    $url_map[ $gurl ] = $attach_id;
                    $attachment_ids[] = $attach_id;
                }
                update_post_meta( $existing_id, '_rai_gallery_url_map', $url_map );
                update_post_meta( $existing_id, '_rai_gallery_attachment_ids', $attachment_ids );
            }
        }
        $count++;
        wp_reset_postdata();
    }
    rai_update_sync_status( true );
    return [ 'count' => $count ];
}

function rai_update_sync_status( $success, $error_message = '' ) {
    $status = get_option( RAI_STATUS_OPTION, [] );
    if ( $success ) {
        $status['last_success'] = time();
        unset( $status['last_error'] );
    } else {
        $status['last_error'] = $error_message;
    }
    update_option( RAI_STATUS_OPTION, $status );
}
