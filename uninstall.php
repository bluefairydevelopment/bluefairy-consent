<?php
/**
 * Uninstall: remove the settings option. Visitors' choice cookies expire on their own.
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'bfconsent_settings' );
