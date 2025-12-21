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
	/**
	 * Provide a no-op filter handler that returns the input value unchanged.
	 *
	 * @param string $hook  Name of the filter hook.
	 * @param mixed  $value Value to be filtered.
	 * @return mixed The original `$value` passed in.
	 */
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub implementation of WordPress add_action that performs no operation.
	 *
	 * Allows code under test to call add_action without registering callbacks or changing global state.
	 */
	function add_action() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * No-op stub that emulates WordPress add_filter for test environments.
	 *
	 * Accepts any arguments and performs no operation so code that calls
	 * add_filter can run outside of a full WordPress runtime. */
	function add_filter() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'did_action' ) ) {
	/**
	 * Report the number of times a WordPress action has been fired in this test stub.
	 *
	 * @return int `0` as actions are not tracked in this test environment.
	 */
	function did_action() {
		return 0;
	}
}

if ( ! function_exists( 'load_textdomain' ) ) {
	/**
	 * No-op stub for loading a translation textdomain in test environments.
	 *
	 * @return false `false` to indicate the textdomain was not loaded.
	 */
	function load_textdomain() {
		return false;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	/**
	 * Indicates whether the current request is running in an admin context.
	 *
	 * This test stub always reports a non-admin context.
	 *
	 * @return bool `true` if running in an admin context, `false` otherwise (always `false` in this stub).
	 */
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'get_user_locale' ) ) {
	/**
	 * Retrieve the current user's locale identifier.
	 *
	 * This test stub always returns 'en_US'.
	 *
	 * @return string The locale identifier (for example, 'en_US').
	 */
	function get_user_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	/**
	 * Retrieve the site's locale identifier.
	 *
	 * @return string The locale identifier in the format `language_TERRITORY` (for example, `en_US`). Defaults to `en_US`.
	 */
	function get_locale() {
		return 'en_US';
	}
}

if ( ! function_exists( 'wp_normalize_path' ) ) {
	/**
	 * Normalize a filesystem path to use forward slashes as directory separators.
	 *
	 * @param string $path The path to normalize.
	 * @return string The normalized path with backslashes (`\`) replaced by forward slashes (`/`).
	 */
	function wp_normalize_path( $path ) {
		return str_replace( '\\', '/', $path );
	}
}

if ( ! function_exists( 'site_url' ) ) {
	/**
	 * Build a full site URL for a given path using http://example.org as the base.
	 *
	 * @param string $path Path relative to the site root; use '/' for the site root. Leading slashes are tolerated and normalized.
	 * @return string The full URL composed of the base and the normalized path.
	 */
	function site_url( $path = '/' ) {
		$base = 'http://example.org';
		if ( '/' !== $path ) {
			$path = '/' . ltrim( $path, '/' );
		}
		return $base . $path;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Escape text for safe insertion into an HTML attribute.
	 *
	 * @param string $text The text to escape for attribute context.
	 * @return string The escaped string suitable for inclusion in an HTML attribute.
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Escapes text for safe inclusion in HTML by converting special characters into HTML entities.
	 *
	 * @param string $text The text to escape.
	 * @return string The escaped string with HTML entities for special characters and quotes.
	 */
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Stubbed HTML sanitizer for post content that returns the provided text unchanged.
	 *
	 * @param string $text The HTML text to (normally) sanitize; returned verbatim in this stub.
	 * @return string The original input text unchanged.
	 */
	function wp_kses_post( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Merge user-supplied arguments with a set of defaults, accepting arrays or objects.
	 *
	 * If `$args` is an object, its public properties are used as the argument set.
	 * If `$args` is neither an array nor an object, it is treated as an empty set.
	 *
	 * @param array|object|mixed $args Arguments to merge; may be an array or an object whose public properties will be used.
	 * @param array $defaults Default values to fall back to when keys are missing from `$args`.
	 * @return array The merged array where values from `$args` override those in `$defaults`.
	 */
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
	/**
	 * Retrieve a stored option value from the in-memory test options store.
	 *
	 * @param string $name The option key to retrieve.
	 * @return mixed The stored value if present, or `false` if the option does not exist.
	 */
	function get_option( $name ) {
		return isset( $GLOBALS['vkfav_options'][ $name ] ) ? $GLOBALS['vkfav_options'][ $name ] : false;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Store or update an option in the in-memory test options store.
	 *
	 * @param string $name  The option name.
	 * @param mixed  $value The value to store for the option.
	 * @return bool True on success.
	 */
	function update_option( $name, $value ) {
		$GLOBALS['vkfav_options'][ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Removes an option from the in-memory options store.
	 *
	 * @param string $name The option name to remove.
	 * @return bool Always `true`.
	 */
	function delete_option( $name ) {
		unset( $GLOBALS['vkfav_options'][ $name ] );
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Retrieve the translation for a given text.
	 *
	 * @param string $text Text to translate.
	 * @return string The translated text (same as the input in this test stub).
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	/**
	 * No-op stub for attaching inline CSS to an enqueued stylesheet in the test environment.
	 *
	 * In test stubs this function intentionally does nothing so code that calls
	 * it can run without the full WordPress runtime.
	 */
	function wp_add_inline_style() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	/**
	 * No-op placeholder for WordPress's wp_enqueue_script used by the test stub.
	 */
	function wp_enqueue_script() {
		// No-op in stub mode.
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	/**
	 * No-op test stub for wp_enqueue_style.
	 *
	 * Performs no action so code that calls wp_enqueue_style during tests does not fail.
	 */
	function wp_enqueue_style() {
		// No-op in stub mode.
	}
}

// Load the plugin code.
require dirname( __DIR__ ) . '/src/VkFontAwesomeVersions.php';