<?php
/**
 * Settings Page
 * Minimal blog-friendly fields (EN, i18n-ready, no textdomain loading here)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================================
// Admin Menu
// ==========================================================
add_action( 'admin_menu', function () {
    add_options_page(
        esc_html__( 'Init Pulse For Discord', 'init-pulse-for-discord' ),
        esc_html__( 'Init Pulse For Discord', 'init-pulse-for-discord' ),
        'manage_options',
        INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SLUG,
        'init_plugin_suite_pulse_for_discord_render_settings_page'
    );
});

// ==========================================================
// Register Settings
// ==========================================================
add_action( 'admin_init', function () {

    $group = INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_OPTION;

    $sanitize_bool = fn( $v ) => isset( $v ) ? '1' : '0';

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_enable', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_webhook_url', [
        'sanitize_callback' => 'esc_url_raw',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_username', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_avatar', [
        'sanitize_callback' => 'esc_url_raw',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_notify_new_post', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_notify_post_update', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_include_featured', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_image_size', [
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_message_template_post', [
        'sanitize_callback' => 'sanitize_textarea_field',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_timeout', [
        'sanitize_callback' => 'absint',
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_retry', [
        'sanitize_callback' => 'absint',
    ]);
});

// ==========================================================
// Render Page
// ==========================================================
function init_plugin_suite_pulse_for_discord_render_settings_page() {

    // Defaults
    $enabled      = get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' );

    $webhook      = get_option( 'init_plugin_suite_pulse_for_discord_webhook_url', '' );
    $username     = get_option( 'init_plugin_suite_pulse_for_discord_username', get_bloginfo( 'name' ) );
    $avatar       = get_option( 'init_plugin_suite_pulse_for_discord_avatar', '' );

    $notify_post   = get_option( 'init_plugin_suite_pulse_for_discord_notify_new_post', '1' );
    $notify_update = get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' );

    $include_feat = get_option( 'init_plugin_suite_pulse_for_discord_include_featured', '1' );
    $image_size   = get_option( 'init_plugin_suite_pulse_for_discord_image_size', 'full' );

    $template_post = get_option( 'init_plugin_suite_pulse_for_discord_message_template_post', "{title_url}\n— {site_name}" );

    $timeout      = absint( get_option( 'init_plugin_suite_pulse_for_discord_timeout', 8 ) );
    $retry        = absint( get_option( 'init_plugin_suite_pulse_for_discord_retry', 1 ) );

    ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Discord Notifications', 'init-pulse-for-discord' ); ?></h1>
        
        <form method="post" action="options.php">
            <?php settings_fields( INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_OPTION ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="init_plugin_suite_pulse_for_discord_enable"><?php esc_html_e( 'Enable Discord Notifications', 'init-pulse-for-discord' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_enable"
                                   id="init_plugin_suite_pulse_for_discord_enable"
                                   value="1" <?php checked( $enabled, '1' ); ?>>
                            <?php esc_html_e( 'Send notifications to Discord when content is published.', 'init-pulse-for-discord' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'This page configures options only. Hook the actual sending into publish/update events.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Events to Notify', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><?php esc_html_e( 'Notify On', 'init-pulse-for-discord' ); ?></th>
                    <td>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_notify_new_post"
                                   value="1" <?php checked( $notify_post, '1' ); ?>>
                            <?php esc_html_e( 'A new post is published (post type: post)', 'init-pulse-for-discord' ); ?>
                        </label>
                        <label style="display:block;">
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_notify_post_update"
                                   value="1" <?php checked( $notify_update, '1' ); ?>>
                            <?php esc_html_e( 'An existing post is updated (status remains publish)', 'init-pulse-for-discord' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Targets standard blog posts only. Custom post types can be added later via filters or extensions.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Webhook & Identity', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_webhook_url"><?php esc_html_e( 'Discord Webhook URL', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="url" class="regular-text ltr"
                               name="init_plugin_suite_pulse_for_discord_webhook_url"
                               id="init_plugin_suite_pulse_for_discord_webhook_url"
                               value="<?php echo esc_attr( $webhook ); ?>"
                               placeholder="https://discord.com/api/webhooks/XXX/YYY" />
                        <p class="description"><?php esc_html_e( 'Paste the full webhook URL from your Discord channel.', 'init-pulse-for-discord' ); ?></p>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_username"><?php esc_html_e( 'Default Username', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="text" class="regular-text"
                               name="init_plugin_suite_pulse_for_discord_username"
                               id="init_plugin_suite_pulse_for_discord_username"
                               value="<?php echo esc_attr( $username ); ?>" />
                        <p class="description"><?php esc_html_e( 'Shown as the sender name (e.g., "Init Bot").', 'init-pulse-for-discord' ); ?></p>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_avatar"><?php esc_html_e( 'Avatar URL', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="url" class="regular-text ltr"
                               name="init_plugin_suite_pulse_for_discord_avatar"
                               id="init_plugin_suite_pulse_for_discord_avatar"
                               value="<?php echo esc_attr( $avatar ); ?>"
                               placeholder="https://example.com/avatar.png" />
                        <p class="description"><?php esc_html_e( 'Public image URL used as the webhook avatar (optional).', 'init-pulse-for-discord' ); ?></p>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Content & Media', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_include_featured"><?php esc_html_e( 'Include Featured Image', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_include_featured"
                                   id="init_plugin_suite_pulse_for_discord_include_featured"
                                   value="1" <?php checked( $include_feat, '1' ); ?>>
                            <?php esc_html_e( 'Attach the featured image to the Discord embed (if available).', 'init-pulse-for-discord' ); ?>
                        </label>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_image_size"><?php esc_html_e( 'Image Size', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <select name="init_plugin_suite_pulse_for_discord_image_size" id="init_plugin_suite_pulse_for_discord_image_size">
                            <?php foreach ( array( 'full','large','medium','thumbnail' ) as $sz ): ?>
                                <option value="<?php echo esc_attr( $sz ); ?>" <?php selected( $image_size, $sz ); ?>>
                                    <?php echo esc_html( ucfirst( $sz ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Message Template', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_message_template_post"><?php esc_html_e( 'New/Updated Post Template', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <textarea name="init_plugin_suite_pulse_for_discord_message_template_post"
                                  id="init_plugin_suite_pulse_for_discord_message_template_post"
                                  class="large-text code" rows="5"><?php
                            echo esc_textarea( $template_post );
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Available placeholders:', 'init-pulse-for-discord' ); ?><br>
                            <code>{title}</code> – <?php esc_html_e( 'Post title', 'init-pulse-for-discord' ); ?><br>
                            <code>{title_url}</code> – <?php esc_html_e( 'Post title linked to URL', 'init-pulse-for-discord' ); ?><br>
                            <code>{url}</code> – <?php esc_html_e( 'Post URL', 'init-pulse-for-discord' ); ?><br>
                            <code>{excerpt}</code> – <?php esc_html_e( 'Post excerpt (trimmed)', 'init-pulse-for-discord' ); ?><br>
                            <code>{site_name}</code> – <?php esc_html_e( 'Your site name', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Reliability & Safety', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_timeout"><?php esc_html_e( 'Request Timeout (seconds)', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="number" min="1" step="1"
                               name="init_plugin_suite_pulse_for_discord_timeout"
                               id="init_plugin_suite_pulse_for_discord_timeout"
                               value="<?php echo esc_attr( $timeout ); ?>">
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_retry"><?php esc_html_e( 'Retry Attempts', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="number" min="0" step="1"
                               name="init_plugin_suite_pulse_for_discord_retry"
                               id="init_plugin_suite_pulse_for_discord_retry"
                               value="<?php echo esc_attr( $retry ); ?>">
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <?php
}
