=== Right Click Blocker PRO – Right Click & Content Protection ===
Contributors: infinitycoder
Tags: right click, content protection, copy protection, anti spam, image protection
Requires at least: 4.9
Tested up to: 7.1
Requires PHP: 7.0
Stable tag: 2.26.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disable right click, stop content theft, block copy-paste and spam comments — with live preview, real-time stats, image watermark. Everything free.

== Description ==

**Stop content theft in 30 seconds.** Right Click Blocker PRO disables right click, copy-paste, text selection, drag & drop, keyboard shortcuts (F12, Ctrl+U, Ctrl+S, Ctrl+Shift+I), printing, screenshots and dev tools — **plus a 5-layer comment spam blocker** — all in one lightweight plugin.

Your texts, photos, prices and product sheets are your work. One setup neutralizes **11 content protections + 5 antispam layers**, with a **live preview** so you see exactly what your visitors see. Real-time statistics show you who's trying to steal your content, when and how.

Unlike plugins that lock features behind a paid version, **everything is free**: dev tools detection, print blocking, image watermark, antispam, statistics, detailed logs, 10 message styles. No external dependency, no data collection, no slowdown.

**Key features (100% free — no Pro version needed)**

* 🖱️ **Right click disabled** — context menu blocked site-wide with custom warning message
* 📋 **Copy-paste blocked** — Ctrl+C, Ctrl+X, Ctrl+A neutralized outside form fields (WooCommerce checkout safe)
* ⌨️ **Keyboard shortcuts blocked** — F12, Ctrl+U, Ctrl+S, Ctrl+P, Ctrl+Shift+I/J/C/K
* 🛠️ **DevTools detection** — real-time detection with content blur or auto-redirect
* 🖨️ **Print blocking** — Ctrl+P shows a warning instead of your content
* 📸 **Screenshot protection** — PrintScreen key detected, clipboard cleared
* 💧 **Image watermark** — copyright text overlaid on every image (exempt with data-rcb-exempt)
* 🖼️ **Anti-clickjacking** — X-Frame-Options header + optional JS frame busting
* 🚫 **Comment spam blocker** — 5 layers: honeypot trap, time-gate, per-IP rate limit, keyword blacklist, link-count limit
* 👀 **Live preview** — customize your message and see it in real time (10 styles, custom colors, 8 quick palettes)
* 📊 **Real-time dashboard** — animated KPIs, activity charts, per-type breakdown, top IPs, CSV export
* 📜 **Detailed logs** — every attempt logged with date, type, IP and browser; filterable and exportable
* 🚨 **Attack alerts** — email notification when one IP exceeds your threshold (e.g. 50 attempts/hour)
* 🎨 **10 message styles** — Blue, Dark, Purple, Green, Gold, Frosted Glass, Neon, Gradient, Minimal White, Red Alert
* 🧩 **Presets & exclusions** — Soft / Balanced / Maximum one-click presets; exclude pages, admins, roles
* 💾 **Settings import/export** — full JSON config, replicate on another site in two clicks
* 🧱 **Gutenberg block** — insert the interactive protection demo from the editor
* 🇫🇷 **French-first** — interface en français, parfait pour les sites francophones (Algérie, Maroc, Tunisie, France, Belgique, Suisse, Canada)

**Why choose Right Click Blocker PRO over the competition?**

| Feature | Right Click Blocker PRO | WP Content Copy Protection | Right Click Disable Or Ban |
|---|---|---|---|
| Right click + copy blocked | ✅ Free | ✅ Free | ✅ Free |
| DevTools detection | ✅ Free | ❌ Pro only | ❌ Pro only |
| Print blocking | ✅ Free | ❌ Pro only | ❌ |
| Screenshot protection | ✅ Free | ❌ | ❌ |
| Image watermark | ✅ Free | ❌ | ❌ |
| Comment spam blocker | ✅ Free (5 layers) | ❌ | ❌ |
| Live message preview | ✅ Free | ❌ | ❌ |
| Real-time statistics | ✅ Free | ❌ Pro only | ❌ |
| Attack alerts by email | ✅ Free | ❌ | ❌ |
| Settings import/export | ✅ Free | ❌ | ❌ |
| French interface | ✅ | ❌ | ❌ |
| Weight on your site | ~19 KB | Heavy | Medium |
| jQuery dependency | None | Required | Required |
| External requests | Zero | Unknown | Unknown |

**Who is it for?**

* 📷 **Photographers & designers** — protect your portfolios from image theft
* ✍️ **Bloggers & writers** — keep your articles unique, stop plagiarism
* 🎓 **Course creators** — secure your premium training content
* 🛒 **E-commerce owners** — protect product sheets, prices and descriptions (WooCommerce compatible)
* 🏢 **Agencies & businesses** — deploy consistent protection across all client sites (JSON import/export)
* 🌍 **Francophone sites** — interface entièrement en français (Algérie, Afrique du Nord, Europe, Canada)

**How does it work?**

Each protection uses standard browser events (contextmenu, keydown, copy, selectstart, dragstart, beforeprint…), intercepted in the **capture phase** to run before theme and page builder scripts. This ensures blocking works with any theme: Gutenberg, FSE, classic, Elementor, Divi, WPBakery.

No content is sent to third-party servers. Statistics and logs stay in your WordPress database and wp-uploads folder. The plugin never slows your site: ~19 KB total, no jQuery, scripts don't load on excluded pages, and processing pauses when the tab is in background.

**Performance & privacy**

* ⚡ **Ultra light**: ~19 KB total assets (1 CSS ~7 KB + 1 JS ~12 KB), zero dependencies
* 🔒 **Zero data collection**: no cookies, no external calls, GDPR-friendly
* 📱 **Fully responsive**: admin and messages adapt to mobile, tablet and desktop
* ♿ **Accessible**: respects prefers-reduced-motion, forms and checkout always work

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

= How do I disable right click on my WordPress site? =

Install Right Click Blocker PRO and activate it — protection starts immediately with recommended settings. Go to Settings to toggle individual protections, or use the Soft / Balanced / Maximum presets. The right-click context menu is replaced by your custom warning message.

= How do I stop people from copying my content? =

Right Click Blocker PRO blocks copy-paste (Ctrl+C, Ctrl+X, Ctrl+A), text selection, and drag-and-drop in one click. Form fields remain usable (login, checkout, comments) so your site stays functional. A custom warning message appears each time someone tries to copy.

= How do I protect my images from being stolen? =

Three layers: (1) right-click Save-image-as is blocked, (2) drag-and-drop of images is disabled, and (3) an optional image watermark overlays your copyright text on every image. Exempt specific images with the data-rcb-exempt attribute.

= How do I block spam comments without a paid service? =

The built-in 5-layer antispam blocks bots automatically: honeypot trap field, time-gate, per-IP rate limit, keyword blacklist, and link-count limit. No external API, no captcha, no subscription. Blocked comments go to the spam folder, never deleted.

= How do I stop dev tools inspection (F12)? =

Enable DevTools detection — when someone opens developer tools, your content blurs automatically or the visitor is redirected to a page of your choice. Detection pauses when the tab is not visible.

= How do I disable printing of my pages? =

Print blocking replaces the printed page with a copyright warning instead of your content. Ctrl+P is intercepted and the beforeprint event is captured.

= Does the plugin slow down my site? =

No: about 19 KB total (1 CSS + 1 JS), zero jQuery, zero dependencies. Scripts do not load on excluded pages. Statistics are batched (one request max every 6 seconds). No measurable impact on PageSpeed.

= Does it hurt SEO? =

No. Your content stays identical for search engines — only visitor interactions are intercepted. Google can still crawl, index and rank your pages normally.

= Can administrators be excluded from protection? =

Yes. A dedicated toggle disables protection for logged-in admins. You can also exclude other roles and specific pages by ID, slug or URI.

= Does it work with Elementor, Divi, WPBakery, WooCommerce? =

Yes. Protections run in the capture phase (before theme and page builder scripts). WooCommerce checkout, login forms and comment fields always remain functional.

= Is it available in French? =

Oui, l'interface est entierement en francaise — parfaite pour les sites algeriens, marocains, tunisiens, francais, belges, suisses et canadiens.

= Is there a Pro version or paid upgrade? =

No. Everything is 100% free with no locked features. An optional symbolic lifetime license (from 2900 DZD) exists only to support development — it adds or removes nothing.

= Can I see who is trying to steal my content? =

Yes. The real-time dashboard shows every blocked attempt: date, type, IP address and browser. Export to CSV. Set up email alerts when one IP exceeds your threshold.

= Can I copy my configuration to another site? =

Yes: Settings, Export (JSON), then Import on the target site. Perfect for agencies managing multiple sites.

== Screenshots ==

1. Real-time dashboard: KPIs, activity chart and protection status
2. License & Purchase: plans, order form with automatic email to the developer
3. Settings: 10 customizable message styles with live preview beside the form
4. Warning message shown to visitors on right click (automatic copyright line)
5. Public shop: plans, WhatsApp ordering and CIB / Edahabia card payment
6. Professional structured HTML emails (order confirmation, license key)

== Changelog ==

= 2.26.0 =
* New: detailed purchase modal on the About page. The "Buy a license" button now opens a popup with the three license plans (amount updates live), and one card per payment method — BaridiMob (RIP with one-click copy), CCP (account + copy), Edahabia/CIB card via Chargily, and PayPal (direct link when configured). Each card links to the guided order flow with the plan preselected and the form scrolled into view.

= 2.25.1 =
* Fix: About page layout — four changelog entries (2.17.0, 2.15.0, 2.14.5, 2.14.4) had mismatched markup that closed the content wrapper early, pushing the FAQ section outside the centered layout and causing a 164px horizontal overflow. Entries rebuilt, FAQ realigned, overflow gone.

= 2.25.0 =
* New: custom logo for the protection message. Settings → Appearance now includes a "Custom logo" picker (WordPress media library) that replaces the default shield icon with your own PNG/SVG — in the visitor-facing message, the live preview and the dashboard preview card. Falls back to a URL prompt when the media library is unavailable; a "Remove" button restores the shield.

= 2.24.1 =
* Fix: the sticky save bar no longer overlaps the last settings rows when scrolling to the bottom of a tab (proper clearance is now reserved below the floating bar).

= 2.24.0 =
* New: a branded update card now appears on WordPress Updates (wp-admin/update-core.php) when a GitHub-channel update is pending — installed → new version, release date, channel badge, release notes excerpt, one-click "Update now" (auto-checks and submits the form), full details in the modal, and a link to the GitHub release notes. It never shows when WordPress.org manages the plugin.
* New: after a successful update, a "Discover what's new" link points to the plugin's changelog.
* New: the release cache is purged right after any plugin upgrade, so stale "update available" rows disappear immediately.
* Compatibility metadata (requires / requires_php) is now sent with update offers to avoid false compatibility warnings.

= 2.23.1 =
* Fix: the "Text size" appearance setting now really controls the protection message. The message title inherited a hardcoded 15px; it now follows the slider, and the copyright line, shield icon and close button scale proportionally (em-based). Mobile no longer overrides the chosen size (only spacing shrinks). Font-size is applied with high priority to resist aggressive theme CSS.

= 2.23.0 =
* About page polish: compatibility updated (WP 4.9-7.1, PHP 7.0-8.3), FAQ styling harmonized with collapsible sections, presentation text tightened, antispam now counted in the feature total (16 protections).

= 2.22.0 =
* License nag system (wp.org compliant): dismissible "unlicensed" banner on the dashboard (returns after 7 days), discreet footer label, and email attack alerts now require an active license. All features remain fully functional without a license (no gating, no trialware).

= 2.21.0 =
* Watermark polish: adjustable opacity (10-100%) and font size (8-48px) with live sliders — settings appear only when the watermark is enabled. Full security and performance audit: 0 PHP errors (48 files), 0 JS errors, 0 SQL injection, 0 XSS, 0 jQuery dependency, ~24 KB total public assets.

= 2.20.0 =
* SEO & GEO optimization: keyword-rich readme with high-volume tags, comparison table with competitors, 14 FAQ entries matching real Google search queries, French/Algeria targeting, antispam prominently featured. Forced update to trigger GitHub update channel.

= 2.19.2 =
* Professional layout polish: the admin wrapper is now centered on screen (max-width 1400px, auto margins) for a premium dashboard feel on any display size; responsive padding adjusted across all breakpoints.

= 2.19.1 =
* Quick navigation chips on the Settings page (jump between tabs in one click); antispam blocked-spam counter card added to the Statistics page with a link to the spam queue; CSV export now includes the spam count; sticky save bar with gradient fade.

= 2.19.0 =
* Antispam integration: dashboard shows blocked-spam counter, widget displays count, uninstall cleans up.

= 2.18.1 =
* Fixed: duplicate color picker section removed from the Behavior card (Appearance tab); the four-card layout is now clean with no duplicated fields, and the live preview sidebar is fully restored.

= 2.18.0 =
* Comment spam blocker with 5 protection layers: honeypot trap field, time-gate (minimum seconds before submission), per-IP hourly rate limit, keyword/IP/email blacklist, and link-count limit — suspicious comments are marked as spam (never deleted) and a badge on the Comments menu shows the total blocked.

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
