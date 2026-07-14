<?php
/**
 * AJAX: Send Test Message
 *
 * Lets an admin verify a webhook URL directly from the settings screen,
 * using whatever values are currently in the form (no need to save first).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_init_plugin_suite_pulse_for_discord_test', 'init_plugin_suite_pulse_for_discord_ajax_test' );

function init_plugin_suite_pulse_for_discord_ajax_test() {

    check_ajax_referer( 'init_plugin_suite_pulse_for_discord_test', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'init-pulse-for-discord' ) ), 403 );
    }

    $webhook  = isset( $_POST['webhook'] ) ? esc_url_raw( wp_unslash( $_POST['webhook'] ) ) : '';
    $username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
    $avatar   = isset( $_POST['avatar'] ) ? esc_url_raw( wp_unslash( $_POST['avatar'] ) ) : '';
    $color    = isset( $_POST['color'] ) ? sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '#5865F2';
    $rich     = isset( $_POST['rich'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['rich'] ) );

    if ( empty( $webhook ) ) {
        wp_send_json_error( array( 'message' => __( 'Please enter a webhook URL first.', 'init-pulse-for-discord' ) ) );
    }

    $host = wp_parse_url( $webhook, PHP_URL_HOST );
    if ( ! $host || ! preg_match( '/(^|\.)discord(app)?\.com$/i', $host ) ) {
        wp_send_json_error( array( 'message' => __( 'This does not look like a valid Discord webhook URL.', 'init-pulse-for-discord' ) ) );
    }

    $site_name = get_bloginfo( 'name' );
    $username  = $username ? $username : $site_name;
    $color     = $color ? $color : '#5865F2';

    if ( $rich ) {
        $payload = array(
            'content'  => '',
            'username' => $username,
            'embeds'   => array(
                array(
                    'type'        => 'rich',
                    'title'       => __( 'Test message', 'init-pulse-for-discord' ),
                    'description' => sprintf(
                        /* translators: %s: site name. */
                        __( 'Init Pulse For Discord is correctly configured on %s.', 'init-pulse-for-discord' ),
                        $site_name
                    ),
                    'color'       => hexdec( ltrim( $color, '#' ) ),
                    'footer'      => array( 'text' => $site_name ),
                    'timestamp'   => gmdate( 'c' ),
                ),
            ),
        );
    } else {
        $payload = array(
            'content'  => sprintf(
                /* translators: %s: site name. */
                __( '✅ Test message from %s — Init Pulse For Discord is configured correctly.', 'init-pulse-for-discord' ),
                $site_name
            ),
            'username' => $username,
        );
    }

    if ( ! empty( $avatar ) ) {
        $payload['avatar_url'] = $avatar;
    }

    $result = init_plugin_suite_pulse_for_discord_send_webhook( $webhook, $payload, 8, 0 );

    init_plugin_suite_pulse_for_discord_add_log_entry( array(
        'context' => 'test',
        'post_id' => 0,
        'title'   => __( 'Test message', 'init-pulse-for-discord' ),
        'status'  => is_wp_error( $result ) ? 'error' : 'success',
        'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Delivered', 'init-pulse-for-discord' ),
    ) );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( array( 'message' => __( 'Test message sent! Check your Discord channel.', 'init-pulse-for-discord' ) ) );
}
