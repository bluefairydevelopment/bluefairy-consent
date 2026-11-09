/**
 * BlueFairy Consent: Google Consent Mode v2 defaults.
 *
 * Printed inline as the first script in <head>, after window.bfconsentConfig.
 * Every signal starts denied (security_storage granted). If the visitor already
 * chose, for the current policy version, the stored choice is applied at once
 * so tags that load later see it.
 */
( function () {
	'use strict';
	var cfg = window.bfconsentConfig || {};
	window.dataLayer = window.dataLayer || [];
	window.gtag = window.gtag || function () {
		window.dataLayer.push( arguments );
	};
	window.gtag( 'consent', 'default', {
		ad_storage: 'denied',
		ad_user_data: 'denied',
		ad_personalization: 'denied',
		analytics_storage: 'denied',
		functionality_storage: 'denied',
		personalization_storage: 'denied',
		security_storage: 'granted',
		wait_for_update: 500
	} );
	window.gtag( 'set', 'ads_data_redaction', true );

	var match = document.cookie.match( new RegExp( '(?:^|; )' + ( cfg.cookie || 'bfc_consent' ) + '=([^;]*)' ) );
	if ( ! match ) {
		return;
	}
	var stored;
	try {
		stored = JSON.parse( decodeURIComponent( match[ 1 ] ) );
	} catch ( e ) {
		return;
	}
	if ( ! stored || stored.v !== cfg.version || ! stored.c ) {
		return;
	}
	var update = {};
	var cats = cfg.categories || {};
	Object.keys( cats ).forEach( function ( slug ) {
		var granted = cats[ slug ].required || stored.c[ slug ] === true;
		( cats[ slug ].signals || [] ).forEach( function ( signal ) {
			if ( granted ) {
				update[ signal ] = 'granted';
			} else if ( ! update[ signal ] ) {
				update[ signal ] = 'denied';
			}
		} );
	} );
	window.gtag( 'consent', 'update', update );
}() );
