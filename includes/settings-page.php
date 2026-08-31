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
// Sanitize callbacks
// ==========================================================

// Keep only valid, currently-selectable post type slugs.
function init_plugin_suite_pulse_for_discord_sanitize_post_types( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }

    $valid = array_keys( init_plugin_suite_pulse_for_discord_get_selectable_post_types() );
    $clean = array();

    foreach ( $value as $post_type ) {
        $post_type = sanitize_key( $post_type );
        if ( in_array( $post_type, $valid, true ) ) {
            $clean[] = $post_type;
        }
    }

    return array_values( array_unique( $clean ) );
}

// Fall back to the Discord brand color when an invalid/empty hex is submitted.
function init_plugin_suite_pulse_for_discord_sanitize_color( $value ) {
    $color = sanitize_hex_color( $value );
    return $color ? $color : '#5865F2';
}

// ==========================================================
// Register Settings
// ==========================================================
add_action( 'admin_init', function () {

    $group = INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_OPTION;

    $sanitize_bool = fn( $v ) => ( isset( $v ) && $v === '1' ) ? '1' : '0';

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_enable', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_post_types', [
        'type'              => 'array',
        'sanitize_callback' => 'init_plugin_suite_pulse_for_discord_sanitize_post_types',
        'default'           => array( 'post' ),
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

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_enable_rich_embed', [
        'sanitize_callback' => $sanitize_bool,
    ]);

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_embed_color', [
        'sanitize_callback' => 'init_plugin_suite_pulse_for_discord_sanitize_color',
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
// Assets (settings screen only)
// ==========================================================
add_action( 'admin_enqueue_scripts', function ( $hook ) {

    if ( 'settings_page_' . INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SLUG !== $hook ) {
        return;
    }

    // No standalone file needed for a handful of lines of vanilla JS: register a
    // sourceless handle and attach the code via wp_add_inline_script() instead.
    wp_register_script( 'init-plugin-suite-pulse-for-discord-admin', false, array(), INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_VERSION, true );
    wp_enqueue_script( 'init-plugin-suite-pulse-for-discord-admin' );

    wp_localize_script( 'init-plugin-suite-pulse-for-discord-admin', 'initPulseForDiscord', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'init_plugin_suite_pulse_for_discord_test' ),
        'i18n'    => array(
            'sending' => __( 'Sending…', 'init-pulse-for-discord' ),
            'send'    => __( 'Send Test Message', 'init-pulse-for-discord' ),
        ),
    ) );

    $inline_js = <<<'JS'
document.addEventListener( 'DOMContentLoaded', function () {
	var btn = document.getElementById( 'init-pulse-for-discord-test-btn' );
	if ( ! btn ) {
		return;
	}
	var result = document.getElementById( 'init-pulse-for-discord-test-result' );

	btn.addEventListener( 'click', function ( event ) {
		event.preventDefault();

		var webhookEl  = document.getElementById( 'init_plugin_suite_pulse_for_discord_webhook_url' );
		var usernameEl = document.getElementById( 'init_plugin_suite_pulse_for_discord_username' );
		var avatarEl   = document.getElementById( 'init_plugin_suite_pulse_for_discord_avatar' );
		var richEl     = document.getElementById( 'init_plugin_suite_pulse_for_discord_enable_rich_embed' );
		var colorEl    = document.getElementById( 'init_plugin_suite_pulse_for_discord_embed_color' );

		var params = new URLSearchParams();
		params.append( 'action', 'init_plugin_suite_pulse_for_discord_test' );
		params.append( 'nonce', initPulseForDiscord.nonce );
		params.append( 'webhook', webhookEl ? webhookEl.value : '' );
		params.append( 'username', usernameEl ? usernameEl.value : '' );
		params.append( 'avatar', avatarEl ? avatarEl.value : '' );
		params.append( 'rich', richEl && richEl.checked ? '1' : '0' );
		params.append( 'color', colorEl ? colorEl.value : '#5865F2' );

		btn.disabled = true;
		var originalLabel = btn.textContent;
		btn.textContent = initPulseForDiscord.i18n.sending;
		if ( result ) {
			result.textContent = '';
		}

		fetch( initPulseForDiscord.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString()
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( data ) {
				if ( ! result ) {
					return;
				}
				result.textContent = data && data.data && data.data.message ? data.data.message : '';
				result.style.color = data && data.success ? '#0a7d2c' : '#b32d2e';
			} )
			.catch( function () {
				if ( result ) {
					result.textContent = initPulseForDiscord.i18n.send;
				}
			} )
			.finally( function () {
				btn.disabled = false;
				btn.textContent = originalLabel;
			} );
	} );
} );
JS;

    wp_add_inline_script( 'init-plugin-suite-pulse-for-discord-admin', $inline_js );
});

// ==========================================================
// Render Page
// ==========================================================
function init_plugin_suite_pulse_for_discord_render_settings_page() {

    // Defaults
    $enabled      = get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' );
    $post_types   = get_option( 'init_plugin_suite_pulse_for_discord_post_types', array( 'post' ) );
    if ( ! is_array( $post_types ) ) {
        $post_types = array( 'post' );
    }

    $webhook      = get_option( 'init_plugin_suite_pulse_for_discord_webhook_url', '' );
    $username     = get_option( 'init_plugin_suite_pulse_for_discord_username', get_bloginfo( 'name' ) );
    $avatar       = get_option( 'init_plugin_suite_pulse_for_discord_avatar', '' );

    $notify_post   = get_option( 'init_plugin_suite_pulse_for_discord_notify_new_post', '1' );
    $notify_update = get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' );

    $include_feat = get_option( 'init_plugin_suite_pulse_for_discord_include_featured', '1' );
    $image_size   = get_option( 'init_plugin_suite_pulse_for_discord_image_size', 'full' );

    $rich_embed   = get_option( 'init_plugin_suite_pulse_for_discord_enable_rich_embed', '1' );
    $embed_color  = get_option( 'init_plugin_suite_pulse_for_discord_embed_color', '#5865F2' );

    $template_post = get_option( 'init_plugin_suite_pulse_for_discord_message_template_post', "{title_url}\n— {site_name}" );

    $timeout      = absint( get_option( 'init_plugin_suite_pulse_for_discord_timeout', 8 ) );
    $retry        = absint( get_option( 'init_plugin_suite_pulse_for_discord_retry', 1 ) );

    $view_count_active   = init_plugin_suite_pulse_for_discord_view_count_active();
    $notify_milestone    = get_option( 'init_plugin_suite_pulse_for_discord_notify_milestone', '0' );
    $milestone_thresholds = get_option( 'init_plugin_suite_pulse_for_discord_milestone_thresholds', '1000, 5000, 10000, 50000, 100000' );
    $template_milestone   = get_option( 'init_plugin_suite_pulse_for_discord_message_template_milestone', "🔥 {title_url}\n**{views_short}** views and counting — {site_name}" );

    $selectable_post_types = init_plugin_suite_pulse_for_discord_get_selectable_post_types();

    // Read-only display flag only; the actual Clear Log action is nonce-verified
    // in includes/delivery-log.php before this redirect ever happens.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['log_cleared'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Delivery log cleared.', 'init-pulse-for-discord' ) . '</p></div>';
    }

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

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Post Types', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><?php esc_html_e( 'Notify These Post Types', 'init-pulse-for-discord' ); ?></th>
                    <td>
                        <?php foreach ( $selectable_post_types as $post_type ) : ?>
                            <label style="display:block;margin-bottom:6px;">
                                <input type="checkbox"
                                       name="init_plugin_suite_pulse_for_discord_post_types[]"
                                       value="<?php echo esc_attr( $post_type->name ); ?>"
                                       <?php checked( in_array( $post_type->name, $post_types, true ) ); ?>>
                                <?php echo esc_html( $post_type->labels->singular_name ); ?>
                                <code><?php echo esc_html( $post_type->name ); ?></code>
                            </label>
                        <?php endforeach; ?>
                        <p class="description">
                            <?php esc_html_e( 'Choose which post types should trigger a Discord notification. Custom post types are supported.', 'init-pulse-for-discord' ); ?>
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
                            <?php esc_html_e( 'A tracked item is published', 'init-pulse-for-discord' ); ?>
                        </label>
                        <label style="display:block;">
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_notify_post_update"
                                   value="1" <?php checked( $notify_update, '1' ); ?>>
                            <?php esc_html_e( 'An existing item is updated (status remains publish)', 'init-pulse-for-discord' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Applies to whichever post types are selected above.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Hot Post Milestones', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <?php if ( ! $view_count_active ) : ?>
                    <tr class="idh-dependent">
                        <th colspan="2">
                            <div class="notice notice-warning inline" style="margin:0 0 12px;">
                                <p>
                                    <?php
                                    $init_pulse_vc_notice = sprintf(
                                        /* translators: %s: link to the Init View Count plugin page. */
                                        __( 'This feature requires the %s plugin to be active on this site.', 'init-pulse-for-discord' ),
                                        '<a href="https://wordpress.org/plugins/init-view-count/" target="_blank" rel="noopener noreferrer">Init View Count</a>'
                                    );
                                    echo wp_kses(
                                        $init_pulse_vc_notice,
                                        array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) )
                                    );
                                    ?>
                                    <?php esc_html_e( 'You can still configure the fields below now — they will start working automatically as soon as Init View Count is installed and activated, no need to re-save.', 'init-pulse-for-discord' ); ?>
                                </p>
                            </div>
                        </th>
                    </tr>
                <?php endif; ?>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_notify_milestone"><?php esc_html_e( 'Notify on View Milestones', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_notify_milestone"
                                   id="init_plugin_suite_pulse_for_discord_notify_milestone"
                                   value="1" <?php checked( $notify_milestone, '1' ); ?>>
                            <?php esc_html_e( 'Send a Discord notification when a tracked post\'s total view count (from Init View Count) crosses one of the milestones below.', 'init-pulse-for-discord' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Uses the same post types selected in "Post Types" above. Each milestone is only ever sent once per post.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_milestone_thresholds"><?php esc_html_e( 'View Milestones', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="text" class="regular-text ltr"
                               name="init_plugin_suite_pulse_for_discord_milestone_thresholds"
                               id="init_plugin_suite_pulse_for_discord_milestone_thresholds"
                               value="<?php echo esc_attr( $milestone_thresholds ); ?>"
                               placeholder="1000, 5000, 10000, 50000, 100000" />
                        <p class="description">
                            <?php esc_html_e( 'Comma-separated view counts. A notification fires once when total views reach or pass each one, in ascending order.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_message_template_milestone"><?php esc_html_e( 'Milestone Message Template', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <textarea name="init_plugin_suite_pulse_for_discord_message_template_milestone"
                                  id="init_plugin_suite_pulse_for_discord_message_template_milestone"
                                  class="large-text code" rows="5"><?php
                            echo esc_textarea( $template_milestone );
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Uses the same placeholders as the post template above, plus:', 'init-pulse-for-discord' ); ?><br>
                            <code>{views}</code> – <?php esc_html_e( 'Current total views, formatted (e.g. 12,345)', 'init-pulse-for-discord' ); ?><br>
                            <code>{views_short}</code> – <?php esc_html_e( 'Current total views, abbreviated (e.g. 12.3K)', 'init-pulse-for-discord' ); ?><br>
                            <code>{milestone}</code> – <?php esc_html_e( 'The milestone that was just crossed', 'init-pulse-for-discord' ); ?>
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
                        <button type="button" class="button" id="init-pulse-for-discord-test-btn">
                            <?php esc_html_e( 'Send Test Message', 'init-pulse-for-discord' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Paste the full webhook URL from your Discord channel.', 'init-pulse-for-discord' ); ?></p>
                        <p><span id="init-pulse-for-discord-test-result"></span></p>
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

                <tr class="idh-dependent"><th colspan="2"><h2><?php esc_html_e( 'Embed Style', 'init-pulse-for-discord' ); ?></h2></th></tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_enable_rich_embed"><?php esc_html_e( 'Use Rich Embed', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="init_plugin_suite_pulse_for_discord_enable_rich_embed"
                                   id="init_plugin_suite_pulse_for_discord_enable_rich_embed"
                                   value="1" <?php checked( $rich_embed, '1' ); ?>>
                            <?php esc_html_e( 'Send a formatted embed (title, description, color, footer) instead of plain text.', 'init-pulse-for-discord' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When disabled, the plugin falls back to the plain-text format used before version 1.1.', 'init-pulse-for-discord' ); ?>
                        </p>
                    </td>
                </tr>

                <tr class="idh-dependent">
                    <th scope="row"><label for="init_plugin_suite_pulse_for_discord_embed_color"><?php esc_html_e( 'Embed Color', 'init-pulse-for-discord' ); ?></label></th>
                    <td>
                        <input type="color"
                               name="init_plugin_suite_pulse_for_discord_embed_color"
                               id="init_plugin_suite_pulse_for_discord_embed_color"
                               value="<?php echo esc_attr( $embed_color ? $embed_color : '#5865F2' ); ?>">
                        <p class="description"><?php esc_html_e( 'Accent color shown on the left edge of the embed.', 'init-pulse-for-discord' ); ?></p>
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
                            <?php esc_html_e( 'Used as the embed description when Rich Embed is enabled, or as the full message otherwise.', 'init-pulse-for-discord' ); ?><br>
                            <?php esc_html_e( 'Available placeholders:', 'init-pulse-for-discord' ); ?><br>
                            <code>{title}</code> – <?php esc_html_e( 'Post title', 'init-pulse-for-discord' ); ?><br>
                            <code>{title_url}</code> – <?php esc_html_e( 'Post title linked to URL', 'init-pulse-for-discord' ); ?><br>
                            <code>{url}</code> – <?php esc_html_e( 'Post URL', 'init-pulse-for-discord' ); ?><br>
                            <code>{excerpt}</code> – <?php esc_html_e( 'Post excerpt (trimmed)', 'init-pulse-for-discord' ); ?><br>
                            <code>{site_name}</code> – <?php esc_html_e( 'Your site name', 'init-pulse-for-discord' ); ?><br>
                            <code>{author}</code> – <?php esc_html_e( 'Post author display name', 'init-pulse-for-discord' ); ?><br>
                            <code>{categories}</code> – <?php esc_html_e( 'Comma-separated category names', 'init-pulse-for-discord' ); ?><br>
                            <code>{tags}</code> – <?php esc_html_e( 'Comma-separated tag names', 'init-pulse-for-discord' ); ?><br>
                            <code>{post_type}</code> – <?php esc_html_e( 'Post type label', 'init-pulse-for-discord' ); ?><br>
                            <code>{date}</code> – <?php esc_html_e( 'Published date (site format)', 'init-pulse-for-discord' ); ?>
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

        <hr>

        <h2><?php esc_html_e( 'Delivery Log', 'init-pulse-for-discord' ); ?></h2>
        <p class="description">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %d: maximum number of stored log entries. */
                    __( 'Shows the last %d webhook deliveries (newest first) for troubleshooting.', 'init-pulse-for-discord' ),
                    INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_LOG_MAX
                )
            );
            ?>
        </p>

        <?php
        $log = array_reverse( init_plugin_suite_pulse_for_discord_get_log() );

        if ( empty( $log ) ) :
            ?>
            <p><?php esc_html_e( 'No deliveries yet.', 'init-pulse-for-discord' ); ?></p>
            <?php
        else :
            ?>
            <table class="widefat striped" style="max-width:900px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Time', 'init-pulse-for-discord' ); ?></th>
                        <th><?php esc_html_e( 'Item', 'init-pulse-for-discord' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'init-pulse-for-discord' ); ?></th>
                        <th><?php esc_html_e( 'Detail', 'init-pulse-for-discord' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $log as $entry ) : ?>
                        <tr>
                            <td><?php echo esc_html( isset( $entry['time'] ) ? $entry['time'] : '' ); ?></td>
                            <td><?php echo esc_html( isset( $entry['title'] ) ? $entry['title'] : '' ); ?></td>
                            <td>
                                <?php if ( isset( $entry['status'] ) && 'success' === $entry['status'] ) : ?>
                                    <span style="color:#0a7d2c;">● <?php esc_html_e( 'Success', 'init-pulse-for-discord' ); ?></span>
                                <?php else : ?>
                                    <span style="color:#b32d2e;">● <?php esc_html_e( 'Error', 'init-pulse-for-discord' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( isset( $entry['message'] ) ? $entry['message'] : '' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px;">
                <input type="hidden" name="action" value="init_plugin_suite_pulse_for_discord_clear_log">
                <?php wp_nonce_field( 'init_plugin_suite_pulse_for_discord_clear_log' ); ?>
                <?php submit_button( __( 'Clear Log', 'init-pulse-for-discord' ), 'secondary', 'submit', false ); ?>
            </form>
            <?php
        endif;
        ?>
    </div>

    <?php
}
