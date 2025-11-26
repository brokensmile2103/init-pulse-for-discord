<?php
/**
 * Taxonomy Discord Role IDs
 * Adds Role ID fields to categories and tags (blog scope).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
        });
    }
});
