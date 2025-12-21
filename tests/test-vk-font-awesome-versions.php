<?php
use VektorInc\VK_Font_Awesome_Versions\VkFontAwesomeVersions;

class VkFontAwesomeVersionsTest extends WP_UnitTestCase {

	/**
	 * Prepare the test environment by clearing Vk Font Awesome related options.
	 *
	 * This setup runs before each test and ensures the options
	 * `vk_font_awesome_version`, `vk_font_awesome_compatibilities`, and
	 * `vk_font_awesome_options` are removed so tests start from a clean state.
	 */
	public function setUp(): void {
		parent::setUp();
		delete_option( 'vk_font_awesome_version' );
		delete_option( 'vk_font_awesome_compatibilities' );
		delete_option( 'vk_font_awesome_options' );
	}

	/**
	 * Verifies VkFontAwesomeVersions::get_icon_tag() produces the expected <i> HTML for various saved values and additional classes.
	 *
	 * Tests scenarios where the saved icon value is either a class string or a full `<i>` tag, with and without an extra CSS class,
	 * while font-awesome compatibility options are set to v4 and v5 as false.
	 *
	 * @return void
	 */
	function test_get_icon_tag() {

		$tests = array(
			array(
				'option_fa_version' => '7_WebFonts_CSS',
				'saved_value'       => 'far fa-file-alt',
				'correct'           => '<i class="far fa-file-alt"></i>',
			),
			array(
				'option_fa_version' => '7_WebFonts_CSS',
				'saved_value'       => '<i class="far fa-file-alt"></i>',
				'correct'           => '<i class="far fa-file-alt"></i>',
			),
			array(
				'option_fa_version' => '7_WebFonts_CSS',
				'saved_value'       => 'far fa-file-alt',
				'additional_class'  => 'test-class',
				'correct'           => '<i class="far fa-file-alt test-class"></i>',
			),
			array(
				'option_fa_version' => '7_WebFonts_CSS',
				'saved_value'       => '<i class="far fa-file-alt"></i>',
				'additional_class'  => 'test-class',
				'correct'           => '<i class="far fa-file-alt test-class"></i>',
			),
		);

			foreach ( $tests as $value ) {
				update_option( 'vk_font_awesome_version', $value['option_fa_version'] );
				update_option(
					'vk_font_awesome_compatibilities',
					array(
					'v4' => false,
					'v5' => false,
				)
			);
			if ( ! empty( $value['additional_class'] ) ) {
				$return = VkFontAwesomeVersions::get_icon_tag( $value['saved_value'], $value['additional_class'] );
			} else {
				$return = VkFontAwesomeVersions::get_icon_tag( $value['saved_value'] );
			}
			$this->assertEquals( $value['correct'], $return );
		}
	}

	function test_get_directory_uri() {
		$tests = array(
			array(
				'path'    => '/var/www/html/wp-content/themes/lightning-pro/vendor/vektor-inc/font-awesome-versions/src',
				'correct' => site_url( '/' ) . 'wp-content/themes/lightning-pro/vendor/vektor-inc/font-awesome-versions/src/',
			),
		);
		foreach ( $tests as $key => $value ) {
			$return = VkFontAwesomeVersions::get_directory_uri( $value['path'] );
			$this->assertEquals( $value['correct'], $return );
		}
	}

	/**
	 * Verifies that get_option_compatibilities() returns and saves default compatibilities when the option is absent.
	 *
	 * @return void
	 */
	function test_get_option_compatibilities_default() {
		delete_option( 'vk_font_awesome_compatibilities' );

		$return = VkFontAwesomeVersions::get_option_compatibilities();
		$this->assertEquals(
			array(
				'v4' => false,
				'v5' => false,
			),
			$return
		);
		$this->assertEquals( $return, get_option( 'vk_font_awesome_compatibilities' ) );
	}

	/**
	 * Verify that get_option_fa() normalizes stored FA version values and updates compatibility options.
	 *
	 * Iterates several stored `vk_font_awesome_version` inputs, calls VkFontAwesomeVersions::get_option_fa(),
	 * and asserts the returned canonical version, the saved `vk_font_awesome_version` option, and the
	 * `vk_font_awesome_compatibilities` option match expected values for each case.
	 *
	 * @return void
	 */
	function test_get_option_fa() {
		$tests = array(
			array(
				'option_fa_version' => '4.7',
				'correct_version'   => '7_WebFonts_CSS',
				'correct_compatibilities' => array(
					'v4' => true,
					'v5' => false,
				),
			),
			array(
				'option_fa_version' => '5.0_WebFonts_CSS',
				'correct_version'   => '7_WebFonts_CSS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => true,
				),
			),
			array(
				'option_fa_version' => '5.0_SVG_JS',
				'correct_version'   => '7_SVG_JS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => true,
				),
			),
			array(
				'option_fa_version' => '5_WebFonts_CSS',
				'correct_version'   => '7_WebFonts_CSS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => true,
				),
			),
			array(
				'option_fa_version' => '5_SVG_JS',
				'correct_version'   => '7_SVG_JS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => true,
				),
			),
			array(
				'option_fa_version' => '6_WebFonts_CSS',
				'correct_version'   => '7_WebFonts_CSS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => false,
				),
			),
			array(
				'option_fa_version' => '6_SVG_JS',
				'correct_version'   => '7_SVG_JS',
				'correct_compatibilities' => array(
					'v4' => false,
					'v5' => false,
				),
			),
		);

		foreach ( $tests as $value ) {
			update_option( 'vk_font_awesome_version', $value['option_fa_version'] );
			update_option(
				'vk_font_awesome_compatibilities',
				array(
					'v4' => false,
					'v5' => false,
				)
			);
			$return = VkFontAwesomeVersions::get_option_fa();
			$this->assertEquals( $value['correct_version'], $return );
			$this->assertEquals( $value['correct_version'], get_option( 'vk_font_awesome_version' ) );
			$this->assertEquals( $value['correct_compatibilities'], get_option( 'vk_font_awesome_compatibilities' ) );
		}
	}

	/**
	 * Verifies that class_switch selects the provided v7 class when using the two-argument signature.
	 *
	 * Sets the vk_font_awesome_version option to '7_WebFonts_CSS', ensures v4 and v5 compatibilities are false,
	 * calls VkFontAwesomeVersions::class_switch with v4 and v7 candidates, and asserts the selected class is 'v7'.
	 */
	function test_class_switch_returns_v7_class() {
		update_option( 'vk_font_awesome_version', '7_WebFonts_CSS' );
		update_option(
			'vk_font_awesome_compatibilities',
			array(
				'v4' => false,
				'v5' => false,
			)
		);

		$return = VkFontAwesomeVersions::class_switch( 'v4', 'v7' );
		$this->assertEquals( 'v7', $return );
	}
}