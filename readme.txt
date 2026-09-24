=== Right Click Blocker PRO – Right Click & Content Protection ===
Contributors: infinitycoder
Tags: right click, content protection, copy protection, disable right click, image protection
Requires at least: 4.9
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 2.17.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable right click, copy, selection, printing, screenshots and dev tools — with custom messages, real-time stats and logs. All free.

== Description ==

Your texts, photos, prices and product sheets are your work. **Right Click Blocker PRO** protects them in seconds: one setup neutralizes right clicks, keyboard shortcuts (F12, Ctrl+U, Ctrl+S, Ctrl+Shift+I…), copy-paste, text selection, drag & drop, printing and even screenshots — on desktop and mobile.

Unlike plugins that lock advanced features behind a paid version, **Right Click Blocker PRO gives you everything, for free**: dev tools detection, print blocking, real-time statistics, detailed logs, 10 message styles. No external dependency, no data collection, no slowdown.

**Why choose Right Click Blocker PRO?**

* 🆓 **Everything is free** — DevTools, printing, stats, styles: nothing is locked behind a Pro version.
* ⚡ **Ultra light** — one CSS file (~7 Ko) and one JS file (~12 Ko), no jQuery, no framework; attempts are batched and sent in a single request via sendBeacon.
* 🌐 **Compatible everywhere** — WordPress 4.9 → 7.1, PHP 7.0 → 8.3, Gutenberg/FSE themes, classic themes, Elementor, Divi, WPBakery, WooCommerce, multisite. Protections run before theme scripts (capture phase).
* 📊 **You see everything** — every blocked attempt is counted, charted and logged (date, type, IP, browser), exportable as CSV.
* 🔒 **Total privacy** — no data ever leaves your server, no cookie, no external call (the optional Chargily Pay gateway only runs if the vendor configures their own API key).
* 💧 **Image watermark** — overlay your copyright text on every image; exempt specific images with data-rcb-exempt.
* 🖼️ **Anti-clickjacking** — X-Frame-Options SAMEORIGIN header + optional JavaScript frame busting.
* 🎨 **10 message styles** — Blue, Dark, Purple, Green, Gold, Frosted Glass, Neon, Gradient, Minimal White, Red Alert + custom colors, close button, progress bar and sound beep.
* 🧩 **Presets & exclusions** — Soft / Balanced / Maximum in one click; exclude pages (ID, slug, URI), logged-in administrators and selected roles.
* 💾 **Settings import/export** — full configuration as JSON, replicated on another site in two clicks.
* 🧱 **Gutenberg block** — insert the interactive protection demo from the editor.
* 🌐 **Fully responsive** — admin and messages adapt to mobile, tablet and desktop; respects prefers-reduced-motion.

**Free features (often paid elsewhere)**

| Protection | Right Click Blocker PRO (free) |
|---|---|
| Right click, copy, selection blocking | ✅ |
| Dev tools detection | ✅ |
| Print blocking | ✅ |
| Screenshot protection | ✅ |
| Custom message styles | ✅ (10 styles) |
| Real-time stats & logs | ✅ |
| Page / admin / role exclusions | ✅ |
| External dependencies | None |

**Who is it for?**

* 📷 Photographers and designers — protect your portfolios.
* ✍️ Bloggers and writers — keep your articles unique.
* 🎓 Course creators — secure your premium content.
* 🛒 E-commerce owners — protect product sheets and prices.
* 🏢 Agencies and businesses — full shielding in 2 minutes.

**How does it work?**

Each protection relies on standard browser events (contextmenu, keydown, copy, selectstart, dragstart, beforeprint…), intercepted in the **capture phase** to run before theme scripts. No content is sent to a third-party server: statistics and logs stay in your WordPress database and your wp-uploads folder. The plugin never slows your site down: the script does not load at all on excluded pages and pauses when the tab is in the background.

== Installation ==

**Automatic installation**

1. Open Plugins → Add New.
2. Search for “Right Click Blocker PRO”.
3. Click Install, then Activate.
4. Open “Right Click Blocker PRO”: protection is already active with recommended settings.

**Manual installation**

1. Download the .zip archive.
2. Plugins → Add New → Upload Plugin.
3. Select the .zip, click Install, then Activate.

== Frequently Asked Questions ==

= Does the plugin block 100% of content theft? =

No web solution blocks 100% of a determined visitor (disabling JavaScript, system tools). Right Click Blocker PRO neutralizes all common methods and deters the vast majority — while keeping the site perfectly usable.

= Does it hurt SEO? =

No. Content stays identical for search engines; only visitor actions (right click, copy…) are intercepted.

= Does the plugin slow my site down? =

No: ~19 Ko of assets total, no jQuery, loaded only when protection is active and never on excluded pages. Statistics are sent in batches (one request every 6 seconds maximum).

= Can administrators be excluded? =

Yes, a dedicated setting disables protection for logged-in administrator accounts — handy to edit your site without constraints. Other roles (subscribers, authors…) can also be excluded one by one.

= Can I watermark my images? =

Yes: Settings → Advanced → “Image watermark”. Your text ({site}, {year} variables) is overlaid semi-transparently on every image larger than 80px. Exempt a specific image by adding the data-rcb-exempt attribute.

= Can I be alerted on attacks? =

Yes: set a threshold (e.g. 50 attempts/hour from one IP) in Settings → Advanced; beyond it, an alert email is sent automatically (1 max per IP per hour).

= Can I copy my configuration to another site? =

Yes: Settings → Tools → Export (JSON), then Import on the target site. Ideal for agencies managing several sites.

= Does the plugin add a Gutenberg block? =

Yes: the “Protection demo — Right Click Blocker PRO” block inserts the interactive demo (11 real tests, live log, 10 styles) into any page.

= Do my forms and WooCommerce keep working? =

Yes. Copy-paste and selection stay active inside input fields (input, textarea, select, contenteditable): login, comments and checkout are never blocked.

= Does it work on mobile? =

Yes: touch events and message layouts are designed for iOS and Android, on Chrome/Safari/Edge/Firefox/Opera/Samsung Internet.

= Compatible with Elementor, Divi, WPBakery, Gutenberg? =

Yes: protections run in the capture phase, before page builder scripts, and message styles are hardened against aggressive stylesheets.

= Can I exclude specific pages? =

Yes, by ID, slug or URI (Settings → Advanced). On those pages the script does not load at all.

= Where are statistics stored? =

In your WordPress database and your wp-uploads folder — nothing is sent outside, no cookie is set (GDPR-friendly).

= How do I receive updates? =

Two channels, without conflict: as soon as the plugin is published on WordPress.org (or installed from the directory), updates arrive automatically from there. In the meantime — or for a manual installation — a GitHub repository can deliver updates (releases with a .zip file); that channel disables itself as soon as WordPress.org takes over. “Check now” button in Settings → General.

= Is there a Pro version? =

All features are free and complete. A symbolic lifetime license (2,900 DZD ≈ 11.90 € single site, 4,800 DZD ≈ 22.90 € 5 sites) exists only to support development and get priority support — it adds or removes no functionality.

== Screenshots ==

1. Real-time dashboard: KPIs, activity chart and protection status
2. License & Purchase: plans, order form with automatic email to the developer
3. Settings: 10 customizable message styles with live preview beside the form
4. Warning message shown to visitors on right click (automatic copyright line)
5. Public shop: plans, WhatsApp ordering and CIB / Edahabia card payment
6. Professional structured HTML emails (order confirmation, license key)

== Changelog ==

= 2.17.2 =
* Appearance tab redesigned into four focused cards (Style & Colors, Behavior & Position, Enriched Message, Finishing); color pickers now show live hex codes and offer 8 one-click preset palettes that instantly apply to the live preview.

= 2.17.1 =
* About page reorganized: quick anchor navigation, collapsible brand identity / roadmap / changelog sections, anchored section IDs and consistent card rhythm — the page reads top-down without endless scrolling.

= 2.17.0 =
* Guided order experience for downloading customers: automatic popup right after ordering (reference, amount, the three next steps, copy-reference button) and after declaring payment ("keep this page open"); real-time order watch that polls every 25 seconds and pops a celebration dialog the moment the vendor delivers — with the key, a copy button and a one-click "Activate now" that pre-fills the license form.

= 2.16.5 =
* The public shop now reports email delivery after ordering: if the host blocks wp_mail, the customer sees a clear warning with direct fallback links (vendor email with the order details pre-filled, WhatsApp) — the order itself is always registered and trackable.

= 2.16.4 =
* Promo codes entered on sites that don't know them are now stored with the order and flagged "to verify" in the vendor emails (instead of being silently dropped); the dashboard widget protection counter now derives from the registered types instead of a hardcoded value.

= 2.16.3 =
* Full-options logic audit: saving the settings no longer wipes the GitHub update channel on client sites (fields preserved unless the vendor form posts them) and no longer resurrects the first-run wizard; a failed key generation (OpenSSL unavailable) keeps the order pending instead of marking it paid with an empty key; the update check returns the neutral notice when the GitHub channel is deliberately disabled.

= 2.16.2 =
* GitHub update details window redesigned: real banner cover, full icon set (SVG/1x/2x), a screenshots gallery of the actual plugin, installation steps and release notes; compatibility warning silenced on the GitHub channel (tested matches the running WordPress). The WordPress.org directory keeps answering with its own data when it manages the install.

= 2.16.1 =
* Plugin Check pass: escaping annotations for binary downloads (CSV/JSON), static brand SVG/wordmark helpers and pre-escaped dashboard widgets; one leftover date() call replaced with date_i18n(); the integrity manifest is renamed rcb-manifest.json (dot-files are not permitted in directory builds). Update system untouched: the optional GitHub updater file stays excluded from WordPress.org builds only.

= 2.16.0 =
* Tamper protection: every official release ships a SHA-256 manifest of all plugin files; the About page verifies each file against it and warns when a redistributed copy or zip has been modified (with the list of changed files and a link to the official sources). Releases also publish SHA256SUMS.txt so anyone can verify a downloaded zip before installing.

= 2.15.2 =
* Hardening: the tracking batch (sendBeacon payload) is normalized immediately after JSON decoding — type whitelist and capped counts before any use in stats, logs or alert emails.

= 2.15.1 =
* Official GitHub repository now configured by default (derouicheoussama/right-click-blocker-pro) for the secondary update channel — existing installs are migrated automatically; WordPress.org remains the primary source.

= 2.15.0 =
* Redesigned order confirmation popup (License page and public shop): brand header, key facts recap, highlighted amount box and the three next steps (pay, "I have paid", key by email); the confirm button focuses on open, Escape/backdrop close returns to the form and the submit button shows a sending state.
* Order button wording fixed to match the real flow ("payment comes first, request goes to the vendor on I-have-paid") and now displays the selected plan and price dynamically.

= 2.14.5 =
* Clearer update check feedback: when no GitHub repository is configured, the check now returns a neutral confirmation that WordPress.org handles updates automatically; the warning is only shown when a configured repository cannot be reached.

= 2.14.4 =
* Plugin Check compliance pass: the optional GitHub updater file is now excluded from the WordPress.org build (loaded only when present), uninstall cleanup moved to WP_Filesystem and extended to every plugin option, file operations switched to wp_delete_file/file_get_contents, date() replaced with date_i18n(), all CSV/JSON/SVG outputs properly escaped via printf, Tested up to raised to 7.1, and the readme is now written in standard English (5 tags, short description under 150 characters).

= 2.14.3 =
* Real-behavior message preview: appear, stay for the configured duration, auto-hide — close button actually closes, sound beep plays when enabled, and every setting instantly replays the sequence; a “Replay” button appears in the frame when the message is hidden.

= 2.14.2 =
* Enriched plugin row on the Plugins screen: direct actions (Dashboard, Settings, License, Delete with double confirmation through the native WordPress flow) and status notes under the description — active protection X/11 and license pills, update channel, documentation link.

= 2.14.1 =
* Message preview (Appearance tab) significantly widened: adaptive column up to 600px, reduced inner margins and a roomier browser frame.

= 2.14.0 =
* Dual update channel: WordPress.org (automatic, priority as soon as published) and GitHub Releases for manual installs — the GitHub channel disables itself as soon as WordPress.org manages the plugin (directory-compliant).
* “Updates” card in Settings → General: installed version, active channel, latest GitHub release, “Check now” button and configurable GitHub repository.

= 2.13.2 =
* Appearance tab split in two columns: settings form on the left, sticky preview on the right — every change reflected in real time without scrolling. On mobile the preview moves above the form.

= 2.13.1 =
* The message preview card now sits at the top of Settings → Appearance and follows the form live: every style, color, duration, radius, copyright or position change is instantly reflected (and the chips update the form). Also available on the dashboard.

= 2.13.0 =
* Smoother License & Purchase page: fixed plan selection highlight, one-click whole-key selection, cleanly truncated keys in the table, “Open” button to reopen a order timeline and one-click key resend to the buyer.
* Quick anchor navigation at the top of the page and precise confirmations after every action.

= 2.12.2 =
* “Message preview” card on the dashboard: exact rendering of the visitor warning (style, colors, copyright, progress bar) with live testing of the 10 styles, 3 positions and 11 messages — without changing settings.

= 2.12.1 =
* Renamed to “Right Click Blocker PRO – Right Click & Content Protection”, with a blue gradient PRO badge in the plugin row title (features, Infinity RCB Pro branding and settings unchanged).

= 2.12.0 =
* Reworked order flow: new “I have paid” step (button on the public shop and the License page, or late declaration by reference + email) — the vendor receives the actionable license request only after the customer declares payment (creation email becomes informational).
* Visual tracking: Commanded → Paid → Verified → Key received timeline, “declared” status and a dedicated vendor KPI.
* Vendor automation: payment acknowledgement to the customer, daily reminder for paid-but-undelivered orders, and unchanged automatic delivery (“Validate + key” button, Chargily CIB/Edahabia card, J+2/J+5 follow-ups).

= 2.11.0 =
* Sales: “Order via WhatsApp” button on the public shop, advanced promo codes (expiry + usage quota), automatic unpaid-order follow-ups (D+2 and D+5) and weekly vendor digest.
* Chargily Pay card payment (CIB / Edahabia, vendor site): fully automatic key delivery through a signed webhook.
* Protection: image watermark, anti-clickjacking (X-Frame-Options + frame busting), attack-peak email alert, role-based exclusions.
* Experience: WordPress dashboard widget, JSON settings import/export, “Protection demo” Gutenberg block.

= 2.10.1 =
* Complete email rework: branded structured HTML templates, plain-text alternative (multipart/alternative) and optimized headers for better inbox delivery.
* Default vendor contact address migration.

= 2.10.0 =
* First-run onboarding wizard, mobile touch protection (iOS/Android long-press), image hardening, noscript banner, admin bar quick link.
