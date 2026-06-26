=== DiceStack ===
Contributors: dicecodes
Tags: security, performance, seo, woocommerce, optimization
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.5.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

One plugin, 180+ free tools: caching, security, SEO, WooCommerce, backups, object cache, image optimization and more. Turn on only what you need.

== Description ==

**DiceStack** replaces a whole stack of separate (and often paywalled) plugins with one lightweight, modular toolkit. Switch tools on and off from a single dashboard — when a tool is off, none of its code runs, so your site stays fast.

Every feature is free. There is no locked "Pro" tier.

Built and maintained by **[Dice Codes](https://dicecodes.com)**.

= Why DiceStack =

* **Modular & light** — disabled modules load zero code, scripts, or extra database reads.
* **See the cost of everything** — each module shows its memory, front-end JavaScript, and database-query impact so you decide what to keep.
* **No paywalls** — 2FA-grade login protection, schema, spam protection, caching, backups, and more, all included.
* **Self-hosted first** — runs on your own server. Modules that contact an external service ask for consent and link to where to get any keys.

= What's included (180+ modules across 10 categories) =

* **Security** — login protection, **change the wp-login URL (hide login)**, login & comment captcha, security hardening, security headers, force HTTPS, activity log, 404 monitor, bad-bot blocking, Cloudflare control (Under Attack mode, cache purge), and more.
* **Performance** — **page caching with gzip, mobile cache and preloading**, **Redis / Memcached object cache**, minify HTML, **minify CSS & JavaScript**, defer JavaScript, lazy loading, image optimization, database cleanup, hover prefetch, disable bloat, and more.
* **SEO & AI** — meta tags, schema/JSON-LD, XML-friendly robots, breadcrumbs, FAQ schema, site verification, **Analytics (GA4, Tag Manager, Meta Pixel)**, llms.txt for AI assistants, and a free SEO/AI visibility checker.
* **Forms** — contact form, spam shield, SMTP email, newsletter signup, and a universal submissions tracker that logs Contact Form 7 / WPForms / Gravity / Forminator / Elementor entries in one place.
* **WooCommerce** — checkout field editor, custom order statuses, wishlist, back-in-stock alerts, product badges, catalog mode, quick quantity buttons, and more.
* **Media** — image optimizer, WebP/AVIF uploads, media folders, SVG (sanitized), broken-image finder.
* **Content & marketing** — popups, cookie consent, maintenance mode with shareable bypass link, reviews showcase (no API key), table of contents, related posts, and more.
* **Site management** — backup & restore (no upload limit), cloud backup (FTP/WebDAV/email/Google Drive), monthly client report, update notifications, search & replace, import/export config.
* **Admin & developer** — admin menu editor, hide notices, white label, file manager, master search, shortcode generator, custom code, and more.
* **Accessibility & legal** — accessibility toolbar, skip links, focus outlines, reduced-motion, cookie consent, terms consent.

== External services ==

DiceStack itself contacts no external services by default. The following modules connect to a third party **only when you enable and configure them**, and each field links to where to obtain any required ID/token:

* **Analytics & tracking** — when you add a GA4, Google Tag Manager, or Meta Pixel ID, the corresponding script from Google/Meta is loaded on your front end and visitor analytics is sent to that provider. See Google's privacy policy (https://policies.google.com/privacy) and Meta's (https://www.facebook.com/privacy/policy/).
* **Cloudflare control** — sends your API token and Zone ID to the Cloudflare API (https://api.cloudflare.com) to change settings you request. Cloudflare privacy policy: https://www.cloudflare.com/privacypolicy/.
* **Cloud backup** — uploads your backup archive to the destination you configure (your own FTP/WebDAV server, an email address, or Google Drive). Google Drive uses a one-time connect via the Dice Codes connector (https://dicecodes.com/dicestack); only an access token is stored.
* **Reviews showcase (embed mode)** — if you paste a third-party reviews widget embed code, that provider's script loads on your front end. Manual mode sends nothing.

No data is sent to any of these unless you turn the relevant module on and enter your details.

== Installation ==

1. In wp-admin go to **Plugins → Add New → Upload Plugin** and choose `dicestack.zip` (or install from the directory).
2. Activate **DiceStack**.
3. Open **DiceStack** in the admin menu and toggle on the modules you want. Each card shows its performance cost.

== Frequently Asked Questions ==

= Does an inactive module slow my site down? =
No. When a module is off, its code is never loaded — no hooks, scripts, or database reads. The only baseline cost is one cached option read.

= Is it really free? =
Yes. Every module is free, with no locked features.

= Do I need API keys? =
Most modules need nothing. A few optional ones (Analytics, Cloudflare, Google Drive backup) need an ID or token — each field links to exactly where to get it.

= How do I request a feature? =
Use the "Request a feature" link in the DiceStack dashboard, or email Contact@dicecodes.com.

= Who makes DiceStack? =
DiceStack is built and maintained by Dice Codes (https://dicecodes.com).

== Screenshots ==

1. The modular dashboard — every tool shows its memory, JavaScript, and database cost, and turns on or off with one click.
2. Active tools list and category navigation in the sidebar.
3. A single module's settings open in a full-screen panel.
4. Recommended setup — DiceStack scans your site and enables the tools you actually need.
5. Agency Mode — manage white-label, backups, reports, and updates for client sites in one place.
6. Object cache — install a Redis or Memcached drop-in when your server supports it.

== Changelog ==

= 1.5.3 =
* Changed: docs now live at dicecodes.com/dicestack/docs/ — "Docs & guides" and every per-tool Help link point there.


= 1.5.2 =
* Improved: WooCommerce tools now show a clear "WooCommerce is not installed" message with an Install link, instead of looking enable-able. Status reads "Needs WooCommerce".
* Fixed: cleared two Plugin Check sanitisation warnings in the File manager (download/delete).


= 1.5.1 =
* Added: bundled Dice Codes logo (SVG) now shows in the dashboard sidebar out of the box; drop a dice-codes-white.png in assets/img/ to override it.


= 1.5.0 =
* Added: Dice Codes logo in the dashboard brand card (drop your logo at assets/img/dice-codes-white.png) with a graceful fallback; logo also added to the landing-page template.


= 1.4.9 =
* Fixed: tools with their own page (Cloudflare, Cloud backup, Image optimizer, Reviews, Monthly report, Error monitor, Backups, Admin menu editor) now show their settings fields right on the page — no more "configure elsewhere" dead ends.


= 1.4.8 =
* Added: a confirmation warning before enabling a tool that overlaps with another active plugin (e.g. a second cache or SEO plugin).
* Changed: plugin and docs links now point to the DiceStack page on dicecodes.com.


= 1.4.7 =
* Compliance: File manager uploads now use WordPress's wp_handle_upload(); fixed output escaping and input unslash/sanitize & nonce annotations flagged by Plugin Check.


= 1.4.6 =
* Fixed: card footers now truly align across every row (a duplicate CSS rule was overriding the alignment).


= 1.4.5 =
* Improved: card grid alignment is now bulletproof (footers line up across every row).


= 1.4.4 =
* Redesigned the File manager: clickable breadcrumb, in-folder search, colour-coded file-type icons, a Modified column, folders-first sorting, and a cleaner panel layout.


= 1.4.3 =
* Improved: even card heights — the Settings/Help row now lines up across every tool.
* Changed: the .htaccess editor now opens in the same inline modal as other tools (auto-backup on save).


= 1.4.2 =
* Fixed: File manager Rename was rendering raw code — rebuilt cleanly with a tidy, modern layout (upload, download, rename, delete, new folder).
* Added: enabling a tool that adds its own admin page now reloads and offers a one-click "Open" so you can jump straight to it.


= 1.4.1 =
* Added: Essential & Recommended tags on tools, and each category now lists its most important tools first.


= 1.4.0 =
* Added: .htaccess editor with automatic one-click backup & restore.
* Improved: File manager now supports upload, download, rename, delete and create-folder (was browse/edit only).
* Added: a confirmation toast when you enable a tool — shows what it does with quick Configure & How-to links.
* Fixed: final Plugin Check warning (DISALLOW_FILE_EDIT constant).


= 1.3.4 =
* Fixed: removed the horizontal scrollbar and duplicate heading when a dedicated tool (Agency Mode, Backups, etc.) opens in the modal — now flush and clean.


= 1.3.3 =
* Changed: Agency Mode and Recommended setup now open in the same centered modal as every other tool (no more jumping to a differently-styled page).


= 1.3.2 =
* Changed: every tool now opens its settings in the centered modal (dedicated tools load inside it too) — consistent Settings + Help buttons on every card.
* Improved: "Needs server support" only appears when a tool is actually unsupported.


= 1.3.1 =
* Added: a Help link on every tool that opens the matching how-to in the DiceStack docs.
* Improved: narrower sidebar.


= 1.3.0 =
* Renamed to DiceStack (the previous name could not be used on WordPress.org). Same plugin, new name — one install replaces your whole stack.


= 1.2.3 =
* Fixed: dashboard CSS/JS are now inlined, so a cached/CDN-stale copy can never break the sidebar, Active tools, or Settings buttons.
* Compliance: cleared remaining Plugin Check warnings (backup nonce annotations, search-replace query); removed the auto-update-control tool (core WordPress already covers it).


= 1.2.2 =
* Compliance: resolved Plugin Check findings — translator comments, output escaping, prepared-SQL annotations, WP_Filesystem/get_posts adjustments, GA4 now enqueued, removed manual textdomain loading.


= 1.2.1 =
* Fixed: Diagnostics page fatal error (namespace resolution).


= 1.2.0 =
* Added: Safe Mode & module isolation — if a tool errors, DiceStack auto-disables just that tool so your site never white-screens (define DICESTACK_SAFE_MODE in wp-config.php for full recovery).
* Added: WordPress Site Health checks and a Diagnostics page (copy-for-support system info).
* Added: WP-CLI commands (wp dicestack list/status/enable/disable/clear-cache).
* Fixed: admin assets now cache-bust by file modification time; wider sidebar so labels stay on one line.


= 1.1.12 =
* Improved: the "Scan my site" banner is now dismissible; "Active tools" and "Needs server support" views show a clear message when empty; "Needs server support" is always reachable from the sidebar.


= 1.1.11 =
* Compliance: removed the self-update mechanism for WordPress.org (the directory handles updates automatically).
* Fixed: i18n issue in custom order statuses; updated "Tested up to".


= 1.1.10 =
* Added: "Docs & guides" link to https://dicecodes.com/dicestack-wordpress-plugin/ across the plugin.


= 1.1.9 =
* Improved: Recommended setup now detects your existing SEO/cache/security/backup plugins (Rank Math, WP Rocket, Wordfence, UpdraftPlus, etc.) and will not recommend tools that would clash — and a fresh install no longer auto-enables them either.
* Improved: professional, redesigned onboarding screen.
* Changed: "Active tools" and "Needs server support" are now compact sidebar views instead of a long list.


= 1.1.8 =
* Added: cache preloading — after the cache clears, DiceStack quietly re-warms your home page and recent posts so visitors always hit a cached page.
* Added: Docs & guides link to the DiceStack help page.


= 1.1.7 =
* Added: Object cache (Redis / Memcached) with a fail-safe drop-in — shown only when your server supports it.
* Added: Change login URL — move wp-login.php to a secret slug, 404 the old one, and email the new link to your client.
* Added: Server capability detection — tools that need Redis, an image library, ZipArchive, etc. now show whether your host supports them and how to enable them, instead of failing silently.
* Improved: page caching now supports gzip, a separate mobile cache, and cookie/user-agent exclusions (WP Rocket-style options).
* Fixed: checkbox settings can now be turned off (were stuck on).


= 1.1.6 =
* Added: Minify CSS (inline + cached local files, with url() rewriting) and Minify JavaScript (string-safe, ASI-safe) — caching-plugin parity.
* Added: Clear log button on the Changes page.
* Improved: settings panel now opens centered on screen.


= 1.1.5 =
* Added: Recommended setup — scans your site and enables the tools you really need in one click.
* Added: "Active tools" list in the sidebar showing every enabled module with quick jump to its settings; updates live as you toggle.
* Fixed: sidebar labels render on a single line; removed a stray icon next to Agency Mode.


= 1.1.4 =
* Fixed: sidebar labels now stay on one line; "Open settings" no longer shown for a disabled page-module (could 404); Agency Mode now toggles each tool on/off with live status and enable/disable all.


= 1.1.3 =
* Changed: comparison labels now use generic categories (e.g. "premium SEO plugins") instead of specific product names, for trademark-safe distribution on both WordPress.org and GitHub.
* Verified: full syntax + integration sweep, no errors.

= 1.1.2 =
* Added: Agency Mode control panel — enable and manage white-label, backups, client reports, audit logging, and updates from one place.

= 1.1.1 =
* Improved: wider sidebar with single-row labels; module settings now open in a full-screen modal; modules with their own page open it directly; live Enabled/Disabled counts; clearer settings button.
* Added: placement controls (before / after content / shortcode) for Related posts and Author box so you choose where they appear.

= 1.1.0 =
* Added: Theme Styler with a point-and-click visual editor (style any element live on the front end).
* Added: Analytics module (GA4, Google Tag Manager, Meta Pixel) and Cloudflare control (Under Attack mode, cache purge, dev mode).
* Added: Cloud backup (FTP/WebDAV/email/Google Drive), monthly client report, universal form-submissions tracker, error monitor, 404 monitor, master search, file manager, and a self/GitHub updater for non-directory installs.
* Added: "Changes" log, bulk enable/disable, clear-cache button, bulk image optimizer with progress, and front-end custom CSS.
* Improved: dashboard scrolling, expandable sidebar (collapsed by default), professional settings UI, guide links under key fields, and Dice Codes branding.
* Hardened: backup restore (Zip-Slip guard), config import sanitisation, SVG sanitiser, login/comment captchas fail-open, and credential fields.

= 1.0.0 =
* Initial release: modular framework with a performance-transparency dashboard and 175+ modules across security, performance, SEO/AI, forms, WooCommerce, media, content, site management, admin, and accessibility.

== Upgrade Notice ==

= 1.1.0 =
Adds the visual Theme Styler, Analytics, Cloudflare control, cloud backup, and many hardening fixes.

= 1.0.0 =
First public release of DiceStack.
