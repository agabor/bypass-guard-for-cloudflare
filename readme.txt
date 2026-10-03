=== Bypass Guard for Cloudflare ===
Contributors: gaborangyal
Tags: cloudflare, security, firewall, origin, header
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks requests that bypass Cloudflare by requiring a secret token header. For hosts that offer no stronger origin protection.

== Description ==

When your site is behind Cloudflare, visitors are supposed to reach it through Cloudflare's network. But if someone discovers your server's real IP address, they can send requests to it directly and skip Cloudflare entirely, along with its firewall, rate limiting, bot protection and caching.

Bypass Guard for Cloudflare closes that gap with a shared secret. You configure Cloudflare to add a header containing a secret token to every request it forwards to your server, and the plugin rejects WordPress requests that do not carry the correct token.

**How it works**

1. When the plugin is first activated, it generates a random secret token.
2. You create a Request Header Transform Rule in your Cloudflare dashboard that sets a static header named `BYPASS-GUARD-TOKEN` to that token.
3. The plugin watches incoming requests and shows on its settings page whether the header has been detected with the correct value.
4. Once the header is detected, you can enable the filter. From then on, requests without the correct token receive a 403 Forbidden response.

The filter cannot be enabled until the header has been detected. This prevents you from locking yourself out of your own site by enabling it before Cloudflare is configured.

**Logging blocked requests**

Every request the filter blocks is recorded in a log that you can review on the plugin's settings page. Each entry contains:

* The date and time of the request.
* The IP address that connected to your server.
* The request method and URL.
* The user agent.

The log helps you confirm that the filter is working, spot scanners or attackers that have found your server's real IP address, and diagnose legitimate services that are being blocked by mistake.

Only the most recent 500 entries are kept, so a flood of blocked requests cannot fill up your database. You can clear the log at any time from the settings page. Token values are never written to the log, even when a request sends an incorrect one.

The IP address recorded is the address that connected directly to your server. Because blocked requests did not come through Cloudflare, this is the real source address. The plugin deliberately ignores headers such as `CF-Connecting-IP` and `X-Forwarded-For` for blocked requests, since anyone bypassing Cloudflare can set those headers to any value.

**When to use this plugin**

This plugin is a fallback. Use it only if your hosting environment supports neither of these stronger options:

* **Authenticated Origin Pulls** (mutual TLS), where your web server verifies a client certificate presented by Cloudflare.
* **Restricting your firewall to Cloudflare's IP ranges**, so the server refuses connections from anywhere else.

Both of these work at the server or network level and protect everything, including static files. If your host allows either one, use it instead of, or in addition to, this plugin. Many shared hosting providers allow neither, which is the situation this plugin is designed for.

**Limitations**

Because this is a WordPress plugin, it can only filter requests that WordPress itself handles. In particular:

* **Static files** such as images, CSS, JavaScript and uploads are usually served directly by the web server without loading WordPress, so they remain accessible directly.
* **Page caching** that serves pages before WordPress plugins load is not filtered. This includes caching plugins that use an `advanced-cache.php` drop-in, as well as server-level caches such as LiteSpeed Cache, Varnish or nginx FastCGI cache. Cached pages may still be served to direct requests.
* **The token is a shared secret.** Anyone who learns it can bypass Cloudflare. It is visible to site administrators, to anyone with access to your database, and to anyone with access to your Cloudflare account.

If you can edit your server configuration or `.htaccess` file, checking the header there as well will also cover static files and cached pages.

**Source code and contributions**

Bypass Guard for Cloudflare is developed openly on GitHub. Bug reports, feature requests and pull requests are welcome:

https://github.com/agabor/bypass-guard-for-cloudflare

**Disclaimer**

Bypass Guard for Cloudflare is an independent, open source project. It is not affiliated with, endorsed by, or supported by Cloudflare, Inc. "Cloudflare" is a trademark of Cloudflare, Inc. and is used here only to describe what the plugin works with.

== Installation ==

1. Install and activate the plugin from the Plugins screen, or upload the `bypass-guard-for-cloudflare` folder to `/wp-content/plugins/` and activate it.
2. Go to the plugin's settings page and copy the generated token.
3. In the Cloudflare dashboard, open your site (zone) and go to **Rules → Overview**.
4. Select **Create rule → Request Header Transform Rule**.
5. Give the rule a name, for example "Bypass Guard".
6. Under the match condition, choose **All incoming requests**.
7. Under **Modify request header**, choose **Set static**, enter `BYPASS-GUARD-TOKEN` as the header name and paste your token as the value.
8. Select **Deploy**.
9. Return to the plugin's settings page and reload it. When the header is detected, the status will change and the option to enable the filter will become available.
10. Enable the filter.
11. Test that direct access is blocked by sending a request straight to your server's IP address, bypassing Cloudflare. It should receive a 403 Forbidden response.

**Required Cloudflare settings**

* Your DNS records for the site must be **proxied** (orange cloud). Requests to DNS-only records do not pass through Cloudflare and will not carry the header.
* Your SSL/TLS encryption mode should be **Full (strict)**. In Flexible mode, Cloudflare connects to your server over unencrypted HTTP, and the token would be sent in plain text with every request.

== Frequently Asked Questions ==

= Is this plugin made by Cloudflare? =

No. It is an independent open source plugin with no connection to Cloudflare, Inc. Please do not contact Cloudflare support about it.

= Why should I prefer Authenticated Origin Pulls or an IP firewall? =

They are enforced by your web server or network before any request reaches WordPress, so they protect everything, including static files and cached pages. They also do not rely on a shared secret. This plugin works at the WordPress level only and is intended for hosting environments where those options are not available.

= I locked myself out. How do I disable the plugin? =

Connect to your site using FTP, SFTP or your hosting control panel's file manager, and rename the folder `/wp-content/plugins/bypass-guard-for-cloudflare` to something else, such as `bypass-guard-for-cloudflare-disabled`. WordPress will deactivate the plugin automatically. Fix your Cloudflare rule, rename the folder back and reactivate the plugin.

= Why does the settings page say the header was not detected? =

Check that:

* The Transform Rule is deployed and matches all incoming requests.
* The header name is exactly `BYPASS-GUARD-TOKEN` and the value matches the token shown in the plugin, with no extra spaces.
* You are visiting the site through its domain name, not the server's IP address.
* The site's DNS records are proxied (orange cloud).

Some hosting environments place their own proxy in front of WordPress that may remove unfamiliar headers. If the header never arrives despite a correct Cloudflare rule, ask your host whether custom request headers are passed through.

= Will this block WP-Cron, Site Health or other loopback requests? =

Loopback requests that WordPress makes to itself through your domain normally travel through Cloudflare and carry the header. On some hosts, however, the server resolves its own domain to a local address and the request skips Cloudflare. If scheduled tasks or Site Health checks start failing after you enable the filter, this is the likely cause. Consider switching to a real server cron job that runs WP-Cron without an HTTP request.

= Will external services that connect to my server directly be blocked? =

Yes, if they connect to your server's IP address rather than through your domain on Cloudflare. Services such as uptime monitors, payment gateway callbacks and webhooks normally use your domain and pass through Cloudflare, but check any integration that is configured with your server's IP address.

= Does the log store personal data? =

Yes. The log stores IP addresses and user agents of blocked requests, which may count as personal data under privacy laws such as the GDPR. The data is stored only in your own WordPress database and is never sent anywhere else. Only the most recent 500 entries are kept, and you can clear the log at any time. Depending on your jurisdiction, you may need to mention this logging in your site's privacy policy, typically as security logging based on legitimate interest.

= Is the token secure? =

The token is a random value that is compared in constant time. However, it is a shared secret: it is stored in your WordPress database, displayed to administrators, and stored in your Cloudflare account. Treat it like a password and keep your SSL/TLS mode set to Full (strict) so it is never transmitted unencrypted.

== Changelog ==

= 1.0.0 =
* Initial release.
* Token generation, header detection and request filtering.
* Logging of blocked requests, limited to the most recent 500 entries.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
