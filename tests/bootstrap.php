<?php
/**
 * PHPUnit bootstrap: small recorded WordPress stubs, no database, no WordPress.
 *
 * Hooks really dispatch, options live in an array, and every output function
 * records what it was given so tests can assert on it.
 *
 * The Pro add-on's suite includes this file too (BFCONSENT_TEST_FREE_DIR).
 *
 * @package BlueFairy\Consent
 */

declare(strict_types=1);

// phpcs:disable

$bfconsent_free_dir = getenv( 'BFCONSENT_TEST_FREE_DIR' ) ?: dirname( __DIR__ );

spl_autoload_register(
	static function ( string $class ) use ( $bfconsent_free_dir ): void {
		$map = [
			'BlueFairy\\Consent\\Tests\\' => $bfconsent_free_dir . '/tests/',
			'BlueFairy\\Consent\\'        => $bfconsent_free_dir . '/includes/',
		];
		foreach ( $map as $prefix => $dir ) {
			if ( str_starts_with( $class, $prefix ) ) {
				$file = $dir . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
				if ( is_readable( $file ) ) {
					require $file;
				}
				return;
			}
		}
	},
	true,
	true
);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/' );
}
if ( ! defined( 'BFCONSENT_DIR' ) ) {
	define( 'BFCONSENT_DIR', $bfconsent_free_dir . '/' );
	define( 'BFCONSENT_URL', 'https://example.com/wp-content/plugins/bluefairy-consent/' );
	define( 'BFCONSENT_VERSION', '1.0.0' );
	define( 'BFCONSENT_FILE', $bfconsent_free_dir . '/bluefairy-consent.php' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
	define( 'OBJECT', 'OBJECT' );
	define( 'DAY_IN_SECONDS', 86400 );
	define( 'HOUR_IN_SECONDS', 3600 );
}

function bfconsent_test_reset(): void {
	$GLOBALS['bfct'] = [
		'filters'   => [],
		'options'   => [],
		'scripts'   => [],
		'styles'    => [],
		'inline'    => [],
		'shortcodes'=> [],
		'settings'  => [],
		'pages'     => [],
		'can'       => true,
		'actions'   => [],
		'transients'=> [],
		'headers'   => [],
		'routes'    => [],
		'cli'       => [],
		'die'       => null,
		'time'      => null,
		'multisite' => false,
		'blog'      => 1,
		'blogstack' => [],
		'sites'     => [],
		'switches'  => [],
	];
}
bfconsent_test_reset();

// Multisite: each site has its own option store; switch_to_blog swaps it in
// and moves $wpdb->prefix the way core does (wp_ for site 1, wp_N_ otherwise).
function is_multisite(): bool { return (bool) $GLOBALS['bfct']['multisite']; }
function get_sites( array $args = [] ): array {
	$GLOBALS['bfct']['get_sites_args'] = $args;
	$ids = isset( $GLOBALS['bfct']['site_ids'] ) ? array_values( $GLOBALS['bfct']['site_ids'] ) : array_keys( $GLOBALS['bfct']['sites'] );
	return ( $args['fields'] ?? '' ) === 'ids' ? $ids : array_map( static fn( $id ) => (object) [ 'blog_id' => $id ], $ids );
}
function bfconsent_test_select_blog( int $id ): void {
	$t = &$GLOBALS['bfct'];
	$t['sites'][ $t['blog'] ] = $t['options'];
	$t['blog']    = $id;
	$t['options'] = $t['sites'][ $id ] ?? [];
	if ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) && property_exists( $GLOBALS['wpdb'], 'prefix' ) ) {
		$GLOBALS['wpdb']->prefix = 1 === $id ? 'wp_' : 'wp_' . $id . '_';
	}
}
function switch_to_blog( int $id ): bool {
	$GLOBALS['bfct']['blogstack'][] = $GLOBALS['bfct']['blog'];
	$GLOBALS['bfct']['switches'][]  = $id;
	bfconsent_test_select_blog( $id );
	return true;
}
function restore_current_blog(): bool {
	$prev = array_pop( $GLOBALS['bfct']['blogstack'] );
	if ( null === $prev ) {
		return false;
	}
	bfconsent_test_select_blog( $prev );
	return true;
}

// Hooks.
function add_filter( string $hook, callable|array|string $cb, int $priority = 10, int $args = 1 ): bool {
	$GLOBALS['bfct']['filters'][ $hook ][ $priority ][] = [ $cb, $args ];
	return true;
}
function add_action( string $hook, callable|array|string $cb, int $priority = 10, int $args = 1 ): bool {
	return add_filter( $hook, $cb, $priority, $args );
}
function remove_all_filters( string $hook ): void {
	unset( $GLOBALS['bfct']['filters'][ $hook ] );
}
function has_filter( string $hook, mixed $cb = false ): bool|int {
	if ( empty( $GLOBALS['bfct']['filters'][ $hook ] ) ) {
		return false;
	}
	if ( false === $cb ) {
		return true;
	}
	foreach ( $GLOBALS['bfct']['filters'][ $hook ] as $priority => $list ) {
		foreach ( $list as $entry ) {
			if ( $entry[0] === $cb ) {
				return $priority;
			}
		}
	}
	return false;
}
function has_action( string $hook, mixed $cb = false ): bool|int {
	return has_filter( $hook, $cb );
}
function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
	$list = $GLOBALS['bfct']['filters'][ $hook ] ?? [];
	ksort( $list );
	foreach ( $list as $entries ) {
		foreach ( $entries as [ $cb, $n ] ) {
			$value = $cb( ...array_slice( array_merge( [ $value ], $args ), 0, $n ) );
		}
	}
	return $value;
}
function do_action( string $hook, mixed ...$args ): void {
	$GLOBALS['bfct']['actions'][] = $hook;
	$list = $GLOBALS['bfct']['filters'][ $hook ] ?? [];
	ksort( $list );
	foreach ( $list as $entries ) {
		foreach ( $entries as [ $cb, $n ] ) {
			$cb( ...array_slice( $args, 0, $n ) );
		}
	}
}

// Options and transients.
function get_option( string $name, mixed $default = false ): mixed {
	return array_key_exists( $name, $GLOBALS['bfct']['options'] ) ? $GLOBALS['bfct']['options'][ $name ] : $default;
}
function update_option( string $name, mixed $value, mixed $autoload = null ): bool {
	$GLOBALS['bfct']['options'][ $name ] = $value;
	return true;
}
function add_option( string $name, mixed $value = '', string $deprecated = '', mixed $autoload = null ): bool {
	if ( array_key_exists( $name, $GLOBALS['bfct']['options'] ) ) {
		return false;
	}
	$GLOBALS['bfct']['options'][ $name ] = $value;
	return true;
}
function delete_option( string $name ): bool {
	unset( $GLOBALS['bfct']['options'][ $name ] );
	return true;
}
function get_transient( string $name ): mixed {
	return $GLOBALS['bfct']['transients'][ $name ] ?? false;
}
function set_transient( string $name, mixed $value, int $exp = 0 ): bool {
	$GLOBALS['bfct']['transients'][ $name ] = $value;
	return true;
}

// i18n and escaping.
function __( string $t, string $d = 'default' ): string { return $t; }
function esc_html__( string $t, string $d = 'default' ): string { return esc_html( $t ); }
function esc_html_e( string $t, string $d = 'default' ): void { echo esc_html( $t ); }
function esc_html( mixed $t ): string { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( mixed $t ): string { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( mixed $t ): string { return htmlspecialchars( (string) $t, ENT_QUOTES, 'UTF-8' ); }
function esc_url( mixed $u ): string { return htmlspecialchars( esc_url_raw( $u ), ENT_QUOTES, 'UTF-8' ); }
function esc_url_raw( mixed $u ): string {
	$u = trim( (string) $u );
	return 1 === preg_match( '#^https?://#i', $u ) ? $u : '';
}
function sanitize_text_field( mixed $t ): string { return trim( strip_tags( (string) $t ) ); }
function sanitize_textarea_field( mixed $t ): string { return trim( strip_tags( (string) $t ) ); }
function sanitize_key( mixed $k ): string { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_html_class( mixed $c ): string { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $c ); }
function sanitize_hex_color( string $c ): ?string { return 1 === preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $c ) ? $c : null; }
function wp_kses( string $html, array $allowed ): string {
	return strip_tags( $html, array_map( static fn( $t ) => '<' . $t . '>', array_keys( $allowed ) ) );
}
function wp_json_encode( mixed $data, int $flags = 0 ): string|false { return json_encode( $data, $flags ); }
function absint( mixed $n ): int { return abs( (int) $n ); }
function wp_unslash( mixed $v ): mixed { return is_string( $v ) ? stripslashes( $v ) : $v; }
function selected( mixed $a, mixed $b, bool $echo = true ): string { return (string) $a === (string) $b ? ' selected="selected"' : ''; }
function checked( mixed $a, mixed $b = true, bool $echo = true ): string { return (string) $a === (string) $b ? ' checked="checked"' : ''; }
function wp_salt( string $scheme = 'auth' ): string { return 'test-salt'; }
function current_time( string $type, bool $gmt = false ): string|int {
	$t = $GLOBALS['bfct']['time'] ?? 1794000000;
	return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s', $t ) : $t;
}

// Assets and output.
function plugin_dir_path( string $f ): string { return dirname( $f ) . '/'; }
function plugin_dir_url( string $f ): string { return 'https://example.com/wp-content/plugins/' . basename( dirname( $f ) ) . '/'; }
function wp_enqueue_script( string $h, string $src = '', array $deps = [], mixed $ver = false, mixed $args = [] ): void {
	$GLOBALS['bfct']['scripts'][ $h ] = compact( 'src', 'deps', 'ver', 'args' );
}
function wp_enqueue_style( string $h, string $src = '', array $deps = [], mixed $ver = false ): void {
	$GLOBALS['bfct']['styles'][ $h ] = compact( 'src', 'deps', 'ver' );
}
function wp_add_inline_style( string $h, string $css ): bool {
	$GLOBALS['bfct']['inline'][ 'style:' . $h ] = $css;
	return true;
}
function wp_print_inline_script_tag( string $js, array $attrs = [] ): void {
	echo '<script id="' . esc_attr( $attrs['id'] ?? '' ) . '">' . $js . '</script>';
}
function add_shortcode( string $tag, callable|array $cb ): void { $GLOBALS['bfct']['shortcodes'][ $tag ] = $cb; }
function shortcode_atts( array $pairs, array $atts, string $sc = '' ): array {
	$out = [];
	foreach ( $pairs as $k => $v ) {
		$out[ $k ] = $atts[ $k ] ?? $v;
	}
	return $out;
}

// Admin.
function register_setting( string $group, string $name, array $args = [] ): void { $GLOBALS['bfct']['settings'][ $name ] = $args; }
function add_options_page( string $t, string $m, string $cap, string $slug, callable|array $cb ): string {
	$GLOBALS['bfct']['pages'][ $slug ] = [ 'parent' => 'options-general.php', 'cap' => $cap, 'cb' => $cb ];
	return 'settings_page_' . $slug;
}
function add_submenu_page( string $parent, string $t, string $m, string $cap, string $slug, callable|array $cb ): string {
	$GLOBALS['bfct']['pages'][ $slug ] = [ 'parent' => $parent, 'cap' => $cap, 'cb' => $cb ];
	return 'page_' . $slug;
}
function current_user_can( string $cap ): bool { return (bool) $GLOBALS['bfct']['can']; }
function settings_fields( string $g ): void { echo '<input type="hidden" name="option_page" value="' . esc_attr( $g ) . '">'; }
function submit_button( string $text = 'Save Changes' ): void { echo '<input type="submit" value="' . esc_attr( $text ) . '">'; }
function admin_url( string $path = '' ): string { return 'https://example.com/wp-admin/' . $path; }
function rest_url( string $path = '' ): string { return 'https://example.com/wp-json/' . ltrim( $path, '/' ); }
function wp_nonce_url( string $url, string $action ): string { return $url . '&_wpnonce=nonce-' . $action; }
function wp_nonce_field( string $action, string $name = '_wpnonce' ): void { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce-' . esc_attr( $action ) . '">'; }
function wp_verify_nonce( mixed $nonce, string $action ): int|false { return 'nonce-' . $action === $nonce ? 1 : false; }
function check_admin_referer( string $action, string $name = '_wpnonce' ): int {
	$nonce = $_REQUEST[ $name ] ?? '';
	if ( 'nonce-' . $action !== $nonce ) {
		throw new \RuntimeException( 'bad nonce' );
	}
	return 1;
}
function wp_die( mixed $msg = '', mixed $title = '', mixed $args = [] ): void {
	throw new \RuntimeException( 'wp_die: ' . ( is_string( $msg ) ? $msg : '' ) );
}

bfconsent_test_reset();
