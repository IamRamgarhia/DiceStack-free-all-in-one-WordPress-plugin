# DiceStack — Free All-in-One WordPress Plugin (185 Tools)

> **One plugin. Every tool. Always free.** DiceStack bundles **185 modular tools** for security, performance, SEO, WooCommerce, caching, backups, forms, and site management into a single lightweight dashboard — and you turn on **only what you need**. Disabled tools load **zero code**, so your site stays fast.

![Version](https://img.shields.io/badge/version-1.5.3-2563EB)
![License](https://img.shields.io/badge/license-GPLv2%2B-0bb8d9)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)
![Tools](https://img.shields.io/badge/tools-185-16a34a)
![Price](https://img.shields.io/badge/price-100%25%20free-success)

**Built by [Dice Codes](https://dicecodes.com) — your digital dream team.**

🔗 **[Download on WordPress.org](https://wordpress.org/plugins/dicestack/)** &nbsp;•&nbsp; 🌐 **[Plugin Home](https://dicecodes.com/dicestack-wordpress-plugin/)** &nbsp;•&nbsp; 📖 **[Documentation](https://dicecodes.com/dicestack/docs/)**

---

## Why DiceStack?

Most WordPress sites run a dozen plugins — one for caching, one for security, one for SEO, another for backups — each adding weight, cost, and conflicts. **DiceStack replaces that whole stack** with one free, modular toolkit:

- **🧩 185 tools, one install** — security, performance, SEO, WooCommerce, media, marketing, admin, accessibility and more.
- **⚡ Zero bloat by design** — every tool is off until you enable it, and disabled tools load **no PHP, JS, or CSS**. The dashboard shows the exact RAM / JS / DB cost of each tool *before* you turn it on.
- **💯 100% free, forever** — no "Pro" upsell, no locked features, no nag screens.
- **🛡️ Safe Mode & isolation** — a failing tool auto-disables itself instead of taking down your site.
- **🤝 Conflict-aware** — DiceStack detects plugins you already run (e.g. an SEO or caching plugin) and warns you before enabling an overlapping tool.
- **🏢 Agency Mode** — per-tool control and a 1-click "Recommended setup" that scans your site and enables the right essentials.

---

## Features — 185 tools across 10 categories

| Category | Tools | Examples |
|---|---|---|
| 🛡️ **Security** | 21 | Login URL change, 2FA, firewall rules, XML-RPC control, Cloudflare control, file-change monitor |
| ⚡ **Performance** | 24 | Page cache, object cache (Redis/Memcached drop-in), CSS/JS minify, lazy-load, heartbeat control, GZIP |
| 🔍 **SEO** | 20 | Meta titles & descriptions, schema markup, XML sitemap, robots.txt editor, Open Graph, redirects |
| 📝 **Forms** | 8 | Contact forms, spam protection, submission logging |
| 🛒 **WooCommerce** | 22 | Catalog mode, custom order statuses, checkout field editor, free-shipping nudge, min/max order, extra fees |
| 🖼️ **Media** | 10 | Image optimization, WebP, SVG support, lazy media, bulk compression |
| 📣 **Content & Marketing** | 30 | Reviews, shortcodes, custom code, GA4, popups, related posts |
| 🧰 **Admin & Developer** | 30 | File manager, .htaccess editor, admin menu editor, DB search-replace, config export/import, WP-CLI |
| 🗄️ **Site Management** | 13 | Backups & restore, cloud backup, monthly reports, error monitor, activity log, diagnostics |
| ♿ **Accessibility & Legal** | 7 | Cookie consent, accessibility toolbar, privacy tools |

> 👉 Full tool-by-tool guides: **[dicecodes.com/dicestack/docs](https://dicecodes.com/dicestack/docs/)**

---

## Screenshots

| | |
|---|---|
| ![DiceStack dashboard — 185 tools with live RAM, JS and DB cost shown per tool](.wordpress-org/screenshot-1.png) | ![Category view with one-click enable toggles and conflict warnings](.wordpress-org/screenshot-2.png) |
| **The DiceStack dashboard** — every tool shows its exact RAM / JS / DB footprint *before* you enable it. | **Category view** — enable only what you need; overlapping plugins are flagged before you turn a tool on. |
| ![Per-tool settings open in a modal — no page reloads](.wordpress-org/screenshot-3.png) | ![Recommended setup scans your site and enables the right essentials](.wordpress-org/screenshot-4.png) |
| **In-place settings** — configure each tool in a modal, no hunting through menus. | **Recommended setup & Agency Mode** — scan the site and switch on the essentials in one click. |

---

## Installation

### From WordPress.org (recommended)
1. In wp-admin go to **Plugins → Add New**.
2. Search for **DiceStack**.
3. Click **Install Now**, then **Activate**.
4. Open the **DiceStack** dashboard and enable the tools you want.

### Manual install
1. Download the latest `dicestack.zip` from the [Releases](https://github.com/IamRamgarhia/dicestack-/releases) page or from [WordPress.org](https://wordpress.org/plugins/dicestack/).
2. In wp-admin go to **Plugins → Add New → Upload Plugin** and choose the zip.
3. **Activate**, then open the **DiceStack** dashboard.

---

## How it works

DiceStack uses a **modular architecture**: each tool is a self-contained module that is only loaded when you enable it.

- Disabled module → **0 KB** PHP/JS/CSS, **0** extra DB queries.
- Each card in the dashboard shows the tool's footprint *(e.g. "If on: +12 KB")* so you make informed choices.
- Your enabled/disabled choices **persist across updates** — updating the plugin never re-enables tools or loses settings.

---

## Requirements

- **WordPress** 6.0 or higher
- **PHP** 7.4 or higher
- Some tools need server capabilities (e.g. Redis/Memcached for object cache, Imagick for image optimization, ZipArchive for backups) — DiceStack detects these and clearly marks any tool your server can't run.

---

## FAQ

**Is DiceStack really free?**
Yes — every one of the 185 tools is free. There is no premium tier or paid add-on.

**Will it slow my site down?**
No. Tools you don't enable load nothing at all. Enabling the caching and optimization tools typically makes sites **faster**.

**Will it conflict with my existing SEO / caching / security plugin?**
DiceStack detects common plugins and warns you before you enable a tool that overlaps, so you can avoid double-handling.

**Do I need WooCommerce?**
Only for the WooCommerce category. Those tools stay dormant (and clearly marked "Needs WooCommerce") until WooCommerce is active.

**What happens to my settings when I update?**
Nothing changes — enabled tools stay enabled, disabled stay disabled, and all settings are preserved.

---

## Contributing

Issues and pull requests are welcome. Please open an [issue](https://github.com/IamRamgarhia/dicestack-/issues) for bugs or feature requests. The canonical release channel for end users is **[WordPress.org](https://wordpress.org/plugins/dicestack/)**.

## License

DiceStack is free software, released under the **[GNU General Public License v2.0 or later](LICENSE)** — the same license as WordPress itself.

## Credits

Designed and built by **[Dice Codes](https://dicecodes.com)**.
📧 Contact@dicecodes.com
