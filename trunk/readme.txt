=== xZeroProtect ===
Contributors: benkhalifedev
Tags: firewall, security, bot-protection, analytics, waf
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight firewall for WordPress — blocks bots and scanners, tracks real visitor analytics with zero external dependencies.

== Description ==

xZeroProtect brings the power of the [xZeroProtect PHP library](https://github.com/webrium/xzeroprotect) to WordPress with a clean admin dashboard. The plugin source is available at [github.com/webrium/xzeroprotect-wp](https://github.com/webrium/xzeroprotect-wp).

**What it does:**

* Blocks bots, scanners, and common web attacks (SQLi, XSS, path traversal, command injection)
* Rate-limits IPs and automatically bans repeat offenders
* Verifies legitimate crawlers (Googlebot, Bingbot) via double-DNS — they're never blocked
* Tracks **real** visitor analytics — bot traffic is already filtered out before anything is recorded
* Shows unique visitors, top pages, device breakdown, and block reasons in a dashboard
* Zero external dependencies — no Redis, no external API, everything on disk and in your database

**Dashboard includes:**

* Traffic overview chart (visits, unique visitors, blocked)
* Top pages by hits and unique visitors
* Device breakdown (desktop / mobile / tablet)
* Block reason breakdown
* Real visitor log with browser, OS, and device info
* Blocked request log with attack type and reason

== Installation ==

1. Upload the plugin via **Plugins → Add New Plugin → Upload Plugin** and select the plugin zip file, or extract the `xzeroprotect` folder into `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **xZeroProtect → Settings** to configure

== Frequently Asked Questions ==

= Will this block me from my own admin? =

No. The plugin automatically whitelists `/wp-admin`, `/wp-login.php`, and other WordPress core paths. Logged-in administrators are also exempt.

= Does it work on shared hosting? =

Yes — that's one of its main advantages. No Redis, no system-level access, no external services required.

= What happens to my data if I deactivate the plugin? =

Data is kept on deactivation. It is only removed when you **delete** the plugin (uninstall).

== Privacy Policy ==

xZeroProtect stores visitor data (IP address, browser, OS, device type) and blocked
request data locally in your WordPress database. No data is transmitted to external
servers. All stored data is automatically deleted after the configured retention period
(default: 30 days). All data is permanently removed when the plugin is uninstalled.

== Changelog ==

= 1.1.4 =
* Fix: Blocked Requests page was always empty — now reads directly from the xZeroProtect library's log file, so both blocked and suspicious (Learning Mode) requests are shown.
* Fix: Static assets (favicon.ico, CSS, JS, fonts, images) were inflating the rate-limit counter, causing premature bans. A single page view now counts as one request.
* New: Hint added above the rate-limit fields in Settings explaining what counts as a request.

= 1.1.3 =
* Renamed plugin slug from xzeroprotect-wp to xzeroprotect (resolves trademarked-term warning for the "wp" suffix)
* Fixed Text Domain to match the new slug ("xzeroprotect") across all strings
* Renamed main plugin file to xzeroprotect.php
* Removed the unused "Domain Path" header (no languages folder bundled)
* Moved firewall storage directory from uploads/xzeroprotect-wp to uploads/xzeroprotect
* Sanitized $_POST['days'] in AJAX handlers before casting
* Added phpcs ignore annotations for safe, already-prepared direct DB queries
* Renamed internal constants from XZPWP_* to XZP_*

= 1.1.2 =
* Updated bundled Chart.js to v4.5.1
* Moved firewall storage to the WordPress uploads directory (wp_upload_dir())
* Replaced inline dashboard <script> with wp_add_inline_script
* Removed unnecessary load_plugin_textdomain() call (handled by WordPress.org since 4.6)
* Removed directory asset files from the plugin package

= 1.1.1 =
* Added real visitor tracking with device and browser detection
* Added unique visitor fingerprinting (daily-resetting SHA-256)
* Added analytics dashboard: traffic chart, top pages, device breakdown, block reasons
* Added real visitor log and blocked request log
* Removed curl, wget, python-requests, go-http-client from default blocked agents
* Removed .php extension from default blocked paths to avoid false positives
* Raised auto-ban violations threshold from 5 to 10

= 1.0.0 =
* Initial release
