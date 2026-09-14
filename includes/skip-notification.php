<?php
/**
 * Per-Post "Do not send to Discord" Checkbox
 *
 * Adds a small meta box so an editor can opt an individual post out of
 * every Discord notification — the publish/update event, and any future
 * Hot Post Milestones alerts for that same post (see
 * INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META, checked in
 * includes/webhook-dispatcher.php and includes/milestone-notify.php).
 *
 * Visibility rules:
 * - Shown while the post has not been published yet (draft, pending,
 *   scheduled, auto-draft...) — since a first-time publish notification
 *   may still fire once it is.
 * - Once the post is published, the box only keeps showing if "Notify on
 *   post update" is enabled in the plugin's global settings — otherwise
 *   there is nothing left for it to suppress on that post.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', function ( $post_type, $post ) {

    if ( get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' ) !== '1' ) {
        return;
    }

    if ( ! in_array( $post_type, init_plugin_suite_pulse_for_discord_get_post_types(), true ) ) {
        return;
    }

    if ( ! ( $post instanceof WP_Post ) ) {
        return;
    }

    // Already published: only worth showing if update notifications are on,
    // otherwise nothing would be sent for this post again anyway.
    if ( 'publish' === $post->post_status
        && get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' ) !== '1' ) {
        return;
    }

    add_meta_box(
        'init_plugin_suite_pulse_for_discord_skip',
        __( 'Discord Notification', 'init-pulse-for-discord' ),
        'init_plugin_suite_pulse_for_discord_render_skip_metabox',
        $post_type,
        'side',
        'default'
    );
}, 10, 2 );

/**
 * Render the meta box content.
 *
 * @param WP_Post $post Post currently being edited.
 */
function init_plugin_suite_pulse_for_discord_render_skip_metabox( $post ) {
    $skip = get_post_meta( $post->ID, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META, true ) === '1';

    wp_nonce_field( 'init_plugin_suite_pulse_for_discord_save_skip', 'init_plugin_suite_pulse_for_discord_skip_nonce' );
    ?>
    <label for="init_plugin_suite_pulse_for_discord_skip_field">
        <input type="checkbox"
               name="init_plugin_suite_pulse_for_discord_skip_field"
               id="init_plugin_suite_pulse_for_discord_skip_field"
               value="1" <?php checked( $skip ); ?>>
        <?php esc_html_e( 'Do not send this post to Discord', 'init-pulse-for-discord' ); ?>
    </label>
    <p class="description">
        <?php
        if ( 'publish' === $post->post_status ) {
            esc_html_e( 'Skips the update notification (and any future view-milestone alerts) for this post.', 'init-pulse-for-discord' );
        } else {
            esc_html_e( 'Skips the publish notification (and any future update or view-milestone alerts) for this post.', 'init-pulse-for-discord' );
        }
        ?>
    </p>
    <?php
}

/**
 * Persist the checkbox on save.
 *
 * @param int     $post_id Post ID being saved.
 * @param WP_Post $post    Post object being saved.
 */
function init_plugin_suite_pulse_for_discord_save_skip_meta( $post_id, $post ) {

    if ( ! isset( $_POST['init_plugin_suite_pulse_for_discord_skip_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_skip_nonce'] ) );
    if ( ! wp_verify_nonce( $nonce, 'init_plugin_suite_pulse_for_discord_save_skip' ) ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! ( $post instanceof WP_Post ) || ! in_array( $post->post_type, init_plugin_suite_pulse_for_discord_get_post_types(), true ) ) {
        return;
    }

    $skip = isset( $_POST['init_plugin_suite_pulse_for_discord_skip_field'] )
        && '1' === sanitize_text_field( wp_unslash( $_POST['init_plugin_suite_pulse_for_discord_skip_field'] ) );

    $skip
        ? update_post_meta( $post_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META, '1' )
        : delete_post_meta( $post_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META );
}
add_action( 'save_post', 'init_plugin_suite_pulse_for_discord_save_skip_meta', 10, 2 );
