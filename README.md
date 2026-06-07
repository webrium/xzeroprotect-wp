<div align="center">

<br/>

# xZeroProtect for WordPress

**A lightweight firewall plugin that actually knows the difference between real visitors and bots.**  
Block attacks. Track real traffic. Zero external dependencies.

<br/>

[![WordPress](https://img.shields.io/badge/WordPress-%3E%3D%206.0-21759B?style=flat-square&logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-8892BF?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-22c55e?style=flat-square)](LICENSE)
[![Zero Dependencies](https://img.shields.io/badge/External%20Dependencies-Zero-f59e0b?style=flat-square)]()
[![Powered by](https://img.shields.io/badge/Powered%20by-xzeroprotect-3b82f6?style=flat-square)](https://github.com/webrium/xzeroprotect)

<br/>

</div>

---

## What is this?

Most security plugins tell you how many requests they blocked. This one also tells you how many **real humans** actually visited your site — because it filters bot traffic before recording anything.

xZeroProtect for WordPress brings the [xZeroProtect PHP firewall library](https://github.com/webrium/xzeroprotect) into WordPress with a clean admin dashboard, database-backed analytics, and settings that make sense.

---

## Features

### 🛡 Firewall

- Blocks bots, scanners, and exploit tools by User-Agent signature
- Detects and blocks SQLi, XSS, path traversal, LFI/RFI, and command injection in request payloads
- Blocks requests to sensitive paths (`.env`, `phpmyadmin`, web shells, config files, and more)
- Rate-limits IPs with a sliding-window counter — no Redis required
- Auto-bans repeat offenders; escalates to permanent ban after N violations
- Verifies legitimate crawlers (Googlebot, Bingbot, and others) via **double-DNS** before granting trust — so search engine crawlers are never accidentally blocked
- Syncs permanent bans to `.htaccess` so Apache rejects them before PHP runs (optional)

### 📊 Real Visitor Analytics

- Tracks only traffic that passed **all** firewall checks — bots and scanners never appear in your stats
- Unique visitor identification via daily-resetting SHA-256 fingerprint (privacy-safe — raw IPs are never stored in the fingerprint)
- Per-visit: browser, browser version, OS, OS version, device type (desktop / mobile / tablet)
- Traffic overview chart with 7 / 14 / 30-day range selector
- Top pages by total hits and unique visitors
- Device breakdown with visual bars
- Block reason breakdown (rate limit, bad User-Agent, payload attack, banned IP, and more)
- Real visitor log and blocked request log with full detail

### ⚙️ Settings

- Firewall mode: **Production** (block + log) · **Learning** (log only) · **Off**
- Toggle each detection module independently
- Whitelist IPs (exact or CIDR) and paths
- Configure rate-limit window and threshold
- Configure auto-ban duration and escalation
- Choose block response HTTP code (403 / 429 / 503)
- Data retention — old records pruned automatically via WP-Cron
- WordPress core paths (`/wp-admin`, `/wp-login.php`, `/wp-json`) are always safe — you can never lock yourself out

---

## Screenshots

| Dashboard | Real Visitors | Blocked Requests | Settings |
|-----------|--------------|-----------------|----------|
| Traffic chart, stat cards, top pages, device breakdown | Verified human visits with browser and device info | Blocked requests with attack type and reason | Full firewall configuration |

---

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress   | 6.0+    |
| PHP         | 8.0+    |
| MySQL       | 5.7+ / MariaDB 10.3+ |

No Redis. No external API. No cloud service. Everything runs on your own server.

---

## Installation

### From WordPress Admin (recommended)

1. Download the latest release zip from [Releases](../../releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate the plugin
5. Go to **xZeroProtect → Settings** to configure

### Manual

```bash
# Clone into your plugins directory
cd /path/to/wp-content/plugins
git clone https://github.com/webrium/xzeroprotect-wp.git

# Install the PHP library dependency
cd xzeroprotect-wp
composer install --no-dev --optimize-autoloader
```

Then activate from **Plugins → Installed Plugins**.

---

## How it works

```
Incoming request
       │
       ▼
┌─────────────────────────────────┐
│         xZeroProtect            │
│                                 │
│  1. Whitelisted IP/path? ──────────────────────────► Pass through
│  2. Verified crawler?   ──────────────────────────► Pass through
│  3. Banned IP?          ──────────────────────────► Block
│  4. Rate limit exceeded?──────────────────────────► Block + violation
│  5. Suspicious path?    ──────────────────────────► Block + violation
│  6. Bad User-Agent?     ──────────────────────────► Block + violation
│  7. Payload attack?     ──────────────────────────► Block + violation
│  8. Custom rules?       ──────────────────────────► Block / Log / Pass
│                                 │
│  All checks passed ─────────────────────────────── ► Record real visit ✓
└─────────────────────────────────┘
```

Only requests that reach step 8 without being blocked are recorded as real visits.

---

## Plugin Structure

```
xzeroprotect-wp/
├── xzeroprotect-wp.php          # Plugin bootstrap and header
├── uninstall.php                # Cleanup on plugin deletion
├── composer.json
├── readme.txt                   # WordPress.org readme
├── includes/
│   ├── class-database.php       # MySQL tables, queries, stats
│   ├── class-firewall.php       # Library ↔ WordPress bridge
│   ├── class-settings.php       # Settings via wp_options
│   └── class-admin.php          # Admin menus, AJAX, form handling
├── admin/views/
│   ├── dashboard.php            # Analytics dashboard
│   ├── visitors.php             # Real visitor log
│   ├── blocked.php              # Blocked request log
│   └── settings.php            # Settings page
└── assets/
    ├── css/admin.css
    └── js/admin.js
```

---

## Data & Privacy

- Visitor fingerprints are SHA-256 hashes of IP + User-Agent + date — the raw IP cannot be recovered from them
- Fingerprints reset daily — no long-term cross-session tracking
- All data is stored in your own WordPress database — nothing is sent externally
- Data is automatically pruned after the configured retention period (default: 30 days)
- All data is removed cleanly when the plugin is deleted

---

## Powered by

This plugin is a WordPress integration layer for the **[xZeroProtect](https://github.com/webrium/xzeroprotect)** PHP library — a standalone, framework-agnostic firewall that works in any PHP 8.0+ application.

---

## License

Released under the [MIT License](LICENSE).  
Built by [Webrium](https://github.com/webrium).
