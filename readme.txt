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

**The problem**

Cloudflare protects your site only for traffic that goes through Cloudflare. If your server's IP address leaks, attackers can send requests to it directly and skip Cloudflare's protection entirely. The usual fixes require server or firewall access, which shared hosting providers often don't give you.

**The solution**

Bypass Guard uses a shared secret to tell proxied traffic from direct traffic:

1. The plugin generates a random secret token.
2. You add a rule in Cloudflare that attaches this token to every request it forwards, in a header called `BYPASS-GUARD-TOKEN`.
3. The plugin checks each WordPress request. If the header is missing or wrong, the request is answered with `403 Forbidden`.

Because the header is added at Cloudflare's edge, a request that hits the origin directly arrives without it, unless the sender already knows the token.

**Built-in safety against locking yourself out**

* The filter cannot be switched on until the plugin has seen at least one request carrying the correct token. This proves your Cloudflare rule works before anything gets blocked.
* Until then, the settings page shows a step by step setup guide with copy buttons for the header name and token.
* WP-CLI commands are never filtered, so you always keep command line access.
* Deactivating the plugin always switches the filter off.

**Logging**

Every blocked request is logged with its date and time, IP address, request method, URL and user agent. You can view and clear the log on the settings page. This helps you confirm that the filter works, spot scanners that found your server's IP, and find legitimate services that were blocked by mistake.

The log keeps the 500 most recent entries, so a flood of requests cannot fill your database. Token values are never logged, not even incorrect ones.

The logged IP address is the one that actually connected to your server. Headers such as `CF-Connecting-IP` or `X-Forwarded-For` are ignored for blocked requests, because anyone bypassing Cloudflare can set them to any value.

**Is this plugin right for you?**

This plugin is a fallback for when better options are not available. Check with your host whether you can use either of these first:

* **Authenticated Origin Pulls** (mutual TLS): your web server only accepts connections that present Cloudflare's client certificate.
* **A firewall that only allows Cloudflare's IP ranges**: your server refuses connections from anywhere else.

Both work at the server or network level, protect everything (including images and cached pages) and do not depend on a shared secret. Many shared hosting plans allow neither, and that is the situation this plugin is built for. You can also use it alongside them as an extra layer.

**Limitations**

The plugin runs inside WordPress, so it only sees requests that WordPress itself handles:

* **Static files** (images, CSS, JavaScript, uploads) are usually served directly by the web server and stay reachable.
* **Page caches that run before plugins load** are not filtered. This includes caching plugins using an `advanced-cache.php` drop-in and server-level caches such as LiteSpeed Cache, Varnish or nginx FastCGI cache. Cached pages may still be served to direct requests.
* **The token is a shared secret.** Anyone who learns it can get past the filter. It is visible to site administrators, to anyone with database access and to anyone with access to your Cloudflare account.

If you can edit your server configuration or `.htaccess` file, checking the same header there as well closes the first two gaps.

**Source code and contributions**

Bypass Guard for Cloudflare is developed openly on GitHub. Bug reports, feature requests and pull requests are welcome:

https://github.com/agabor/bypass-guard-for-cloudflare

**Disclaimer**

Bypass Guard for Cloudflare is an independent open source project. It is not affiliated with, endorsed by or supported by Cloudflare, Inc. "Cloudflare" is a trademark of Cloudflare, Inc. and is used here only to describe the service this plugin works with. No Cloudflare logos, artwork or documentation are included in this plugin.

== Installation ==

**Before you start**

Make sure that:

* Your site's DNS records are **proxied** in Cloudflare (orange cloud icon). DNS-only records send traffic straight to your server, so the header would never be added.
* Your SSL/TLS encryption mode is **Full (strict)**. In Flexible mode Cloudflare talks to your server over plain HTTP, which would send the token unencrypted with every request.
* You have FTP, SFTP, file manager or WP-CLI access to your site, in case you need to disable the plugin manually (see the FAQ).

**Step 1: Install the plugin**

Install and activate it from the Plugins screen, or upload the `bypass-guard-for-cloudflare` folder to `/wp-content/plugins/` and activate it. Then open **Settings → Bypass Guard**. You will see the header name and your generated token, each with a Copy button.

**Step 2: Add the header in Cloudflare**

Create a Request Header Transform Rule for your site that sets a static header on all incoming requests:

* Header name: `BYPASS-GUARD-TOKEN`
* Value: the token from the settings page

At the time of writing, you find this in the Cloudflare dashboard by opening your site (zone), going to **Rules → Overview** and choosing **Create rule → Request Header Transform Rule**. Give the rule any name, set the match condition to **All incoming requests**, choose **Set static** under the header modification, fill in the two values above and select **Deploy**. Cloudflare occasionally reorganises its dashboard; if these menu names do not match, look for "Transform Rules" in Cloudflare's own documentation.

**Step 3: Confirm detection**

Reload the plugin's settings page through your normal domain. Once a request with the correct token arrives, the Header Status changes to **Detected** and the **Enable Filter** button becomes available.

**Step 4: Enable the filter and test it**

Select **Enable Filter**. Then check that direct access is blocked by sending a request straight to your server's IP address. For example, from a terminal:

`curl -k -I --resolve example.com:443:203.0.113.10 https://example.com/`

Replace `example.com` with your domain and `203.0.113.10` with your server's real IP. The response should be `403 Forbidden`, and the request should appear in the plugin's log.

== Frequently Asked Questions ==

= Is this plugin made by Cloudflare? =

No. It is an independent open source plugin with no connection to Cloudflare, Inc. Please do not contact Cloudflare support about it; use the GitHub issue tracker instead.

= I locked myself out. How do I disable the plugin? =

Using FTP, SFTP or your hosting file manager, rename the folder `/wp-content/plugins/bypass-guard-for-cloudflare` to something else, such as `bypass-guard-for-cloudflare-disabled`. WordPress deactivates the plugin automatically, which also switches the filter off. Fix your Cloudflare rule, rename the folder back and reactivate the plugin.

With WP-CLI you can simply run `wp plugin deactivate bypass-guard-for-cloudflare`. WP-CLI is never filtered.

= Why does the settings page say the header was not detected? =

Check that:

* The Transform Rule is deployed and matches all incoming requests.
* The header name is exactly `BYPASS-GUARD-TOKEN` and the value matches the token in the plugin, with no extra spaces.
* You are visiting the site through its domain name, not the server's IP address.
* The site's DNS records are proxied (orange cloud).

Some hosts run their own proxy in front of WordPress that strips unfamiliar headers. If the header never arrives even though the Cloudflare rule is correct, ask your host whether custom request headers are passed through.

= What exactly does "Detected" mean? =

It means the plugin has received at least one request with the correct token since the token was generated. It is not a live check: if you later delete or change the Cloudflare rule, the status still shows Detected. Regenerating the token resets it.

= How do I change the token? =

Disable the filter, then select **Regenerate Token**. The plugin creates a new token and resets the status to Not detected. Update the value in your Cloudflare rule, reload the settings page until the header is detected again, then re-enable the filter.

The Regenerate Token button is only shown while the filter is disabled. Changing the token while the filter is on would immediately block every request, including your own, because Cloudflare would still be sending the old value.

= What happens when I deactivate the plugin? =

The filter is switched off. Your token, detection status and log are kept. When you activate the plugin again, the filter stays off until you enable it. If you changed your Cloudflare rule in the meantime, regenerate the token so that detection is checked again before you enable the filter.

= Will this block WP-Cron, Site Health or other loopback requests? =

Usually not. When WordPress calls itself through your domain, the request normally goes through Cloudflare and carries the header. On some hosts, however, the server resolves its own domain to a local address and skips Cloudflare. If scheduled tasks or Site Health checks start failing after you enable the filter, this is the likely cause. Switching to a real server cron job that runs WP-Cron without an HTTP request solves it.

= Will external services that connect to my server be blocked? =

Only if they connect to your server's IP address directly. Uptime monitors, payment gateway callbacks and webhooks normally use your domain and pass through Cloudflare. Check any integration that is configured with your server's IP address.

= Does the log store personal data? =

Yes. IP addresses and user agents may count as personal data under laws such as the GDPR. The data stays in your own WordPress database and is never sent anywhere. Only the 500 most recent entries are kept and you can clear the log at any time. Depending on your jurisdiction, you may need to mention this in your privacy policy, typically as security logging based on legitimate interest.

= How secure is the token? =

It is 24 hexadecimal characters (96 bits) generated with a cryptographically secure random number generator, and it is compared in constant time to prevent timing attacks. Guessing it is not practical. The realistic risk is leaking it, so treat it like a password, keep SSL/TLS set to Full (strict) and regenerate it if you think it has been exposed.

== Changelog ==

= 1.0.0 =
* Initial release.
* Token generation, header detection and request filtering.
* Token regeneration while the filter is disabled.
* Built-in setup guide with copy buttons for the header name and token.
* Logging of blocked requests, limited to the 500 most recent entries.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
