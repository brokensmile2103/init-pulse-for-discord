# Init Pulse For Discord – Webhooks, Roles, Instant
> Send WordPress post notifications directly to Discord — fast, minimal, and role-aware.

**Automatic alerts. Role mentions. Zero bloat.**

[![Version](https://img.shields.io/badge/stable-v1.0-blue.svg)](https://github.com/brokensmile2103/init-pulse-for-discord)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

**Init Pulse For Discord** automatically sends post publish/update notifications to your Discord server using webhooks.

- Clean, minimal implementation  
- Zero external SDKs  
- Supports Discord role mentions via Categories & Tags  
- Markdown-ready message templates  

Perfect for blogs, communities, editorial teams, or any WordPress site connected to a Discord audience.

## Features

- Auto-send notifications when posts are **published**
- Optional notifications when posts are **updated**
- Role-aware mentions  
  → Assign Role IDs to categories/tags for targeted alerts
- Customizable message template with placeholders:
  - `{title}`
  - `{title_url}`
  - `{url}`
  - `{excerpt}`
  - `{site_name}`
- Optional featured image embed
- Timeout & retry logic (includes `429 Retry-After`)
- Lightweight codebase with no DB clutter
- No frontend JS/CSS — backend-only, fast by design
- Safe-by-default (doesn’t override hooks)

## How It Works

1. You paste your Discord webhook URL  
2. (Optional) Add Discord Role IDs to categories/tags  
3. When a post is published or updated → webhook triggers instantly  
4. Discord receives a clean, formatted message or embed

No noise. No extra dependencies. Just pure webhook efficiency.

## Settings

Navigate to:

```

Settings → Init Pulse For Discord

````

Available fields:

- **Webhook URL**  
- **Bot Username**  
- **Avatar URL**  
- **Include Featured Image**  
- **Timeout / Retries**  
- **Message Template (Markdown)**  

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
````

## Installation

1. Upload plugin folder to `/wp-content/plugins/`
2. Activate under **Plugins → Init Pulse For Discord**
3. Paste your webhook URL in **Settings → Init Pulse For Discord**
4. (Optional) Assign role IDs to categories/tags

That's it. Your Discord now receives WordPress updates instantly.

## License

GPLv2 or later — open source, minimal, developer-first.

## Part of Init Plugin Suite

Init Pulse For Discord is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of blazing-fast, no-bloat plugins made for WordPress developers who care about quality and speed.
