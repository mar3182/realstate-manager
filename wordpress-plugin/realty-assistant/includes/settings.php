<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rai_get_settings() {
    $defaults = [
        'api_base_url' => site_url('/api/v1'), // can override
        'auth_token' => '',
        'tenant_id' => '1',
    ];
    return wp_parse_args( get_option( RAI_OPTION_KEY, [] ), $defaults );
}

function rai_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset( $_POST['rai_settings_submit'] ) && check_admin_referer( 'rai_settings_save', 'rai_settings_nonce' ) ) {
        $settings = [
            'api_base_url' => esc_url_raw( $_POST['api_base_url'] ),
            'auth_token'   => sanitize_text_field( $_POST['auth_token'] ),
            'tenant_id'    => sanitize_text_field( $_POST['tenant_id'] ),
        ];
        update_option( RAI_OPTION_KEY, $settings );
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    $s = rai_get_settings();
    echo '<div class="wrap"><h1>Realty Assistant Settings</h1>';
    echo '<form method="post">';
    wp_nonce_field( 'rai_settings_save', 'rai_settings_nonce' );
    echo '<table class="form-table"><tbody>';
    echo '<tr><th><label for="api_base_url">API Base URL</label></th><td><input type="text" name="api_base_url" value="' . esc_attr( $s['api_base_url'] ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="auth_token">Auth Token (JWT)</label></th><td><input type="password" name="auth_token" value="' . esc_attr( $s['auth_token'] ) . '" class="regular-text" /></td></tr>';
    echo '<tr><th><label for="tenant_id">Tenant / Agency ID</label></th><td><input type="text" name="tenant_id" value="' . esc_attr( $s['tenant_id'] ) . '" class="small-text" /></td></tr>';
    echo '</tbody></table>';
    submit_button( 'Save Settings', 'primary', 'rai_settings_submit' );
    echo '</form></div>';
}
