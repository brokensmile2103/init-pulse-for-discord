<?php
/**
 * Plugin Name: Init Pulse For Discord
 * Plugin URI: https://inithtml.com/plugin/init-pulse-for-discord/
 * Description: Send notifications to Discord via webhooks. Minimal, fast, and ready for extension.
 * Version: 1.0
 * Author: Init HTML
 * Author URI: https://inithtml.com/
 * Text Domain: init-pulse-for-discord
 * Domain Path: /languages
 * Requires at least: 5.5
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// ===== CONSTANTS ===== //
define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_VERSION',      '1.0' );
define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SLUG',         'init-pulse-for-discord' );
define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_OPTION',       'init_plugin_suite_pulse_for_discord_settings' );

define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_URL',           plugin_dir_url( __FILE__ ) );
define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_PATH',          plugin_dir_path( __FILE__ ) );
define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_INCLUDES_PATH', INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_PATH . 'includes/' );

// ===== INCLUDE ===== //
require_once INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_INCLUDES_PATH . 'settings-page.php';
require_once INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_INCLUDES_PATH . 'taxonomy-discord-roles.php';
require_once INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_INCLUDES_PATH . 'webhook-dispatcher.php';

// ==========================
// Settings link
// ==========================

add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    'init_plugin_suite_pulse_for_discord_add_settings_link'
);

// Add a "Settings" link to the plugin row in the Plugins admin screen
function init_plugin_suite_pulse_for_discord_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=' . INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SLUG) . '">' . __('Settings', 'init-pulse-for-discord') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
