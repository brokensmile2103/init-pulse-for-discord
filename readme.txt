=== Init Pulse For Discord – Webhooks, Roles, Instant ===
Contributors: brokensmile.2103
Tags: discord, webhook, notifications, publish, automation
Requires at least: 5.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress post notifications to Discord via webhooks — role mentions, custom post types, and rich embeds.

== Description ==

**Init Pulse For Discord** sends automatic notifications to your Discord channel whenever a post is published or updated.

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-pulse-for-discord](https://github.com/brokensmile2103/init-pulse-for-discord)

Perfect for:

- Blog / community announcements  
- Internal editorial workflow  
- Discord-driven audiences  

Straightforward configuration:

- Paste your webhook URL  
- (Optional) Configure Discord role IDs inside **Categories** or **Tags**  
- Done — your server receives updates instantly  

Clean, minimal, and built for performance — no bloat, no external SDKs.

Key design goals:

- Minimal setup (Webhook + 1 settings page)
- No database clutter
- No JS/CSS overhead on frontend
- Role-aware mentions through taxonomies

== Features ==

- Auto-send notifications when content is **published**
- Optional notifications when content is **updated**
- **Custom Post Type support** — choose exactly which post types (posts, pages, products, or any custom type) should trigger a notification
- Role tagging based on Categories & Tags  
  → When a post is in that taxonomy, that role is mentioned
- **Rich Discord embeds** — title, description, accent color, footer, and timestamp (with a plain-text fallback mode)
- **Send Test Message** button on the settings screen — verify your webhook without publishing a post
- **Delivery Log** — a rolling snapshot of the most recent deliveries (success/error) for troubleshooting, stored lightly in `wp_options` with `autoload = no`
- Custom message template with placeholders:
  - `{title}` — post title
  - `{title_url}` — markdown title linking to URL
  - `{url}` — post URL
  - `{excerpt}` — trimmed excerpt
  - `{site_name}` — your site’s name
  - `{author}` — post author display name
  - `{categories}` — comma-separated category names
  - `{tags}` — comma-separated tag names
  - `{post_type}` — post type label
  - `{date}` — published date (site format)
- Optional featured image embed
- Retry logic & timeout controls
- No action removals — plays well with all other plugins
- Compact, modern codebase

== Usage ==

Navigate to:

**Settings → Init Pulse For Discord**

Available fields:

| Field | Purpose |
|-------|---------|
| Post Types | Which post types trigger a notification |
| Webhook URL | Where notifications are sent (with a Send Test Message button) |
| Username | Display name of your bot |
| Avatar URL | Custom bot avatar (optional) |
| Include Featured Image | Adds featured image as embed |
| Use Rich Embed | Toggle formatted embed vs. plain-text message |
| Embed Color | Accent color for the embed sidebar |
| Timeout / Retries | Reliability controls |
| Message Template | Markdown-ready content, used as the embed description or full message |

The settings screen also shows a **Delivery Log** with the outcome of your most recent notifications.

Role IDs can be configured in:

- Posts → Categories → Edit  
- Posts → Tags → Edit  

Example mention result:

<@&123456789012345678> New post published!

== Filters for Developers ==

`init_plugin_suite_pulse_for_discord_payload`  
Modify the final webhook payload before sending.  
Params: `array $payload`, `int $post_id`, `string $context`

Example:

add_filter('init_plugin_suite_pulse_for_discord_payload', function($payload){
    $payload['content'] .= "\nCustom footer";
    return $payload;
});

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate via **Plugins → Init Pulse For Discord**
3. Go to **Settings → Init Pulse For Discord** and paste your webhook URL
4. Optional: Assign Discord Role IDs to categories & tags

== Screenshots ==

1. Settings page with webhook + template fields  
2. Category/Tag edit screen with Role ID inputs  

== Frequently Asked Questions ==

= Does it support Custom Post Types (CPT)? =  
Yes. Go to **Settings → Init Pulse For Discord → Post Types** and check any public post type you want to notify for (standard posts, pages, WooCommerce products, or any custom type).

= Can I test my webhook without publishing a post? =  
Yes — use the **Send Test Message** button next to the Webhook URL field. It sends a sample message using whatever is currently in the form, even before you save.

= Where can I see if a notification failed to send? =  
The **Delivery Log** section at the bottom of the settings screen shows the most recent deliveries with their status and error detail, if any.

= Can I send different messages for different categories? =  
Indirectly: assign different role IDs to categories/tags to tailor mentions.

= Can I use the webhook without role mentions? =  
Yes — simply leave the Role ID fields empty.

= Does this plugin override or remove publish hooks? =  
No. **It never removes actions or filters.**  
Everything is additive and safe-by-default.

== Changelog ==

= 1.1 – July 14, 2026 =
- Added: Custom Post Type support — select which post types trigger notifications
- Added: Rich Discord embeds (title, description, color, footer, timestamp) with a plain-text fallback toggle
- Added: "Send Test Message" button on the settings screen
- Added: Delivery Log — rolling snapshot of recent deliveries, stored with `autoload = no`
- Added: Extra template placeholders — `{author}`, `{categories}`, `{tags}`, `{post_type}`, `{date}`
- Changed: "Notify On" labels are now generic (no longer hardcoded to "post")
- No breaking changes — existing webhook, template, and role settings are preserved as-is

= 1.0 – November 12, 2025 =  
- Initial release  
- Publish/update notifications  
- Role-ID fields for categories & tags  
- Template placeholders: `{title}`, `{title_url}`, `{url}`, `{excerpt}`, `{site_name}`  
- Featured image embed support  
- Timeout + retry logic (includes 429 Retry-After handling)  
- Small, efficient, and avoids overriding core/hooks  

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
