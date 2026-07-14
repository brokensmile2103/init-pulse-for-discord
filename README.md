# Init Pulse For Discord – Webhooks, Roles, Instant

> Send WordPress post notifications directly to Discord — fast, minimal, and role-aware.

**Automatic alerts. Role mentions. Zero bloat.**

[![Version](https://img.shields.io/badge/stable-v1.1-blue.svg)](https://wordpress.org/plugins/init-pulse-for-discord/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

**Init Pulse For Discord** automatically sends post publish/update notifications to your Discord server using webhooks.

- Clean, minimal implementation  
- Zero external SDKs  
- Custom Post Type support — not just standard posts  
- Rich Discord embeds, with a plain-text fallback mode  
- Supports Discord role mentions via Categories & Tags  
- Markdown-ready message templates  

Perfect for blogs, communities, editorial teams, or any WordPress site connected to a Discord audience.

## Features

- Auto-send notifications when content is **published**
- Optional notifications when content is **updated**
- **Custom Post Type support**  
  → Choose exactly which post types (posts, pages, products, or any custom type) trigger a notification
- Role-aware mentions  
  → Assign Role IDs to categories/tags for targeted alerts
- **Rich Discord embeds**  
  → Title, description, accent color, footer, and timestamp — with a plain-text fallback toggle
- **Send Test Message** button on the settings screen  
  → Verify your webhook instantly, no need to publish a post or save first
- **Delivery Log**  
  → Rolling snapshot of the most recent deliveries (success/error) for troubleshooting, stored lightly in `wp_options` with `autoload = no`
- Customizable message template with placeholders:
  - `{title}`
  - `{title_url}`
  - `{url}`
  - `{excerpt}`
  - `{site_name}`
  - `{author}`
  - `{categories}`
  - `{tags}`
  - `{post_type}`
  - `{date}`
- Optional featured image embed
- Timeout & retry logic (includes `429 Retry-After`)
- Lightweight codebase with no DB clutter
- No frontend JS/CSS — backend-only, fast by design
- Safe-by-default (doesn’t override hooks)

## How It Works

1. You paste your Discord webhook URL, hit **Send Test Message** to confirm it works  
2. Choose which post types should be tracked  
3. (Optional) Add Discord Role IDs to categories/tags  
4. When tracked content is published or updated → webhook triggers instantly  
5. Discord receives a clean, formatted embed (or plain-text message, if you prefer)

No noise. No extra dependencies. Just pure webhook efficiency.

## Settings

Navigate to:

```
Settings → Init Pulse For Discord
```

Available fields:

- **Post Types** — which post types trigger a notification  
- **Webhook URL** — with a built-in Send Test Message button  
- **Bot Username**  
- **Avatar URL**  
- **Include Featured Image**  
- **Use Rich Embed** — toggle formatted embed vs. plain text  
- **Embed Color**  
- **Timeout / Retries**  
- **Message Template (Markdown)**  

The settings screen also shows a **Delivery Log** with the outcome of your most recent notifications.

Role IDs are configured inside:

- Posts → **Categories** → Edit  
- Posts → **Tags** → Edit  

## Developer Filters

### `init_plugin_suite_pulse_for_discord_payload`

Modify the final webhook payload before sending.

**Params:**  
`array $payload`, `int $post_id`, `string $context`

**Example:**

```php
add_filter( 'init_plugin_suite_pulse_for_discord_payload', function( $payload ) {
    $payload['content'] .= "\n— Sent from my custom filter";
    return $payload;
});
```

## Installation

1. Upload plugin folder to `/wp-content/plugins/`
2. Activate under **Plugins → Init Pulse For Discord**
3. Paste your webhook URL in **Settings → Init Pulse For Discord** and hit **Send Test Message**
4. Choose which post types to track
5. (Optional) Assign role IDs to categories/tags

That's it. Your Discord now receives WordPress updates instantly.

## License

GPLv2 or later — open source, minimal, developer-first.

## Part of Init Plugin Suite

Init Pulse For Discord is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of blazing-fast, no-bloat plugins made for WordPress developers who care about quality and speed.
