<?php
// Require composer dependencies.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// If we're running in WP's build directory, ensure that WP knows that, too.
if ( 'build' === getenv( 'LOCAL_DIR' ) ) {
	define( 'WP_RUN_CORE_TESTS', true );
}

// Determine the tests directory (from a WP dev checkout).
// Try the WP_TESTS_DIR environment variable first.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Next, try the WP_PHPUNIT composer package.
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

// See if we're installed inside an existing WP dev instance.
if ( ! $_tests_dir ) {
	$_try_tests_dir = __DIR__ . '/../../../../../tests/phpunit';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
}
// Fallback.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Provide sane defaults when not defined by wp-tests-config.php (e.g. when running via wp-phpunit).
if ( ! defined( 'WP_TESTS_DOMAIN' ) ) {
	define( 'WP_TESTS_DOMAIN', 'example.org' );
}
if ( ! defined( 'WP_TESTS_EMAIL' ) ) {
	define( 'WP_TESTS_EMAIL', 'admin@example.org' );
}
if ( ! defined( 'WP_TESTS_TITLE' ) ) {
	define( 'WP_TESTS_TITLE', 'Test Blog' );
}
if ( ! defined( 'WP_PHP_BINARY' ) ) {
	define( 'WP_PHP_BINARY', PHP_BINARY );
}

/**
 * If the WordPress test suite is unavailable (or explicitly disabled),
 * fall back to lightweight stubs so PHPUnit can run in standalone mode.
 */
$use_wp_suite = getenv( 'USE_WP_TEST_SUITE' );
if ( empty( $use_wp_suite ) || ! file_exists( $_tests_dir . '/includes/bootstrap.php' ) ) {
	require __DIR__ . '/wp-stubs.php';
	return;
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// Do not try to load JavaScript files from an external URL - this takes a
// while.
define( 'GUTENBERG_LOAD_VENDOR_SCRIPTS', false );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/src/VkFontAwesomeVersions.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Adds a wp_die handler for use during tests.
 *
 * If bootstrap.php triggers wp_die, it will not cause the script to fail. This
 * means that tests will look like they passed even though they should have
 * failed. So we throw an exception if WordPress dies during test setup. This
 * way the failure is observable.
 *
 * @param string|WP_Error $message The error message.
 *
 * @throws Exception When a `wp_die()` occurs.
 */
function fail_if_died( $message ) {
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	throw new Exception( 'WordPress died: ' . $message );
}
tests_add_filter( 'wp_die_handler', 'fail_if_died' );

$GLOBALS['wp_tests_options'] = array(
	'gutenberg-experiments' => array(
		'gutenberg-widget-experiments' => '1',
	),
);

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Use existing behavior for wp_die during actual test execution.
remove_filter( 'wp_die_handler', 'fail_if_died' );
