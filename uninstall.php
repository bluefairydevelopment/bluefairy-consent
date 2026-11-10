<?php
/**
 * Uninstall: remove the settings option on every site (each site of a
 * network). Visitors' choice cookies expire on their own. The plugin keeps no
 * network-level options.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) as $bfconsent_site_id ) {
		switch_to_blog( (int) $bfconsent_site_id );
		delete_option( 'bfconsent_settings' );
		restore_current_blog();
	}
} else {
	delete_option( 'bfconsent_settings' );
}
