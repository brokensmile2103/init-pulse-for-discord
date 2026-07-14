<?php
/**
 * Delivery Log
 *
 * Stores a rolling snapshot of the most recent webhook deliveries in a single
 * wp_options row (autoload = no) so admins can troubleshoot failed sends
 * without needing a custom database table.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_OPTION' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_OPTION', 'init_plugin_suite_pulse_for_discord_log' );
}

// Rolling snapshot size: enough for troubleshooting, small enough to stay light in wp_options.
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_MAX' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_MAX', 20 );
}

/**
 * Retrieve the stored delivery log, oldest entry first.
 *
 * @return array[]
 */
function init_plugin_suite_pulse_for_discord_get_log() {
    $log = get_option( INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_OPTION, array() );
    return is_array( $log ) ? $log : array();
}

/**
 * Append an entry to the delivery log and trim it to the configured max size.
 *
 * @param array $entry {
 *     @type string $context  Event context: publish, update, test.
 *     @type int    $post_id  Related post ID (0 for test messages).
 *     @type string $title    Human-readable label for the entry.
 *     @type string $status   'success' or 'error'.
 *     @type string $message  Extra detail (e.g. HTTP code or error message).
 * }
 */
function init_plugin_suite_pulse_for_discord_add_log_entry( $entry ) {
    $log = init_plugin_suite_pulse_for_discord_get_log();

    $log[] = array_merge(
        array(
            'time'    => current_time( 'mysql' ),
            'context' => '',
            'post_id' => 0,
            'title'   => '',
            'status'  => 'error',
            'message' => '',
        ),
        $entry
    );

    // Keep only the most recent N entries.
    if ( count( $log ) > INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_MAX ) {
        $log = array_slice( $log, -1 * INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_MAX );
    }

    // autoload = no: this data is only ever read on the plugin's own settings screen.
    update_option( INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_OPTION, $log, false );
}

/**
 * Clear the delivery log.
 */
function init_plugin_suite_pulse_for_discord_clear_log() {
    delete_option( INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_OPTION );
}

/**
 * Handle the "Clear Log" admin action (admin-post.php).
 */
add_action( 'admin_post_init_plugin_suite_pulse_for_discord_clear_log', function () {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You are not allowed to do this.', 'init-pulse-for-discord' ) );
    }

    check_admin_referer( 'init_plugin_suite_pulse_for_discord_clear_log' );

    init_plugin_suite_pulse_for_discord_clear_log();

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'        => INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SLUG,
                'log_cleared' => '1',
            ),
            admin_url( 'options-general.php' )
        )
    );
    exit;
} );
