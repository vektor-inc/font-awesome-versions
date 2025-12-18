<?php
use VektorInc\VK_Font_Awesome_Versions\VkFontAwesomeVersions;

class VkFontAwesomeVersionsTest extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		delete_option( 'vk_font_awesome_version' );
		delete_option( 'vk_font_awesome_compatibilities' );
		delete_option( 'vk_font_awesome_options' );
	}

	/**
	 * Test get_icon_tag() method
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

		foreach ( $tests as $key => $value ) {
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

	function test_class_switch_returns_v7_class() {
		update_option( 'vk_font_awesome_version', '7_WebFonts_CSS' );
		update_option(
			'vk_font_awesome_compatibilities',
			array(
				'v4' => false,
				'v5' => false,
			)
		);

		$return = VkFontAwesomeVersions::class_switch( 'v4', 'v5', 'v6', 'v7' );
		$this->assertEquals( 'v7', $return );
	}
}
