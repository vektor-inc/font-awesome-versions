<?php
/**
 * Lightweight WordPress stubs for running PHPUnit without a full WP install.
 */

use PHPUnit\Framework\TestCase;

// Basic in-memory option storage.
$GLOBALS['vkfav_options'] = array();

if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	class WP_UnitTestCase extends TestCase {
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'did_action' ) ) {
	function did_action() {
		return 0;
	}
}

if ( ! function_exists( 'load_textdomain' ) ) {
	function load_textdomain() {
		return false;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'get_user_locale' ) ) {
	function get_user_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}

if ( ! function_exists( 'site_url' ) ) {
	function site_url( $path = '/' ) {
		$base = 'http://example.org';
		if ( '/' !== $path ) {
			$path = '/' . ltrim( $path, '/' );
		}
		return $base . $path;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name ) {
		return isset( $GLOBALS['vkfav_options'][ $name ] ) ? $GLOBALS['vkfav_options'][ $name ] : false;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value ) {
		$GLOBALS['vkfav_options'][ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		unset( $GLOBALS['vkfav_options'][ $name ] );
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style() {
		// No-op in stub mode.
	}
}

// Load the plugin code.
require dirname( __DIR__ ) . '/src/VkFontAwesomeVersions.php';
