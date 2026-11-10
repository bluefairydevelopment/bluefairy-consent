# BlueFairy Consent — Installation and Usage Guide

BlueFairy Consent shows a small cookie banner the first time someone visits your site. **Accept All** and **Reject All** look the same and sit side by side, so neither choice is pushed. Google Analytics 4 and Google Tag Manager are requested only after the visitor allows them, Google Consent Mode v2 is set before any tag runs, and a browser's Global Privacy Control signal is honoured.

The default wording is a template for you to review with your own legal adviser. It is not legal advice. The plugin is provided as is, without warranty; to the extent the law allows, Blue Fairy Development is not liable for any loss arising from its use.

## Requirements

- WordPress 6.6 or later
- PHP 8.1 or later
- No account, no CDN: everything is served from your own site.

## Installation

1. In WordPress go to **Plugins → Add New → Upload Plugin**, choose `bluefairy-consent.zip` and click **Install Now**.
2. Click **Activate**.
3. Go to **Settings → Consent**, review the wording, add your privacy policy URL and, if you use them, your GA4 ID and/or Tag Manager ID.

## The banner

The banner appears on the first visit and stays until the visitor chooses. Its title, message, button labels and accent colour are all editable, and it can sit bottom-left, bottom-right or across the bottom.

- **The choice is remembered** in a first-party cookie, `bfc_consent`, for 182 days (six months) by default (you can set 1 to 395).
- **Raise the policy version** in the settings to ask every visitor again, for example after you change your privacy policy.
- **Reopen at any time** from the small floating button, the `[bluefairy_consent_settings]` shortcode, or any link to `#bfc-settings` (for example a "Cookie settings" link in your footer).

## Cookie list

The default message tells visitors that a list of all cookies is available in Cookie settings, so keep that list complete. Under **Settings → Consent → Cookie list** enter one cookie per line:

```
name | category | provider | purpose | duration
_ga|analytics|Google Analytics|Distinguishes visitors|2 years
```

The banner shows the list under **Cookie list**, with the consent cookie `bfc_consent` added automatically at the lifetime you chose. Lines whose category does not exist on your site are not shown. Put the same table on your privacy policy page with the shortcode `[bluefairy_consent_cookie_list]`.

## Google Analytics, Tag Manager and Consent Mode v2

- Enter your **Google Analytics 4 ID** (`G-…`) and it is loaded only after the visitor allows Analytics.
- Enter your **Tag Manager ID** (`GTM-…`) and it is loaded only after the visitor allows at least one optional category.
- **Consent Mode v2:** every signal starts `denied` (only `security_storage` is granted) before any tag runs, and is updated the moment the visitor chooses. A returning visitor's stored choice is applied in the same first script.
- **Withdrawal deletes cookies:** choosing Reject after Accept deletes the Google Analytics cookies (`_ga*`, `_gid`, `_gat*`) and stops GA4 for the rest of the page.

If another plugin already adds Google Analytics, remove it there and enter the ID here, or it will load before consent.

## Global Privacy Control

A browser that sends Global Privacy Control is treated as Reject All, without showing the banner, including for a visitor who had accepted before turning GPC on. The visitor can still opt in from Cookie settings while GPC is on, and that explicit choice is kept. You can switch this off under **Settings → Consent**.

## Page caches

The server sends the same HTML to every visitor; the browser decides what to show and what to load. Cloudflare and caching plugins can cache your pages as usual.

## WooCommerce shops: order attribution

WooCommerce's Order Attribution feature loads a script (Sourcebuster) that sets `sbjs_*` cookies on the first page view, before the visitor has chosen anything. To stop it, tick **Turn off WooCommerce order attribution** under **Settings → Consent**. WooCommerce then loads no attribution script and sets no `sbjs_*` cookies; checkout works as before, and new orders show no Origin. It applies to every visitor, including those who accept. This plugin does not write WooCommerce's own Features setting: while the box is ticked, WooCommerce → Settings → Advanced → Features shows Order Attribution as off and changes made there have no effect; unticking the box hands the decision back to that setting.

The row appears only while WooCommerce is active, and is off by default. If you keep attribution on, add the `sbjs_*` cookies to your Cookie list.

## Languages

Everything visitors see (the banner title and message, the buttons, the category names and descriptions, and the cookie table) is translated into **German, French and Spanish** and appears in the language set under **Settings → General → Site Language**. The default texts follow that language until you change them under **Settings → Consent**; a text you have changed is shown exactly as you wrote it. The settings screens are in English.

For another language, translate `languages/bluefairy-consent.pot` (for example with Poedit) and save the result as `wp-content/languages/plugins/bluefairy-consent-<locale>.mo`. A translation placed there takes precedence over the bundled one.

The bundled translations follow the English template wording. Review them with your own legal adviser, as you would the English text.

## Holding your own scripts

Any script can wait for consent. Write it as

```
<script type="text/plain" data-bfc-category="analytics">…</script>
```

or, for an external file, `<script type="text/plain" data-bfc-category="analytics" data-bfc-src="https://…"></script>`. It runs only once that category is allowed.

## Settings reference

| Setting | What it does |
| :--- | :--- |
| Banner title, Message | The banner text. The message may contain links. |
| Accept / Reject button, Settings link text | Button and link labels. |
| Privacy policy URL | Shown as a link in the banner when set. |
| Position | bottom-left, bottom-right or bottom. |
| Google Analytics 4 ID, Tag Manager ID | Loaded only after consent. |
| Remember choice for (days) | 1 to 395; default 182 (six months). |
| Cookie list | Every cookie your site sets, shown in the banner and by `[bluefairy_consent_cookie_list]`. |
| Policy version | Raise it to ask everyone again. |
| Accent colour | Button colour. |
| Floating settings button | Shows the small reopen button. |
| Global Privacy Control | Treat GPC as Reject All (on by default). |
| WooCommerce order attribution | Shown only with WooCommerce. Turns off WooCommerce order attribution and its `sbjs_*` cookies (off by default). |

## Troubleshooting

- **Google Analytics loads before consent:** another plugin or your theme is adding it. Remove it there and use the GA4 ID setting here.
- **The banner does not appear:** you have already chosen on this browser. Use the Cookie settings link, or open the site in a private window.
- **Visitors are not asked again after a policy change:** raise the Policy version.

## Upgrade to BlueFairy Consent Pro

You already have an honest banner. Pro turns it into a full consent manager: visitors choose category by category, third-party scripts you did not write wait for consent automatically, and you keep a record of every choice you can hand over as a CSV.

| Feature | Consent Pro | Consent (free) |
| :--- | :--- | :--- |
| Equal-weight Accept / Reject banner | Yes | Yes |
| Google Consent Mode v2, GA4 and Tag Manager after consent | Yes | Yes |
| Global Privacy Control | Yes | Yes |
| Works behind page caches | Yes | Yes |
| Hold your own scripts with `data-bfc-category` | Yes | Yes |
| Cookie list in the banner and `[bluefairy_consent_cookie_list]` | Yes | Yes |
| Customize: Necessary, Functional, Analytics, Marketing switches | Yes | No |
| Automatic blocking of Google, Meta, Hotjar, Clarity, LinkedIn and DoubleClick scripts, with editable rules | Yes | No |
| Consent log (time, policy version, choices, source, user agent, hashed visitor id; no IP address), purged after 6 months by default | Yes | No |
| CSV export of the log | Yes | No |
| One-click CookieAdmin importer, plus WP-CLI | Yes | No |
| YouTube, Vimeo and Google Maps embeds wait behind a placeholder until consent | Yes | No |
| Region rules: an opt-out notice for the countries you choose (for example the US), opt-in everywhere else | Yes | No |
| Cookie scanner that proposes Cookie list lines for you to review | Yes | No |
| 12 months of updates and support | Yes | — |

Without Pro, a visitor who wants analytics but not advertising has no way to say so, and Meta or Hotjar tags added by other plugins run before anyone has chosen.

**Get BlueFairy Consent Pro for $25:** https://wordpress.bluefairydevelopment.com/product/bluefairy-consent-pro/

## Support

Documentation and the FAQ: https://wordpress.bluefairydevelopment.com/bluefairy-consent/
