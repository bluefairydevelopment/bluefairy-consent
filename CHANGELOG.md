# Changelog

## 1.3.0 - 2026-11-10

- Translations (WPMAP-324): `Domain Path: /languages`, `load_plugin_textdomain()` on `init`, `languages/bluefairy-consent.pot`, and `de_DE`, `fr_FR`, `es_ES` (`.po`, `.mo`, `.l10n.php`) covering the visitor-facing strings; admin screens stay English in this release. Banner texts equal to their default (title, message, Accept, Reject, Cookie settings) are no longer stored on save, so they keep following the site language; customised texts are stored as before.
- Browser API for add-ons (WPMAP-323, used by Pro 1.3.0 region rules and cookie scanner): `BFConsent.applyDefault(choices, force)` applies choices for the page view without storing them (`granted()` reads them; a stored choice clears them; ignored while a stored choice exists unless `force`), event `bfconsent:default`, and a cancelable `bfconsent:first-visit` fired when there is no stored choice and no GPC signal.

## 1.2.0 - 2026-11-10

- Setting `disable_wc_order_attribution` (Settings > Consent, shown only while WooCommerce is active): answers WooCommerce's `woocommerce_feature_order_attribution_enabled` feature check with `no` through `pre_option_`, so WooCommerce registers no Sourcebuster script, no `sbjs_*` cookies and no attribution checkout fields. WooCommerce's stored option is never written. Off by default. (WPMAP-143)

## 1.1.0 - 2026-11-10

- Consent remembered 182 days (six months) by default. (WPMAP-328 decision 5)
- Cookie list setting, banner Cookie list and `[bluefairy_consent_cookie_list]` shortcode. (WPMAP-325)
- Default message discloses Google Consent Mode and the cookie list; Necessary description restores "These cookies do not require your consent under GDPR". (WPMAP-328 decisions 2, 7, 9)
- readme: limitation-of-liability line. (WPMAP-328 decision 13)

## 1.0.0 - 2026-11-09

- First release: equal-weight Accept/Reject banner, first-party choice cookie, reopen button/shortcode/#bfc-settings link, Google Consent Mode v2 defaults and updates, Global Privacy Control as Reject, GA4/GTM loaded only after consent, analytics cookies deleted on withdrawal, held `text/plain` scripts released per category. (WPMAP-143)
