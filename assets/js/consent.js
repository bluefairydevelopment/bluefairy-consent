/**
 * BlueFairy Consent: banner, stored choice, tag loading, withdrawal.
 *
 * The page HTML is identical for every visitor (page-cache safe); this script
 * decides what to show and what to load.
 *
 * Public API (window.BFConsent), used by add-ons:
 *   get()                -> stored record {v, c, id, t, s} or null
 *   granted(slug)        -> bool
 *   set(choices, source) -> save a choice ({slug: bool}) and apply it
 *   applyDefault(choices, force) -> apply choices for this page view only,
 *                          without storing them (add-ons: region rules,
 *                          cookie scanner). Ignored while a stored choice
 *                          exists unless force is true. Returns bool.
 *   acceptAll(source) / rejectAll(source)
 *   open() / close()
 * Event: document 'bfconsent:change' with detail {choices, previous, source, id, version}.
 * Event: document 'bfconsent:default' with detail {choices} after applyDefault().
 * Event: document 'bfconsent:first-visit' (cancelable) when there is no stored
 *   choice and no GPC signal, just before the banner opens. An add-on that
 *   calls preventDefault() takes over: it must call open() or applyDefault().
 */
( function () {
	'use strict';

	var cfg = window.bfconsentConfig || {};
	var cats = cfg.categories || {};
	var COOKIE = cfg.cookie || 'bfc_consent';
	var loaded = {};
	// Choices applied for this page view without being stored (applyDefault).
	var defaults = null;

	function slugs() {
		return Object.keys( cats );
	}

	function readCookie() {
		var m = document.cookie.match( new RegExp( '(?:^|; )' + COOKIE + '=([^;]*)' ) );
		if ( ! m ) {
			return null;
		}
		try {
			var rec = JSON.parse( decodeURIComponent( m[ 1 ] ) );
			return rec && rec.c && typeof rec.c === 'object' ? rec : null;
		} catch ( e ) {
			return null;
		}
	}

	function current() {
		var rec = readCookie();
		return rec && rec.v === cfg.version ? rec : null;
	}

	function randomId() {
		if ( window.crypto && window.crypto.randomUUID ) {
			return window.crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c ) {
			var r = Math.random() * 16 | 0;
			return ( c === 'x' ? r : ( r & 0x3 | 0x8 ) ).toString( 16 );
		} );
	}

	function writeCookie( rec ) {
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = COOKIE + '=' + encodeURIComponent( JSON.stringify( rec ) ) +
			'; Max-Age=' + ( ( cfg.days || 365 ) * 86400 ) + '; Path=/; SameSite=Lax' + secure;
	}

	function normalise( choices ) {
		var out = {};
		slugs().forEach( function ( slug ) {
			out[ slug ] = !! cats[ slug ].required || choices[ slug ] === true;
		} );
		return out;
	}

	function signals( choices ) {
		var update = {};
		slugs().forEach( function ( slug ) {
			( cats[ slug ].signals || [] ).forEach( function ( s ) {
				if ( choices[ slug ] ) {
					update[ s ] = 'granted';
				} else if ( ! update[ s ] ) {
					update[ s ] = 'denied';
				}
			} );
		} );
		return update;
	}

	function domains() {
		var parts = location.hostname.split( '.' );
		var out = [ '' ];
		for ( var i = 0; i < parts.length - 1; i++ ) {
			out.push( '; domain=.' + parts.slice( i ).join( '.' ) );
		}
		return out;
	}

	function deleteCookies( slug ) {
		var patterns = ( cats[ slug ].cookies || [] ).map( function ( p ) {
			return new RegExp( p );
		} );
		if ( ! patterns.length ) {
			return;
		}
		document.cookie.split( '; ' ).forEach( function ( pair ) {
			var name = pair.split( '=' )[ 0 ];
			if ( ! patterns.some( function ( re ) {
				return re.test( name );
			} ) ) {
				return;
			}
			domains().forEach( function ( d ) {
				document.cookie = name + '=; Max-Age=0; Path=/' + d;
			} );
		} );
	}

	function addScript( src ) {
		var s = document.createElement( 'script' );
		s.async = true;
		s.src = src;
		s.setAttribute( 'data-bfc-loaded', '1' );
		document.head.appendChild( s );
	}

	function loadTags( choices ) {
		if ( cfg.ga4 && choices.analytics ) {
			window[ 'ga-disable-' + cfg.ga4 ] = false;
		}
		if ( cfg.ga4 && choices.analytics && ! loaded.ga4 ) {
			loaded.ga4 = true;
			addScript( 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( cfg.ga4 ) );
			window.gtag( 'js', new Date() );
			window.gtag( 'config', cfg.ga4 );
		}
		var anyOptional = slugs().some( function ( slug ) {
			return ! cats[ slug ].required && choices[ slug ];
		} );
		if ( cfg.gtm && anyOptional && ! loaded.gtm ) {
			loaded.gtm = true;
			window.dataLayer.push( { 'gtm.start': Date.now(), event: 'gtm.js' } );
			addScript( 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent( cfg.gtm ) );
		}
		if ( cfg.ga4 && ! choices.analytics ) {
			window[ 'ga-disable-' + cfg.ga4 ] = true;
		}
	}

	/**
	 * Run scripts held as <script type="text/plain" data-bfc-category="slug">.
	 */
	function activateScripts( choices ) {
		var held = Array.prototype.filter.call(
			document.querySelectorAll( 'script[type="text/plain"][data-bfc-category]:not([data-bfc-done])' ),
			function ( old ) {
				return !! choices[ old.getAttribute( 'data-bfc-category' ) ];
			}
		);
		// One at a time, in document order: an external script that is not
		// async must finish loading before the next held script runs.
		( function next( i ) {
			if ( i >= held.length ) {
				return;
			}
			var old = held[ i ];
			old.setAttribute( 'data-bfc-done', '1' );
			var s = document.createElement( 'script' );
			Array.prototype.forEach.call( old.attributes, function ( a ) {
				if ( a.name === 'type' || a.name.indexOf( 'data-bfc-' ) === 0 ) {
					return;
				}
				s.setAttribute( a.name, a.value );
			} );
			var type = old.getAttribute( 'data-bfc-type' );
			if ( type ) {
				s.type = type;
			}
			var src = old.getAttribute( 'data-bfc-src' );
			var wait = false;
			if ( src ) {
				s.async = old.hasAttribute( 'async' );
				wait = ! s.async;
				if ( wait ) {
					s.onload = s.onerror = function () {
						next( i + 1 );
					};
				}
				s.src = src;
			} else {
				s.text = old.text;
			}
			old.parentNode.insertBefore( s, old.nextSibling );
			if ( ! wait ) {
				next( i + 1 );
			}
		}( 0 ) );
	}

	function apply( choices ) {
		window.gtag( 'consent', 'update', signals( choices ) );
		loadTags( choices );
		activateScripts( choices );
	}

	function gpcOn() {
		return !! cfg.gpc && navigator.globalPrivacyControl === true;
	}

	function grantsOptional( c ) {
		return slugs().some( function ( slug ) {
			return ! cats[ slug ].required && c[ slug ] === true;
		} );
	}

	function banner() {
		return document.getElementById( 'bfc-banner' );
	}

	function showReopen( show ) {
		Array.prototype.forEach.call( document.querySelectorAll( '.bfc-reopen' ), function ( b ) {
			b.hidden = ! show;
		} );
	}

	var api = {
		get: current,
		granted: function ( slug ) {
			var rec = current();
			var c = rec ? rec.c : defaults;
			return !! ( cats[ slug ] && ( cats[ slug ].required || ( c && c[ slug ] === true ) ) );
		},
		set: function ( choices, source ) {
			var prev = current();
			var next = normalise( choices || {} );
			var rec = {
				v: cfg.version,
				c: next,
				id: prev && prev.id ? prev.id : ( readCookie() || {} ).id || randomId(),
				t: Math.floor( Date.now() / 1000 ),
				s: source || 'banner',
				g: gpcOn()
			};
			writeCookie( rec );
			defaults = null;
			var kept = readCookie();
			slugs().forEach( function ( slug ) {
				if ( ! next[ slug ] && ( ! prev || prev.c[ slug ] !== false ) ) {
					deleteCookies( slug );
				}
			} );
			apply( next );
			api.close();
			document.dispatchEvent( new CustomEvent( 'bfconsent:change', {
				detail: { choices: next, previous: prev ? prev.c : null, source: rec.s, id: rec.id, version: rec.v, stored: !! ( kept && kept.id === rec.id && kept.t === rec.t ) }
			} ) );
			return rec;
		},
		acceptAll: function ( source ) {
			var all = {};
			slugs().forEach( function ( slug ) {
				all[ slug ] = true;
			} );
			return api.set( all, source || 'accept' );
		},
		rejectAll: function ( source ) {
			return api.set( {}, source || 'reject' );
		},
		applyDefault: function ( choices, force ) {
			// Without force, a stored choice or a GPC signal wins.
			if ( ! force && ( current() || gpcOn() ) ) {
				return false;
			}
			defaults = normalise( choices || {} );
			apply( defaults );
			document.dispatchEvent( new CustomEvent( 'bfconsent:default', { detail: { choices: defaults } } ) );
			return true;
		},
		open: function () {
			var b = banner();
			if ( ! b ) {
				return;
			}
			b.hidden = false;
			showReopen( false );
			document.dispatchEvent( new CustomEvent( 'bfconsent:open' ) );
			var title = document.getElementById( 'bfc-title' );
			if ( title ) {
				title.focus();
			}
		},
		close: function () {
			var b = banner();
			if ( b ) {
				b.hidden = true;
			}
			showReopen( true );
		}
	};
	window.BFConsent = api;

	function init() {
		document.addEventListener( 'click', function ( e ) {
			var t = e.target.closest ? e.target.closest( '[data-bfc-action],[data-bfc-open],a[href="#bfc-settings"]' ) : null;
			if ( ! t ) {
				return;
			}
			var action = t.getAttribute( 'data-bfc-action' );
			if ( action === 'accept' ) {
				api.acceptAll();
			} else if ( action === 'reject' ) {
				api.rejectAll();
			} else if ( ! action ) {
				e.preventDefault();
				api.open();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			var b = banner();
			if ( e.key === 'Escape' && b && ! b.hidden && current() ) {
				api.close();
			}
		} );

		var rec = current();
		if ( rec && gpcOn() && ! rec.g && grantsOptional( rec.c ) ) {
			// GPC switched on after an earlier opt-in: GPC wins. An opt-in made
			// while GPC was already on (rec.g) is the visitor's explicit choice.
			api.rejectAll( 'gpc' );
		} else if ( rec ) {
			apply( normalise( rec.c ) );
			showReopen( true );
		} else if ( gpcOn() ) {
			api.rejectAll( 'gpc' );
		} else if ( ! document.dispatchEvent( new CustomEvent( 'bfconsent:first-visit', { cancelable: true } ) ) ) {
			// An add-on took over. If it has neither opened the banner nor
			// applied a default after 5 seconds, open it so the visitor can choose.
			window.setTimeout( function () {
				var b = banner();
				if ( ! current() && ! defaults && b && b.hidden ) {
					api.open();
				}
			}, 5000 );
		} else {
			api.open();
			// Opening on load must not steal focus from the page.
			if ( document.activeElement && document.activeElement.id === 'bfc-title' ) {
				document.activeElement.blur();
			}
		}
		if ( location.hash === '#bfc-settings' ) {
			api.open();
		}
		window.addEventListener( 'hashchange', function () {
			if ( location.hash === '#bfc-settings' ) {
				api.open();
			}
		} );
	}

	// Start at DOMContentLoaded, which fires after every deferred script, so
	// add-ons loaded after this file are listening before the first event.
	// 'load' is the fallback for a copy of this file injected later.
	var started = false;
	function start() {
		if ( ! started ) {
			started = true;
			init();
		}
	}
	if ( document.readyState === 'complete' ) {
		start();
	} else {
		document.addEventListener( 'DOMContentLoaded', start );
		window.addEventListener( 'load', start );
	}
}() );
