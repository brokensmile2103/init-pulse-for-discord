<?php
/**
 * Taxonomy Discord Role IDs & Per-Term Webhook
 * Adds Role ID fields, and an optional dedicated Discord Webhook (with its
 * own identity + "exclusive" routing), to categories and tags (blog scope).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Term meta keys (INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_*_META) are
// defined in includes/webhook-dispatcher.php, which is always loaded first
// (see init-pulse-for-discord.php) and consumes them when resolving which
// webhook(s) a post should be sent to.

add_action( 'admin_init', function () {
    // Only show if plugin is enabled
    if ( get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' ) !== '1' ) {
        return;
    }

    $taxonomies = array( 'category', 'post_tag' );

    foreach ( $taxonomies as $tax ) {

        /**
         * CREATE — add fields when creating a new term
         */
        add_action( "{$tax}_add_form_fields", function () {
            ?>
            <div class="form-field term-discord-role-wrap">
                <label for="init_plugin_suite_pulse_for_discord_role_id"><?php esc_html_e( 'Discord Role ID', 'init-pulse-for-discord' ); ?></label>
                <input type="text"
                       name="init_plugin_suite_pulse_for_discord_role_id"
                       id="init_plugin_suite_pulse_for_discord_role_id"
                       value=""
                       placeholder="<?php echo esc_attr__( 'Enter Role ID for this term', 'init-pulse-for-discord' ); ?>">
                <p class="description">
                    <?php esc_html_e( 'Used to mention a specific role when posts in this term are notified.', 'init-pulse-for-discord' ); ?>
                </p>
            </div>

            <div class="form-field term-discord-role-all-wrap">
                <label for="init_plugin_suite_pulse_for_discord_all_role_id"><?php esc_html_e( 'Discord Role ID (all-posts role)', 'init-pulse-for-discord' ); ?></label>
                <input type="text"
                       name="init_plugin_suite_pulse_for_discord_all_role_id"
                       id="init_plugin_suite_pulse_for_discord_all_role_id"
                       value=""
                       placeholder="<?php echo esc_attr__( 'Enter Role ID for all-posts notifications (optional)', 'init-pulse-for-discord' ); ?>">
                <p class="description">
                    <?php esc_html_e( 'Optional: mention a broader role subscribed to all updates.', 'init-pulse-for-discord' ); ?>
                </p>
            </div>

            <div class="form-field term-discord-webhook-wrap">
                <label for="init_plugin_suite_pulse_for_discord_term_webhook_url"><?php esc_html_e( 'Discord Webhook URL (this term only)', 'init-pulse-for-discord' ); ?></label>
                <input type="url"
                       name="init_plugin_suite_pulse_for_discord_term_webhook_url"
                       id="init_plugin_suite_pulse_for_discord_term_webhook_url"
                       value=""
                       placeholder="https://discord.com/api/webhooks/XXX/YYY">
                <p class="description">
                    <?php esc_html_e( 'Optional: send posts in this term to a dedicated Discord webhook instead of (or in addition to) the global one.', 'init-pulse-for-discord' ); ?>
                </p>
            </div>

            <div class="form-field term-discord-webhook-username-wrap">
                <label for="init_plugin_suite_pulse_for_discord_term_username"><?php esc_html_e( 'Username (this term only)', 'init-pulse-for-discord' ); ?></label>
                <input type="text"
                       name="init_plugin_suite_pulse_for_discord_term_username"
                       id="init_plugin_suite_pulse_for_discord_term_username"
                       value=""
                       placeholder="<?php echo esc_attr__( 'Leave blank to use the global Default Username', 'init-pulse-for-discord' ); ?>">
            </div>

            <div class="form-field term-discord-webhook-avatar-wrap">
                <label for="init_plugin_suite_pulse_for_discord_term_avatar"><?php esc_html_e( 'Avatar URL (this term only)', 'init-pulse-for-discord' ); ?></label>
                <input type="url"
                       name="init_plugin_suite_pulse_for_discord_term_avatar"
                       id="init_plugin_suite_pulse_for_discord_term_avatar"
                       value=""
                       placeholder="<?php echo esc_attr__( 'Leave blank to use the global Avatar URL', 'init-pulse-for-discord' ); ?>">
            </div>

            <div class="form-field term-discord-webhook-exclusive-wrap">
                <label>
                    <input type="checkbox"
                           name="init_plugin_suite_pulse_for_discord_term_webhook_exclusive"
                           id="init_plugin_suite_pulse_for_discord_term_webhook_exclusive"
                           value="1">
                    <?php esc_html_e( 'Only send to this webhook', 'init-pulse-for-discord' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'When enabled, posts in this term skip the global webhook and are sent only to the webhook above.', 'init-pulse-for-discord' ); ?>
                </p>
            </div>
            <?php
            wp_nonce_field( 'init_plugin_suite_pulse_for_discord_save_term_roles', 'init_plugin_suite_pulse_for_discord_term_roles_nonce' );
        });

        /**
         * EDIT — display fields when editing term
         */
        add_action( "{$tax}_edit_form_fields", function ( $term ) {
            $role_id     = get_term_meta( $term->term_id, 'init_plugin_suite_pulse_for_discord_role_id', true );
            $all_role_id = get_term_meta( $term->term_id, 'init_plugin_suite_pulse_for_discord_all_role_id', true );
            ?>
            <tr class="form-field term-discord-role-wrap">
                <th scope="row">
                    <label for="init_plugin_suite_pulse_for_discord_role_id"><?php esc_html_e( 'Discord Role ID', 'init-pulse-for-discord' ); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="init_plugin_suite_pulse_for_discord_role_id"
                           id="init_plugin_suite_pulse_for_discord_role_id"
                           value="<?php echo esc_attr( $role_id ); ?>"
                           class="regular-text"
                           placeholder="<?php echo esc_attr__( 'Enter Role ID for this term', 'init-pulse-for-discord' ); ?>">
                    <p class="description">
                        <?php esc_html_e( 'Used to mention a specific role when posts in this term are notified.', 'init-pulse-for-discord' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="form-field term-discord-role-all-wrap">
                <th scope="row">
                    <label for="init_plugin_suite_pulse_for_discord_all_role_id"><?php esc_html_e( 'Discord Role ID (all-posts role)', 'init-pulse-for-discord' ); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="init_plugin_suite_pulse_for_discord_all_role_id"
                           id="init_plugin_suite_pulse_for_discord_all_role_id"
                           value="<?php echo esc_attr( $all_role_id ); ?>"
                           class="regular-text"
                           placeholder="<?php echo esc_attr__( 'Enter Role ID for all-posts notifications (optional)', 'init-pulse-for-discord' ); ?>">
                    <p class="description">
                        <?php esc_html_e( 'Optional: mention a broader role subscribed to all updates.', 'init-pulse-for-discord' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="form-field term-discord-webhook-wrap">
                <th scope="row">
                    <label for="init_plugin_suite_pulse_for_discord_term_webhook_url"><?php esc_html_e( 'Discord Webhook URL (this term only)', 'init-pulse-for-discord' ); ?></label>
                </th>
                <td>
                    <?php
                    $term_webhook   = get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META, true );
                    $term_username  = get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META, true );
                    $term_avatar    = get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META, true );
                    $term_exclusive = get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META, true );
                    ?>
                    <input type="url"
                           name="init_plugin_suite_pulse_for_discord_term_webhook_url"
                           id="init_plugin_suite_pulse_for_discord_term_webhook_url"
                           value="<?php echo esc_attr( $term_webhook ); ?>"
                           class="regular-text ltr"
                           placeholder="https://discord.com/api/webhooks/XXX/YYY">
                    <p class="description">
                        <?php esc_html_e( 'Optional: send posts in this term to a dedicated Discord webhook instead of (or in addition to) the global one.', 'init-pulse-for-discord' ); ?>
                    </p>
                </td>
            </tr>

            <tr class="form-field term-discord-webhook-username-wrap">
                <th scope="row">
                    <label for="init_plugin_suite_pulse_for_discord_term_username"><?php esc_html_e( 'Username (this term only)', 'init-pulse-for-discord' ); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="init_plugin_suite_pulse_for_discord_term_username"
                           id="init_plugin_suite_pulse_for_discord_term_username"
                           value="<?php echo esc_attr( $term_username ); ?>"
                           class="regular-text"
                           placeholder="<?php echo esc_attr__( 'Leave blank to use the global Default Username', 'init-pulse-for-discord' ); ?>">
                </td>
            </tr>

            <tr class="form-field term-discord-webhook-avatar-wrap">
                <th scope="row">
                    <label for="init_plugin_suite_pulse_for_discord_term_avatar"><?php esc_html_e( 'Avatar URL (this term only)', 'init-pulse-for-discord' ); ?></label>
                </th>
                <td>
                    <input type="url"
                           name="init_plugin_suite_pulse_for_discord_term_avatar"
                           id="init_plugin_suite_pulse_for_discord_term_avatar"
                           value="<?php echo esc_attr( $term_avatar ); ?>"
                           class="regular-text ltr"
                           placeholder="<?php echo esc_attr__( 'Leave blank to use the global Avatar URL', 'init-pulse-for-discord' ); ?>">
                </td>
            </tr>

            <tr class="form-field term-discord-webhook-exclusive-wrap">
                <th scope="row"><?php esc_html_e( 'Only send to this webhook', 'init-pulse-for-discord' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="init_plugin_suite_pulse_for_discord_term_webhook_exclusive"
                               id="init_plugin_suite_pulse_for_discord_term_webhook_exclusive"
                               value="1" <?php checked( $term_exclusive, '1' ); ?>>
                        <?php esc_html_e( 'Skip the global webhook for posts in this term.', 'init-pulse-for-discord' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'When enabled, posts in this term skip the global webhook and are sent only to the webhook above.', 'init-pulse-for-discord' ); ?>
                    </p>
                </td>
            </tr>
            <?php
            wp_nonce_field( 'init_plugin_suite_pulse_for_discord_save_term_roles', 'init_plugin_suite_pulse_for_discord_term_roles_nonce' );
        });

        /**
         * SAVE — when creating term
         */
        add_action( "created_{$tax}", function ( $term_id ) {

            if ( ! isset( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) ) {
                return;
            }

            $nonce = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) );

            if ( ! wp_verify_nonce( $nonce, 'init_plugin_suite_pulse_for_discord_save_term_roles' ) ) {
                return;
            }

            if ( isset( $_POST['init_plugin_suite_pulse_for_discord_role_id'] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_role_id'] ) );
                $val ? update_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_role_id', $val )
                     : delete_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_role_id' );
            }

            if ( isset( $_POST['init_plugin_suite_pulse_for_discord_all_role_id'] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_all_role_id'] ) );
                $val ? update_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_all_role_id', $val )
                     : delete_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_all_role_id' );
            }

            init_plugin_suite_pulse_for_discord_save_term_webhook_fields( $term_id );
        });

        /**
         * UPDATE — when editing existing term
         */
        add_action( "edited_{$tax}", function ( $term_id ) {

            if ( ! isset( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) ) {
                return;
            }

            $nonce = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) );

            if ( ! wp_verify_nonce( $nonce, 'init_plugin_suite_pulse_for_discord_save_term_roles' ) ) {
                return;
            }

            if ( isset( $_POST['init_plugin_suite_pulse_for_discord_role_id'] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_role_id'] ) );
                $val ? update_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_role_id', $val )
                     : delete_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_role_id' );
            }

            if ( isset( $_POST['init_plugin_suite_pulse_for_discord_all_role_id'] ) ) {
                $val = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_all_role_id'] ) );
                $val ? update_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_all_role_id', $val )
                     : delete_term_meta( $term_id, 'init_plugin_suite_pulse_for_discord_all_role_id' );
            }

            init_plugin_suite_pulse_for_discord_save_term_webhook_fields( $term_id );
        });
    }
});

/**
 * Persist the per-term webhook override fields (URL, username, avatar,
 * exclusive flag). Shared by both the "created_{tax}" and "edited_{tax}"
 * handlers above. Verifies the nonce itself (defense in depth) even though
 * both callers already do so before invoking this function.
 *
 * @param int $term_id Term ID being saved.
 */
function init_plugin_suite_pulse_for_discord_save_term_webhook_fields( $term_id ) {

    if ( ! isset( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_roles_nonce'] ) );

    if ( ! wp_verify_nonce( $nonce, 'init_plugin_suite_pulse_for_discord_save_term_roles' ) ) {
        return;
    }

    if ( isset( $_POST['init_plugin_suite_pulse_for_discord_term_webhook_url'] ) ) {
        $val = esc_url_raw( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_webhook_url'] ) );
        $val ? update_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META, $val )
             : delete_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META );
    }

    if ( isset( $_POST['init_plugin_suite_pulse_for_discord_term_username'] ) ) {
        $val = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_username'] ) );
        $val ? update_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META, $val )
             : delete_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META );
    }

    if ( isset( $_POST['init_plugin_suite_pulse_for_discord_term_avatar'] ) ) {
        $val = esc_url_raw( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_avatar'] ) );
        $val ? update_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META, $val )
             : delete_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META );
    }

    $exclusive = isset( $_POST['init_plugin_suite_pulse_for_discord_term_webhook_exclusive'] )
        && '1' === sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_term_webhook_exclusive'] ) );

    $exclusive
        ? update_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META, '1' )
        : delete_term_meta( $term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META );
}
