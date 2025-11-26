<?php
/**
 * Uninstall – Init Pulse for Discord
 *
 * Permanently remove plugin data from the database:
 * - Plugin options (settings page)
 * - Term meta (Discord Role IDs)
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * 1. Delete plugin options (settings page values)
 */
$options = array(
    'init_plugin_suite_pulse_for_discord_enable',
    'init_plugin_suite_pulse_for_discord_webhook_url',
    'init_plugin_suite_pulse_for_discord_username',
    'init_plugin_suite_pulse_for_discord_avatar',
    'init_plugin_suite_pulse_for_discord_notify_new_post',
    'init_plugin_suite_pulse_for_discord_notify_post_update',
    'init_plugin_suite_pulse_for_discord_include_featured',
    'init_plugin_suite_pulse_for_discord_image_size',
    'init_plugin_suite_pulse_for_discord_message_template_post',
    'init_plugin_suite_pulse_for_discord_timeout',
    'init_plugin_suite_pulse_for_discord_retry',
);

foreach ( $options as $option ) {
    delete_option( $option );
    delete_site_option( $option ); // For multisite
}

/**
 * 2. Remove taxonomy term meta (Discord Role IDs)
 * Using delete_metadata() avoids direct SQL queries (passes WP Plugin Check)
 */
$meta_keys = array(
    'init_plugin_suite_pulse_for_discord_role_id',
    'init_plugin_suite_pulse_for_discord_all_role_id',
);

foreach ( $meta_keys as $key ) {
    delete_metadata( 'term', 0, $key, '', true );
}
