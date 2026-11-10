# BlueFairy Consent - features

| Feature | Where |
| :--- | :--- |
| Banner, Accept and Reject with identical styling | `includes/Frontend.php`, `assets/css/consent.css` |
| Choice cookie `bfc_consent` (`{v, c, id, t, s}`), policy version re-prompt | `assets/js/consent.js` |
| Consent Mode v2 default denied, stored choice applied in the first head script | `assets/js/consent-mode.js` |
| GPC treated as Reject All | `assets/js/consent.js` |
| GA4 / GTM requested only after consent | `assets/js/consent.js` |
| Analytics cookies deleted on withdrawal | `assets/js/consent.js` |
| Reopen: floating button, `[bluefairy_consent_settings]`, `#bfc-settings` | `includes/Frontend.php`, `assets/js/consent.js` |
| Held scripts (`type="text/plain" data-bfc-category`) | `assets/js/consent.js` |
| Cookie list (setting `cookie_list`, banner `<details class="bfc-cookies">`, `[bluefairy_consent_cookie_list]`) | `includes/Settings.php`, `includes/Frontend.php` |
| WooCommerce order attribution off-switch (setting `disable_wc_order_attribution`, filter `pre_option_woocommerce_feature_order_attribution_enabled`) | `includes/OrderAttribution.php` |
| Translations: `languages/` (`.pot`, `de_DE`, `fr_FR`, `es_ES`), texts equal to the default not stored (`Settings::TRANSLATED`) | `includes/Plugin.php`, `includes/Settings.php`, `languages/` |
| Add-on browser API: `BFConsent.applyDefault()`, events `bfconsent:default`, `bfconsent:first-visit` (cancelable) | `assets/js/consent.js` |
| Settings > Consent | `includes/Admin.php`, `includes/Settings.php` |
| Hooks for add-ons: `bfconsent_loaded`, `bfconsent_categories`, `bfconsent_config`, `bfconsent_banner_body`, `bfconsent_banner_actions`, `bfconsent_settings_rows`; JS `window.BFConsent`, events `bfconsent:change`, `bfconsent:open`, `bfconsent:default`, `bfconsent:first-visit` | |
