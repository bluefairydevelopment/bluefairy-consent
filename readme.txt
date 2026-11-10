=== BlueFairy Consent ===
Contributors: bluefairydevelopment
Tags: cookie consent, gdpr, consent mode, global privacy control, cookies
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A cookie consent banner with Accept and Reject given equal weight, Google Consent Mode v2, Global Privacy Control, and analytics loaded only after consent. Works behind a page cache. No CDN, no account.

== Description ==

BlueFairy Consent shows a small banner the first time someone visits. **Accept All** and **Reject All** look the same and sit side by side, so neither is pushed.

* **The choice is remembered** in a first-party cookie, `bfc_consent`, for 182 days (six months) by default (1 to 395). Raise the policy version in the settings to ask everyone again.
* **Reopen at any time** from the small floating button, the `[bluefairy_consent_settings]` shortcode, or any link to `#bfc-settings`.
* **Google Consent Mode v2.** Every signal starts `denied` (only `security_storage` is granted) before any tag runs, and is updated the moment the visitor chooses. A returning visitor's stored choice is applied in the same first script.
* **Global Privacy Control.** A browser that sends GPC is treated as Reject All, without showing the banner, including for a visitor who had accepted before turning GPC on. The visitor can still opt in from Cookie settings while GPC is on, and that explicit choice is kept. You can switch this off.
* **Analytics only after consent.** Enter your Google Analytics 4 ID and/or Tag Manager ID in the settings: neither is requested until the visitor allows Analytics (Tag Manager: any optional category).
* **Withdrawal deletes cookies.** Choosing Reject after Accept deletes the Google Analytics cookies (`_ga*`, `_gid`, `_gat*`) and stops GA4 for the rest of the page.
* **Page-cache safe.** The server sends the same HTML to every visitor; the browser decides what to show and load. Cloudflare and caching plugins can cache the page.
* **No CDN and no account.** Everything is served from your own site.
* **Cookie list.** The banner has a Cookie list that shows every cookie you declare under **Settings → Consent → Cookie list** (name, category, provider, purpose, duration), plus the consent cookie itself. Put the same table on your privacy policy page with `[bluefairy_consent_cookie_list]`. Keep the list complete: the default message tells visitors it lists all cookies.
* **WooCommerce order attribution off-switch.** WooCommerce's Order Attribution sets `sbjs_*` cookies on the first page view, before the visitor has chosen. On a shop, **Settings → Consent → WooCommerce order attribution** turns it off: no attribution script, no `sbjs_*` cookies, and new orders carry no Origin. WooCommerce's own setting is not changed, so unticking the box restores it. Off by default (attribution keeps working until you tick it); the row appears only while WooCommerce is active.
* **Hold your own scripts.** Write `<script type="text/plain" data-bfc-category="analytics">…</script>` (or `data-bfc-src="…"` for an external file) and it runs only once that category is allowed.

The default wording is a template for you to review with your own legal adviser. It is not legal advice. The plugin is provided as is, without warranty; to the extent the law allows, Blue Fairy Development is not liable for any loss arising from its use, including any regulatory finding about your site's consent practices.

**BlueFairy Consent Pro** adds category toggles (Analytics, Functional, Marketing), automatic script blocking, and a consent log with CSV export.

== Installation ==

1. Upload the `bluefairy-consent` folder to `/wp-content/plugins/`, or install the zip from **Plugins → Add New → Upload Plugin**.
2. Activate it.
3. Go to **Settings → Consent**, review the wording, and add your privacy policy URL and (optionally) your GA4 or Tag Manager ID.

== Frequently Asked Questions ==

= Does it work with a page cache? =

Yes. Nothing on the server depends on the visitor's cookie.

= I already add Google Analytics with another plugin. =

Remove it there and enter the ID here, or wrap that plugin's tag in `type="text/plain" data-bfc-category="analytics"`. Otherwise it loads before consent.

= My shop sets sbjs_ cookies before anyone has chosen. =

That is WooCommerce's Order Attribution. Tick **Turn off WooCommerce order attribution** under Settings → Consent, or, if you keep attribution, list the `sbjs_*` cookies in the Cookie list.

== Screenshots ==

1. The banner: Accept All and Reject All side by side, same size and colour (Customize appears with Pro).

== Changelog ==

= 1.2.0 =
* New setting, shown when WooCommerce is active: **Turn off WooCommerce order attribution**. It stops WooCommerce's Sourcebuster script and its `sbjs_*` cookies, which otherwise appear before consent. Off by default. Checkout is unaffected; new orders have no Origin while it is on.

= 1.1.0 =
* Consent is remembered for 182 days (six months) by default; the range stays 1 to 395.
* Cookie list: declare your cookies under Settings → Consent; the banner shows them under Cookie list, and `[bluefairy_consent_cookie_list]` prints the table on any page.
* Default message mentions Google Consent Mode and the cookie list. The Necessary description says these cookies do not require consent under GDPR.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.2.0 =
WooCommerce shops: a new setting turns off WooCommerce order attribution and its sbjs_* cookies. Nothing changes until you tick it.

= 1.1.0 =
New defaults (six-month consent, new wording) apply only to settings you have not saved. Review Settings → Consent after updating and fill in the Cookie list.
