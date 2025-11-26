<?php
/**
 * Webhook Dispatcher
 * Safe-by-default: no remove_all_actions, no overrides.
 * Hooks publish/update events for standard Posts only.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Helpers
 */

// Return trimmed plain-text excerpt (<= 280 chars)
function init_plugin_suite_pulse_for_discord_get_excerpt( $post ) {
    $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content, true );
    $excerpt = preg_replace( '/\s+/', ' ', $excerpt );
    $excerpt = trim( $excerpt );
    if ( mb_strlen( $excerpt ) > 280 ) {
        $excerpt = mb_substr( $excerpt, 0, 277 ) . '...';
    }
    return $excerpt;
}

// Collect Role IDs from categories & tags of a post
function init_plugin_suite_pulse_for_discord_collect_roles_for_post( $post_id ) {
    $roles      = array(); // specific per-term
    $roles_all  = array(); // all-posts per-term

    foreach ( array( 'category', 'post_tag' ) as $tax ) {
        $terms = get_the_terms( $post_id, $tax );
        if ( empty( $terms ) || is_wp_error( $terms ) ) continue;

        foreach ( $terms as $term ) {
            $rid  = get_term_meta( $term->term_id, 'init_plugin_suite_pulse_for_discord_role_id', true );
            $arid = get_term_meta( $term->term_id, 'init_plugin_suite_pulse_for_discord_all_role_id', true );
            if ( $rid )  { $roles[]     = preg_replace( '/\D+/', '', $rid ); }
            if ( $arid ) { $roles_all[] = preg_replace( '/\D+/', '', $arid ); }
        }
    }

    $roles     = array_values( array_unique( array_filter( $roles ) ) );
    $roles_all = array_values( array_unique( array_filter( $roles_all ) ) );

    // Priority: specific roles first; fallback to all-posts roles
    return ! empty( $roles ) ? $roles : $roles_all;
}

// Build message text from template and post
function init_plugin_suite_pulse_for_discord_render_template( $template, $post ) {
    $title     = get_the_title( $post );
    $url       = get_permalink( $post );
    $site_name = get_bloginfo( 'name' );
    $excerpt   = init_plugin_suite_pulse_for_discord_get_excerpt( $post );

    $repls = array(
        '{title}'     => $title,
        '{title_url}' => sprintf( '[%s](%s)', $title, $url ),
        '{url}'       => $url,
        '{excerpt}'   => $excerpt,
        '{site_name}' => $site_name,
    );

    return strtr( $template, $repls );
}

// Build Discord payload (content + optional embed with image)
function init_plugin_suite_pulse_for_discord_build_payload( $post_id, $context = 'publish' ) {
    $post = get_post( $post_id );
    if ( ! $post ) return false;

    $opts = array(
        'enable'         => get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' ) === '1',
        'webhook'        => trim( (string) get_option( 'init_plugin_suite_pulse_for_discord_webhook_url', '' ) ),
        'username'       => (string) get_option( 'init_plugin_suite_pulse_for_discord_username', get_bloginfo( 'name' ) ),
        'avatar'         => (string) get_option( 'init_plugin_suite_pulse_for_discord_avatar', '' ),
        'include_image'  => get_option( 'init_plugin_suite_pulse_for_discord_include_featured', '1' ) === '1',
        'image_size'     => (string) get_option( 'init_plugin_suite_pulse_for_discord_image_size', 'full' ),
        'timeout'        => absint( get_option( 'init_plugin_suite_pulse_for_discord_timeout', 8 ) ),
        'retry'          => absint( get_option( 'init_plugin_suite_pulse_for_discord_retry', 1 ) ),
        'tpl_post'       => (string) get_option( 'init_plugin_suite_pulse_for_discord_message_template_post', "{title_url}\n— {site_name}" ),
        'notify_new'     => get_option( 'init_plugin_suite_pulse_for_discord_notify_new_post', '1' ) === '1',
        'notify_update'  => get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' ) === '1',
    );

    if ( ! $opts['enable'] || empty( $opts['webhook'] ) ) return false;

    // Choose template
    $template = $opts['tpl_post'];
    $content  = init_plugin_suite_pulse_for_discord_render_template( $template, $post );

    // Mentions from taxonomy roles
    $roles = init_plugin_suite_pulse_for_discord_collect_roles_for_post( $post_id );
    if ( ! empty( $roles ) ) {
        $mention_parts = array_map( function( $rid ) { return '<@&' . $rid . '>'; }, $roles );
        $content = implode( ' ', $mention_parts ) . "\n" . $content;
    }

    // Optional embed image
    $embeds = array();
    if ( $opts['include_image'] && has_post_thumbnail( $post ) ) {
        $img = get_the_post_thumbnail_url( $post, $opts['image_size'] ? $opts['image_size'] : 'full' );
        if ( $img ) {
            $embeds[] = array(
                'type'  => 'rich',
                'image' => array( 'url' => esc_url_raw( $img ) ),
            );
        }
    }

    $payload = array(
        'content'  => $content,
        'username' => $opts['username'],
    );

    if ( ! empty( $opts['avatar'] ) ) {
        $payload['avatar_url'] = esc_url_raw( $opts['avatar'] );
    }
    if ( ! empty( $embeds ) ) {
        $payload['embeds'] = $embeds;
    }

    // Allow theme/plugins to tweak payload safely
    $payload = apply_filters( 'init_plugin_suite_pulse_for_discord_payload', $payload, $post_id, $context );

    return array( $payload, $opts );
}

// Send webhook with simple retry (includes 429 handling via Retry-After)
function init_plugin_suite_pulse_for_discord_send_webhook( $webhook_url, $payload, $timeout = 8, $retry = 1 ) {
    $attempts = max( 1, intval( $retry ) + 1 ); // first try + retries
    $body     = wp_json_encode( $payload );

    for ( $i = 0; $i < $attempts; $i++ ) {
        $resp = wp_remote_post( $webhook_url, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => $body,
            'timeout' => max( 1, intval( $timeout ) ),
        ) );

        if ( is_wp_error( $resp ) ) {
            if ( $i < $attempts - 1 ) { sleep( 1 ); continue; }
            return $resp;
        }

        $code = wp_remote_retrieve_response_code( $resp );

        if ( $code >= 200 && $code < 300 ) {
            return true;
        }

        if ( $code == 429 ) {
            $headers = wp_remote_retrieve_headers( $resp );
            $retry_after = isset( $headers['retry-after'] ) ? max( 1, intval( $headers['retry-after'] ) ) : 1;
            sleep( $retry_after );
            continue;
        }

        if ( $i < $attempts - 1 ) { sleep( 1 ); continue; }
        return new WP_Error( 'discord_http_error', 'Discord webhook responded with HTTP ' . $code );
    }

    return new WP_Error( 'discord_unreachable', 'Discord webhook unreachable after retries.' );
}

/**
 * Event callbacks (NO overrides)
 */

// When a post transitions into 'publish' for the first time
function init_plugin_suite_pulse_for_discord_on_publish( $new_status, $old_status, $post ) {
    if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) return;
    if ( $post->post_type !== 'post' ) return;

    // Only when becoming published
    if ( $old_status === 'publish' || $new_status !== 'publish' ) return;

    // Respect user option
    if ( get_option( 'init_plugin_suite_pulse_for_discord_notify_new_post', '1' ) !== '1' ) return;

    $built = init_plugin_suite_pulse_for_discord_build_payload( $post->ID, 'publish' );
    if ( ! $built ) return;

    list( $payload, $opts ) = $built;
    init_plugin_suite_pulse_for_discord_send_webhook( $opts['webhook'], $payload, $opts['timeout'], $opts['retry'] );
}
add_action( 'transition_post_status', 'init_plugin_suite_pulse_for_discord_on_publish', 10, 3 );

// When an existing published post is updated
function init_plugin_suite_pulse_for_discord_on_update( $post_ID, $post_after, $post_before ) {
    if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) return;
    if ( $post_after->post_type !== 'post' ) return;

    // Only if stays published
    if ( $post_after->post_status !== 'publish' ) return;

    // Respect user option
    if ( get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' ) !== '1' ) return;

    // Avoid double-send when just transitioned to publish
    if ( $post_before && $post_before->post_status !== 'publish' ) return;

    $built = init_plugin_suite_pulse_for_discord_build_payload( $post_ID, 'update' );
    if ( ! $built ) return;

    list( $payload, $opts ) = $built;
    init_plugin_suite_pulse_for_discord_send_webhook( $opts['webhook'], $payload, $opts['timeout'], $opts['retry'] );
}
add_action( 'post_updated', 'init_plugin_suite_pulse_for_discord_on_update', 10, 3 );

/**
 * Public filter to alter payload before sending.
 * Example:
 * add_filter('init_plugin_suite_pulse_for_discord_payload', function($payload, $post_id, $context){
 *     $payload['content'] .= "\nCustom footer";
 *     return $payload;
 * }, 10, 3);
 */
