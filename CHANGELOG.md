# Changelog

## 1.2.0 - 2026-11-10

- Setting `disable_wc_order_attribution` (Settings > Consent, shown only while WooCommerce is active): answers WooCommerce's `woocommerce_feature_order_attribution_enabled` feature check with `no` through `pre_option_`, so WooCommerce registers no Sourcebuster script, no `sbjs_*` cookies and no attribution checkout fields. WooCommerce's stored option is never written. Off by default. (WPMAP-143)

## 1.1.0 - 2026-11-10

- Consent remembered 182 days (six months) by default. (WPMAP-328 decision 5)
- Cookie list setting, banner Cookie list and `[bluefairy_consent_cookie_list]` shortcode. (WPMAP-325)
- Default message discloses Google Consent Mode and the cookie list; Necessary description restores "These cookies do not require your consent under GDPR". (WPMAP-328 decisions 2, 7, 9)
- readme: limitation-of-liability line. (WPMAP-328 decision 13)

## 1.0.0 - 2026-11-09

- First release: equal-weight Accept/Reject banner, first-party choice cookie, reopen button/shortcode/#bfc-settings link, Google Consent Mode v2 defaults and updates, Global Privacy Control as Reject, GA4/GTM loaded only after consent, analytics cookies deleted on withdrawal, held `text/plain` scripts released per category. (WPMAP-143)
