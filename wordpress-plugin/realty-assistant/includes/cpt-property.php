<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rai_register_property_cpt() {
    $labels = [
        'name' => 'Properties',
        'singular_name' => 'Property',
    ];
    register_post_type( 'rai_property', [
        'label' => 'Properties',
        'labels' => $labels,
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title','editor','thumbnail','custom-fields'],
        'menu_icon' => 'dashicons-admin-home',
    ] );
}
add_action( 'init', 'rai_register_property_cpt' );
