<?php
/**
 * Hot Post Milestones (cross-plugin integration: Init View Count)
 *
 * Optional feature: send a Discord notification when a tracked post's total
 * view count — reported by the Init View Count plugin — crosses one of the
 * configured milestones (e.g. 1,000 / 10,000 / 100,000 views).
 *
 * This is a SOFT dependency only. This file never assumes Init View Count is
 * active or loaded first:
 * - Presence is detected via defined('INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION').
 * - The actual hook registration happens on 'plugins_loaded' (priority 20),
 *   which runs only after every active plugin's main file has already been
 *   included — so load order between the two plugins never matters.
 * - If Init View Count is deactivated later, this feature simply goes
 *   dormant again; saved settings are untouched and resume working
 *   automatically if the plugin is reactivated.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_MILESTONE_META' ) ) {
    // Post meta: list of milestone thresholds already notified for this post,
    // so a milestone only ever fires once even if the setting is changed later.
    define( 'INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_MILESTONE_META', '_init_pulse_notified_milestones' );
}

/**
 * Whether Init View Count is active and exposes the hook this feature needs.
 *
 * @return bool
 */
function init_plugin_suite_pulse_for_discord_view_count_active() {
    return defined( 'INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION' );
}

/**
 * Parse a raw "1000, 5000, 10000" string into a sorted, de-duplicated list
 * of positive integers. Used both as the settings sanitize callback input
 * and at notification time.
 *
 * @param string $raw
 * @return int[]
 */
function init_plugin_suite_pulse_for_discord_parse_milestones( $raw ) {
    $parts = preg_split( '/[,\s]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
    $nums  = array();

    foreach ( $parts as $part ) {
        $n = absint( $part );
        if ( $n > 0 ) {
            $nums[] = $n;
        }
    }

    $nums = array_values( array_unique( $nums ) );
    sort( $nums, SORT_NUMERIC );

    return $nums;
}

// Sanitize callback: store back as a clean, human-readable "1000, 5000, 10000" string.
function init_plugin_suite_pulse_for_discord_sanitize_milestones( $value ) {
    return implode( ', ', init_plugin_suite_pulse_for_discord_parse_milestones( $value ) );
}

// ==========================================================
// Settings (kept in its own admin_init block so this whole
// feature stays self-contained and easy to remove if ever needed)
// ==========================================================
add_action( 'admin_init', function () {
    $group = INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_OPTION;

    $sanitize_bool = fn( $v ) => isset( $v ) ? '1' : '0';

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_notify_milestone', [
        'sanitize_callback' => $sanitize_bool,
    ] );

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_milestone_thresholds', [
        'sanitize_callback' => 'init_plugin_suite_pulse_for_discord_sanitize_milestones',
        'default'           => '1000, 5000, 10000, 50000, 100000',
    ] );

    register_setting( $group, 'init_plugin_suite_pulse_for_discord_message_template_milestone', [
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => "🔥 {title_url}\n**{views_short}** views and counting — {site_name}",
    ] );
} );

/**
 * Build the milestone-specific Discord payload. Mirrors
 * init_plugin_suite_pulse_for_discord_build_payload() in webhook-dispatcher.php,
 * but a milestone hit is its own event (not a publish/update), so it gets
 * its own template, placeholders, and doesn't check the notify_new/notify_update
 * options — only the master "Enable Discord Notifications" switch, checked by
 * the caller.
 *
 * @param WP_Post $post
 * @param int     $views     Current total view count (from Init View Count).
 * @param int     $milestone The threshold that was just crossed.
 * @return array{0: array, 1: array}|false
 */
function init_plugin_suite_pulse_for_discord_build_milestone_payload( $post, $views, $milestone ) {
    $opts = array(
        'webhook'       => trim( (string) get_option( 'init_plugin_suite_pulse_for_discord_webhook_url', '' ) ),
        'username'      => (string) get_option( 'init_plugin_suite_pulse_for_discord_username', get_bloginfo( 'name' ) ),
        'avatar'        => (string) get_option( 'init_plugin_suite_pulse_for_discord_avatar', '' ),
        'include_image' => get_option( 'init_plugin_suite_pulse_for_discord_include_featured', '1' ) === '1',
        'image_size'    => (string) get_option( 'init_plugin_suite_pulse_for_discord_image_size', 'full' ),
        'timeout'       => absint( get_option( 'init_plugin_suite_pulse_for_discord_timeout', 8 ) ),
        'retry'         => absint( get_option( 'init_plugin_suite_pulse_for_discord_retry', 1 ) ),
        'tpl'           => (string) get_option( 'init_plugin_suite_pulse_for_discord_message_template_milestone', "🔥 {title_url}\n**{views_short}** views and counting — {site_name}" ),
        'rich_embed'    => get_option( 'init_plugin_suite_pulse_for_discord_enable_rich_embed', '1' ) === '1',
        'embed_color'   => (string) get_option( 'init_plugin_suite_pulse_for_discord_embed_color', '#5865F2' ),
    );

    // Note: the global webhook is intentionally NOT required here — the post
    // may be routed entirely through per-term webhooks, resolved later by
    // init_plugin_suite_pulse_for_discord_collect_webhook_targets().

    // Base placeholders shared with the publish/update template, plus 3 milestone-only ones.
    // Reuses Init View Count's own short-number formatter (e.g. "12.3 K") when available,
    // so both plugins render numbers identically instead of two slightly different formats.
    $placeholders = init_plugin_suite_pulse_for_discord_get_placeholders( $post );
    $placeholders['{views}']       = number_format_i18n( $views );
    $placeholders['{views_short}'] = function_exists( 'init_plugin_suite_view_count_format_thousands' )
        ? init_plugin_suite_view_count_format_thousands( $views )
        : number_format_i18n( $views );
    $placeholders['{milestone}']   = number_format_i18n( $milestone );

    $rendered = strtr( $opts['tpl'], $placeholders );

    $roles   = init_plugin_suite_pulse_for_discord_collect_roles_for_post( $post->ID );
    $mention = '';
    if ( ! empty( $roles ) ) {
        $mention = implode( ' ', array_map( function ( $rid ) { return '<@&' . $rid . '>'; }, $roles ) );
    }

    $image_url = '';
    if ( $opts['include_image'] && has_post_thumbnail( $post ) ) {
        $image_url = (string) get_the_post_thumbnail_url( $post, $opts['image_size'] ? $opts['image_size'] : 'full' );
    }

    // Identity (username/avatar) is filled in per-destination at dispatch
    // time, same as the publish/update payload in webhook-dispatcher.php.
    $payload = array();

    if ( $opts['rich_embed'] ) {
        $embed = array(
            'type'        => 'rich',
            'title'       => mb_substr( get_the_title( $post ), 0, 256 ),
            'url'         => get_permalink( $post ),
            'description' => mb_substr( $rendered, 0, 2048 ),
            'color'       => hexdec( ltrim( $opts['embed_color'] ? $opts['embed_color'] : '#5865F2', '#' ) ),
            'footer'      => array( 'text' => mb_substr( get_bloginfo( 'name' ), 0, 2048 ) ),
        );

        if ( $image_url ) {
            $embed['image'] = array( 'url' => esc_url_raw( $image_url ) );
        }

        $payload['content'] = $mention;
        $payload['embeds']  = array( $embed );
    } else {
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

    // Allow theme/plugins to tweak the milestone payload safely (mirrors the
    // publish/update filter in webhook-dispatcher.php).
    $payload = apply_filters( 'init_plugin_suite_pulse_for_discord_milestone_payload', $payload, $post->ID, $views, $milestone );

    return array( $payload, $opts );
}

/**
 * Runs on EVERY counted view (Init View Count fires this per post, per
 * request). Bails out in one cheap option check whenever the feature is
 * off, before touching post meta or doing any milestone math.
 *
 * @param int              $post_id
 * @param array            $updated  ['total' => int, ...] — see Init View Count readme.
 * @param WP_REST_Request  $request
 */
function init_plugin_suite_pulse_for_discord_on_view_counted( $post_id, $updated, $request ) {
    if ( get_option( 'init_plugin_suite_pulse_for_discord_enable', '0' ) !== '1' ) return;
    if ( get_option( 'init_plugin_suite_pulse_for_discord_notify_milestone', '0' ) !== '1' ) return;

    if ( ! isset( $updated['total'] ) ) return;
    $views = (int) $updated['total'];
    if ( $views <= 0 ) return;

    $post_type     = get_post_type( $post_id );
    $tracked_types = init_plugin_suite_pulse_for_discord_get_post_types();
    if ( ! in_array( $post_type, $tracked_types, true ) ) return;

    $thresholds = init_plugin_suite_pulse_for_discord_parse_milestones(
        get_option( 'init_plugin_suite_pulse_for_discord_milestone_thresholds', '' )
    );
    if ( empty( $thresholds ) ) return;

    $notified = get_post_meta( $post_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_MILESTONE_META, true );
    $notified = is_array( $notified ) ? array_map( 'intval', $notified ) : array();

    // Mốc nào đã đạt (views >= threshold) nhưng CHƯA từng được thông báo.
    $newly_crossed = array_values( array_filter( $thresholds, function ( $t ) use ( $views, $notified ) {
        return $views >= $t && ! in_array( $t, $notified, true );
    } ) );

    if ( empty( $newly_crossed ) ) return;

    // Nếu view nhảy cóc qua nhiều mốc cùng lúc (import dữ liệu, đặt lại thủ
    // công...), chỉ bắn 1 tin cho mốc CAO NHẤT — tránh spam nhiều tin nhắn dồn dập.
    $target_milestone = max( $newly_crossed );

    $post = get_post( $post_id );
    if ( ! $post ) return;

    $built = init_plugin_suite_pulse_for_discord_build_milestone_payload( $post, $views, $target_milestone );

    // Đánh dấu TẤT CẢ mốc <= mốc vừa vượt là đã xử lý — kể cả khi không gửi
    // được (VD: chưa cấu hình webhook) — tránh việc mỗi lượt view tiếp theo
    // đều thử lại vô ích cho một mốc chắc chắn không gửi được.
    $passed   = array_values( array_filter( $thresholds, fn( $t ) => $t <= $target_milestone ) );
    $notified = array_values( array_unique( array_merge( $notified, $passed ) ) );
    update_post_meta( $post_id, INIT_PLUGIN_SUITE_PULSE_FOR_DISCORD_MILESTONE_META, $notified );

    if ( ! $built ) return;

    list( $payload, $opts ) = $built;

    $targets = init_plugin_suite_pulse_for_discord_collect_webhook_targets( $post_id, $opts );
    if ( empty( $targets ) ) return; // Nothing configured to send to.

    $base_title = sprintf(
        /* translators: 1: post title, 2: milestone view count (formatted). */
        __( 'Milestone (%2$s views): %1$s', 'init-pulse-for-discord' ),
        get_the_title( $post ),
        number_format_i18n( $target_milestone )
    );

    foreach ( $targets as $target ) {
        $final_payload             = $payload;
        $final_payload['username'] = $target['username'];
        if ( ! empty( $target['avatar'] ) ) {
            $final_payload['avatar_url'] = esc_url_raw( $target['avatar'] );
        }

        $result = init_plugin_suite_pulse_for_discord_send_webhook( $target['webhook'], $final_payload, $opts['timeout'], $opts['retry'] );

        init_plugin_suite_pulse_for_discord_add_log_entry( array(
            'context' => 'milestone',
            'post_id' => $post_id,
            'title'   => $target['label'] ? sprintf( '%s [%s]', $base_title, $target['label'] ) : $base_title,
            'status'  => is_wp_error( $result ) ? 'error' : 'success',
            'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Delivered', 'init-pulse-for-discord' ),
        ) );
    }
}

/**
 * Register the cross-plugin hook only once every active plugin has finished
 * loading. Priority 20 leaves room for other plugins hooking 'plugins_loaded'
 * at the default priority (10) to finish their own setup first.
 */
add_action( 'plugins_loaded', function () {
    if ( ! init_plugin_suite_pulse_for_discord_view_count_active() ) return;
    add_action( 'init_plugin_suite_view_count_after_counted', 'init_plugin_suite_pulse_for_discord_on_view_counted', 10, 3 );
}, 20 );
