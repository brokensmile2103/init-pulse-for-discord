<?php
/**
 * Webhook Dispatcher
 * Safe-by-default: no remove_all_actions, no overrides.
 * Hooks publish/update events for the configured post types only.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Term meta keys used by the per-term webhook override feature (see
 * includes/taxonomy-discord-roles.php for the admin UI that manages them).
 * Defined here since this file loads first; both files share these keys.
 */
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META', 'init_plugin_suite_pulse_for_discord_term_webhook_url' );
}
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META', 'init_plugin_suite_pulse_for_discord_term_username' );
}
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META', 'init_plugin_suite_pulse_for_discord_term_avatar' );
}
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META', 'init_plugin_suite_pulse_for_discord_term_webhook_exclusive' );
}

/**
 * Post meta key for the per-post "Do not send to Discord" checkbox (see
 * includes/skip-notification.php for the meta box that manages it).
 */
if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META' ) ) {
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META', '_init_plugin_suite_pulse_for_discord_skip' );
}

/**
 * Helpers
 */

// Return the list of post types the admin has chosen to notify for (defaults to 'post').
function init_plugin_suite_pulse_for_discord_get_post_types() {
    $saved = get_option( 'init_plugin_suite_pulse_for_discord_post_types', array( 'post' ) );

    if ( ! is_array( $saved ) ) {
        $saved = array( 'post' );
    }

    return array_values( array_filter( array_map( 'sanitize_key', $saved ) ) );
}

// Return public, selectable post types (attachments excluded) for the settings UI.
function init_plugin_suite_pulse_for_discord_get_selectable_post_types() {
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    unset( $post_types['attachment'] );
    return $post_types;
}

// Whether a specific post has been opted out of ALL Discord notifications
// (publish/update AND Hot Post Milestones) via its own "Do not send to
// Discord" checkbox — see includes/skip-notification.php.
function init_plugin_suite_pulse_for_discord_post_is_skipped( $post_id ) {
    return get_post_meta( $post_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_SKIP_META, true ) === '1';
}

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

// Collect Role IDs from categories & tags of a post (no-op for post types without these taxonomies)
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

/**
 * Collect the Discord webhook destination(s) for a post, based on the
 * per-term overrides configured on its categories/tags (see
 * includes/taxonomy-discord-roles.php) plus the global webhook.
 *
 * Rules:
 * - Every category/tag with its own webhook URL configured is a distinct
 *   destination (deduplicated by webhook URL — the first matching term
 *   wins the identity if the same URL is set on more than one term).
 * - A term's own Username/Avatar are used when set; otherwise the global
 *   ones are used as fallback.
 * - If ANY matching term has "Only send to this webhook" enabled, the
 *   global webhook is skipped entirely for this post.
 * - When no term overrides apply (or none are exclusive), the global
 *   webhook is still included as a destination — this keeps existing
 *   sites working exactly as before, unchanged.
 *
 * @param int   $post_id Post ID to resolve destinations for.
 * @param array $opts Global options, must contain 'webhook', 'username', 'avatar'.
 * @return array[] List of destinations: array{webhook:string, username:string, avatar:string, label:string}
 */
function init_plugin_suite_pulse_for_discord_collect_webhook_targets( $post_id, $opts ) {
    $targets     = array(); // Keyed by webhook URL to dedupe.
    $skip_global = false;

    foreach ( array( 'category', 'post_tag' ) as $tax ) {
        $terms = get_the_terms( $post_id, $tax );
        if ( empty( $terms ) || is_wp_error( $terms ) ) continue;

        foreach ( $terms as $term ) {
            $webhook = trim( (string) get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_WEBHOOK_META, true ) );
            if ( '' === $webhook ) continue;

            $exclusive = get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_EXCLUSIVE_META, true ) === '1';
            if ( $exclusive ) {
                $skip_global = true;
            }

            if ( isset( $targets[ $webhook ] ) ) continue; // Already added by another term.

            $username = trim( (string) get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_USERNAME_META, true ) );
            $avatar   = trim( (string) get_term_meta( $term->term_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_TERM_AVATAR_META, true ) );

            $targets[ $webhook ] = array(
                'webhook'  => $webhook,
                'username' => '' !== $username ? $username : $opts['username'],
                'avatar'   => '' !== $avatar ? $avatar : $opts['avatar'],
                /* translators: %s: category or tag name. */
                'label'    => sprintf( __( 'Term: %s', 'init-pulse-for-discord' ), $term->name ),
            );
        }
    }

    if ( ! $skip_global && ! empty( $opts['webhook'] ) && ! isset( $targets[ $opts['webhook'] ] ) ) {
        $targets[ $opts['webhook'] ] = array(
            'webhook'  => $opts['webhook'],
            'username' => $opts['username'],
            'avatar'   => $opts['avatar'],
            'label'    => '',
        );
    }

    return array_values( $targets );
}

// Build a comma-separated term name list for a post/taxonomy (empty string if none).
function init_plugin_suite_pulse_for_discord_get_term_names( $post_id, $taxonomy ) {
    $terms = get_the_terms( $post_id, $taxonomy );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }
    return implode( ', ', wp_list_pluck( $terms, 'name' ) );
}

// Build the full set of available template placeholders for a post.
function init_plugin_suite_pulse_for_discord_get_placeholders( $post ) {
    $post_type_obj = get_post_type_object( $post->post_type );

    return array(
        '{title}'      => get_the_title( $post ),
        '{title_url}'  => sprintf( '[%s](%s)', get_the_title( $post ), get_permalink( $post ) ),
        '{url}'        => get_permalink( $post ),
        '{excerpt}'    => init_plugin_suite_pulse_for_discord_get_excerpt( $post ),
        '{site_name}'  => get_bloginfo( 'name' ),
        '{author}'     => get_the_author_meta( 'display_name', $post->post_author ),
        '{categories}' => init_plugin_suite_pulse_for_discord_get_term_names( $post->ID, 'category' ),
        '{tags}'       => init_plugin_suite_pulse_for_discord_get_term_names( $post->ID, 'post_tag' ),
        '{post_type}'  => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
        '{date}'       => get_the_date( '', $post ),
    );
}

// Build message text from template and post
function init_plugin_suite_pulse_for_discord_render_template( $template, $post ) {
    return strtr( $template, init_plugin_suite_pulse_for_discord_get_placeholders( $post ) );
}

// Build Discord payload (content + optional embed with image)
function init_plugin_suite_pulse_for_discord_build_payload( $post_id, $context = 'publish' ) {
    $post = get_post( $post_id );
    if ( ! $post ) return false;

    // Per-post opt-out: the author explicitly checked "Do not send this
    // post to Discord" — skip publish/update (and, separately, Hot Post
    // Milestones checks this same flag in milestone-notify.php).
    if ( init_plugin_suite_pulse_for_discord_post_is_skipped( $post_id ) ) return false;

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
        'rich_embed'     => get_option( 'init_plugin_suite_pulse_for_discord_enable_rich_embed', '1' ) === '1',
        'embed_color'    => (string) get_option( 'init_plugin_suite_pulse_for_discord_embed_color', '#5865F2' ),
    );

    // Note: the global webhook is intentionally NOT required here — a post
    // may be routed entirely through per-term webhooks (see
    // init_plugin_suite_pulse_for_discord_collect_webhook_targets()). Whether
    // there is anything at all to send to is resolved later, at dispatch time.
    if ( ! $opts['enable'] ) return false;

    // Rendered template text (used as embed description in rich mode, or as content in legacy mode).
    $rendered = init_plugin_suite_pulse_for_discord_render_template( $opts['tpl_post'], $post );

    // Mentions from taxonomy roles (must live in "content" — embeds never trigger role pings).
    $roles   = init_plugin_suite_pulse_for_discord_collect_roles_for_post( $post_id );
    $mention = '';
    if ( ! empty( $roles ) ) {
        $mention_parts = array_map( function( $rid ) { return '<@&' . $rid . '>'; }, $roles );
        $mention = implode( ' ', $mention_parts );
    }

    // Featured image URL (shared by both modes).
    $image_url = '';
    if ( $opts['include_image'] && has_post_thumbnail( $post ) ) {
        $image_url = (string) get_the_post_thumbnail_url( $post, $opts['image_size'] ? $opts['image_size'] : 'full' );
    }

    // Identity (username/avatar) is intentionally left out of the base
    // payload: it is filled in per-destination at dispatch time, since each
    // per-term webhook target may use its own override (see
    // init_plugin_suite_pulse_for_discord_collect_webhook_targets()).
    $payload = array();

    if ( $opts['rich_embed'] ) {
        $embed = array(
            'type'        => 'rich',
            'title'       => mb_substr( get_the_title( $post ), 0, 256 ),
            'url'         => get_permalink( $post ),
            'description' => mb_substr( $rendered, 0, 2048 ),
            'color'       => hexdec( ltrim( $opts['embed_color'] ? $opts['embed_color'] : '#5865F2', '#' ) ),
            'footer'      => array( 'text' => mb_substr( get_bloginfo( 'name' ), 0, 2048 ) ),
            'timestamp'   => get_post_time( 'c', true, $post ),
        );

        if ( $image_url ) {
            $embed['image'] = array( 'url' => esc_url_raw( $image_url ) );
        }

        $payload['content'] = $mention;
        $payload['embeds']  = array( $embed );
    } else {
        // Legacy plain-text mode (pre-1.1 behavior).
        $content = $mention ? $mention . "\n" . $rendered : $rendered;
        $payload['content'] = $content;

        if ( $image_url ) {
            $payload['embeds'] = array(
                array(
                    'type'  => 'rich',
                    'image' => array( 'url' => esc_url_raw( $image_url ) ),
                ),
            );
        }
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
        return new WP_Error( 'discord_http_error', sprintf(
            /* translators: %d: HTTP status code returned by Discord. */
            __( 'Discord webhook responded with HTTP %d.', 'init-pulse-for-discord' ),
            $code
        ) );
    }

    return new WP_Error( 'discord_unreachable', __( 'Discord webhook unreachable after retries.', 'init-pulse-for-discord' ) );
}

// Send the payload to every resolved destination (global and/or per-term
// webhooks) and record each outcome as its own delivery log entry.
function init_plugin_suite_pulse_for_discord_dispatch_and_log( $post, $context, $payload, $opts ) {
    $targets = init_plugin_suite_pulse_for_discord_collect_webhook_targets( $post->ID, $opts );
    if ( empty( $targets ) ) return; // Nothing configured to send to.

    $context_labels = array(
        'publish' => __( 'New post', 'init-pulse-for-discord' ),
        'update'  => __( 'Update', 'init-pulse-for-discord' ),
    );

    $base_title = isset( $context_labels[ $context ] )
        ? sprintf( '%s: %s', $context_labels[ $context ], get_the_title( $post ) )
        : get_the_title( $post );

    foreach ( $targets as $target ) {
        $final_payload              = $payload;
        $final_payload['username']  = $target['username'];
        if ( ! empty( $target['avatar'] ) ) {
            $final_payload['avatar_url'] = esc_url_raw( $target['avatar'] );
        }

        $result = init_plugin_suite_pulse_for_discord_send_webhook( $target['webhook'], $final_payload, $opts['timeout'], $opts['retry'] );

        init_plugin_suite_pulse_for_discord_add_log_entry( array(
            'context' => $context,
            'post_id' => $post->ID,
            'title'   => $target['label'] ? sprintf( '%s [%s]', $base_title, $target['label'] ) : $base_title,
            'status'  => is_wp_error( $result ) ? 'error' : 'success',
            'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Delivered', 'init-pulse-for-discord' ),
        ) );
    }
}

/**
 * Event callbacks (NO overrides)
 */

// When a post transitions into 'publish' for the first time
function init_plugin_suite_pulse_for_discord_on_publish( $new_status, $old_status, $post ) {
    if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) return;

    $post_types = init_plugin_suite_pulse_for_discord_get_post_types();
    if ( ! in_array( $post->post_type, $post_types, true ) ) return;

    // Only when becoming published
    if ( $old_status === 'publish' || $new_status !== 'publish' ) return;

    // Respect user option
    if ( get_option( 'init_plugin_suite_pulse_for_discord_notify_new_post', '1' ) !== '1' ) return;

    $built = init_plugin_suite_pulse_for_discord_build_payload( $post->ID, 'publish' );
    if ( ! $built ) return;

    list( $payload, $opts ) = $built;
    init_plugin_suite_pulse_for_discord_dispatch_and_log( $post, 'publish', $payload, $opts );
}
add_action( 'transition_post_status', 'init_plugin_suite_pulse_for_discord_on_publish', 10, 3 );

// When an existing published post is updated
function init_plugin_suite_pulse_for_discord_on_update( $post_ID, $post_after, $post_before ) {
    if ( wp_is_post_revision( $post_ID ) || wp_is_post_autosave( $post_ID ) ) return;

    $post_types = init_plugin_suite_pulse_for_discord_get_post_types();
    if ( ! in_array( $post_after->post_type, $post_types, true ) ) return;

    // Only if stays published
    if ( $post_after->post_status !== 'publish' ) return;

    // Respect user option
    if ( get_option( 'init_plugin_suite_pulse_for_discord_notify_post_update', '0' ) !== '1' ) return;

    // Avoid double-send when just transitioned to publish
    if ( $post_before && $post_before->post_status !== 'publish' ) return;

    $built = init_plugin_suite_pulse_for_discord_build_payload( $post_ID, 'update' );
    if ( ! $built ) return;

    list( $payload, $opts ) = $built;
    init_plugin_suite_pulse_for_discord_dispatch_and_log( $post_after, 'update', $payload, $opts );
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
