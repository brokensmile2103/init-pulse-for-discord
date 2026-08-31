=== Init Pulse For Discord – Webhooks, Roles, Instant ===
Contributors: brokensmile.2103
Tags: discord, webhook, notifications, publish, automation
Requires at least: 5.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3
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
- **Hot Post Milestones** *(optional, requires [Init View Count](https://wordpress.org/plugins/init-view-count/))* — automatically notify Discord when a post's total view count crosses a configured threshold (e.g. 1,000 / 10,000 / 100,000 views), with its own message template and `{views}`, `{views_short}`, `{milestone}` placeholders. Each milestone is sent once per post. Gracefully inactive (with a link to install it) when Init View Count isn't active — no errors, no broken settings.

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
| Notify on View Milestones | Enable Discord alerts when a post's view count crosses a threshold (requires Init View Count) |
| View Milestones | Comma-separated view counts (e.g. `1000, 5000, 10000`) |
| Milestone Message Template | Separate template for milestone alerts, with `{views}`, `{views_short}`, `{milestone}` placeholders |

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

`init_plugin_suite_pulse_for_discord_milestone_payload`  
Modify the webhook payload for a milestone notification before sending.  
Params: `array $payload`, `int $post_id`, `int $views`, `int $milestone`

Example:

add_filter('init_plugin_suite_pulse_for_discord_payload', function($payload) {
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
2. Delivery Log with recent webhook delivery history
3. Category/Tag edit screen with Role ID inputs

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

= What is "Hot Post Milestones" and do I need another plugin for it? =  
It's an optional feature that sends a Discord alert when a post's total view count crosses a threshold you configure (e.g. 1,000 views). It requires the free [Init View Count](https://wordpress.org/plugins/init-view-count/) plugin to be active, since that's what actually counts the views. If Init View Count isn't installed, the setting is simply inactive (with a link to get it) — nothing breaks, and no other feature of Init Pulse For Discord is affected.

= I installed Init View Count after configuring milestones — do I need to re-save settings? =  
No. The connection is checked automatically on every page load, so milestone notifications start working as soon as Init View Count is active — no re-save needed.

== Changelog ==

= 1.3 – September 1, 2026 =
* Fixed: The shared checkbox sanitizer treated any present value as enabled, causing every unchecked checkbox to be saved as `1` on the first save. Tightened to require an explicit `'1'` before treating a checkbox as on.

= 1.2 – August 4, 2026 =
- Added: **Hot Post Milestones** — optional cross-plugin integration with Init View Count. Sends a Discord notification (with its own message template) when a tracked post's total view count crosses a configured threshold. Each milestone fires once per post.
  - Soft dependency only: detected via `defined('INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION')` on `plugins_loaded` (priority 20), so plugin load order never matters and nothing errors if Init View Count is inactive or later deactivated.
  - When Init View Count isn't active, the settings screen shows a notice with a link to it; fields remain configurable and take effect automatically once the plugin is installed.
- Added: `init_plugin_suite_pulse_for_discord_milestone_payload` filter for developers to customize the milestone webhook payload.

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
