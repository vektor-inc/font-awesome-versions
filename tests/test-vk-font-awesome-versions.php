<?php
use VektorInc\VK_Font_Awesome_Versions\VkFontAwesomeVersions;

class VkFontAwesomeVersionsTest extends WP_UnitTestCase {

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

		foreach ( $tests as $key => $value ) {
			$options = array(
				'version'       => $value['option_fa_version'],
				'compatibility' => array(
					'v4' => false,
					'v5' => false,
				),
			);
			update_option( 'vk_font_awesome_options', $options );
			if ( ! empty( $value['additional_class'] ) ) {
				$return = VkFontAwesomeVersions::get_icon_tag( $value['saved_value'], $value['additional_class'] );
			} else {
				$return = VkFontAwesomeVersions::get_icon_tag( $value['saved_value'] );
			}
			$this->assertEquals( $value['correct'], $return );
		}
	}

	function test_get_directory_uri() {
		// WP_PLUGIN_DIR / WPMU_PLUGIN_DIR / テーマルート / WP_CONTENT_DIR それぞれのパスから正しい URL が生成されることを確認する.
		$tests = array(
			// WP_PLUGIN_DIR 配下（プラグインの vendor ディレクトリ）.
			array(
				'path'    => WP_PLUGIN_DIR . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
				'correct' => plugins_url() . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src/',
			),
			// WPMU_PLUGIN_DIR 配下（mu-plugin の vendor ディレクトリ）.
			array(
				'path'    => WPMU_PLUGIN_DIR . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
				'correct' => WPMU_PLUGIN_URL . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src/',
			),
			// テーマルート配下（テーマの vendor ディレクトリ）.
			array(
				'path'    => get_theme_root() . '/lightning-pro/vendor/vektor-inc/font-awesome-versions/src',
				'correct' => get_theme_root_uri() . '/lightning-pro/vendor/vektor-inc/font-awesome-versions/src/',
			),
			// WP_CONTENT_DIR 配下（その他のディレクトリ）.
			array(
				'path'    => WP_CONTENT_DIR . '/libraries/vektor-inc/font-awesome-versions/src',
				'correct' => content_url() . '/libraries/vektor-inc/font-awesome-versions/src/',
			),
			// ディレクトリ名が前方一致で誤マッチしないことを確認する.
			// 例: /wp-content/plugins-extra は /wp-content/plugins にマッチしてはいけない.
			array(
				'path'    => WP_PLUGIN_DIR . '-extra/some-plugin/vendor/vektor-inc/font-awesome-versions/src',
				'correct' => content_url() . '/plugins-extra/some-plugin/vendor/vektor-inc/font-awesome-versions/src/',
			),
			// どのディレクトリにもマッチしないパスは空文字を返す.
			array(
				'path'    => '/opt/custom/path/vektor-inc/font-awesome-versions/src',
				'correct' => '',
			),
		);
		foreach ( $tests as $key => $value ) {
			$return = VkFontAwesomeVersions::get_directory_uri( $value['path'] );
			$this->assertEquals( $value['correct'], $return );
		}
	}

	function test_get_option_fa() {
		$tests = array(
			array(
				'option_fa_version' => '4.7',
				'correct'           => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(
						'v4' => true,
						'v5' => false,
					),
				),
			),
			array(
				'option_fa_version' => '5.0_WebFonts_CSS',
				'correct'           => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(
						'v4' => false,
						'v5' => true,
					),
				),
			),
			array(
				'option_fa_version' => '5.0_SVG_JS',
				'correct'           => array(
					'version'       => '7_SVG_JS',
					'compatibility' => array(
						'v4' => false,
						'v5' => true,
					),
				),
			),
			array(
				'option_fa_version' => '5_WebFonts_CSS',
				'correct'           => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(
						'v4' => false,
						'v5' => true,
					),
				),
			),
			array(
				'option_fa_version' => '5_SVG_JS',
				'correct'           => array(
					'version'       => '7_SVG_JS',
					'compatibility' => array(
						'v4' => false,
						'v5' => true,
					),
				),
			),
			array(
				'option_fa_version' => '6_WebFonts_CSS',
				'correct'           => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(
						'v4' => false,
						'v5' => false,
					),
				),
			),
			array(
				'option_fa_version' => '6_SVG_JS',
				'correct'           => array(
					'version'       => '7_SVG_JS',
					'compatibility' => array(
						'v4' => false,
						'v5' => false,
					),
				),
			),
			// versions() に存在しない不正な文字列はデフォルト（7_WebFonts_CSS）へフォールバックする。
			array(
				'option_fa_version' => 'invalid_value',
				'correct'           => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(
						'v4' => false,
						'v5' => false,
					),
				),
			),
		);

		foreach ( $tests as $key => $value ) {
			$options = array(
				'version'       => $value['option_fa_version'],
				'compatibility' => array(
					'v4' => false,
					'v5' => false,
				),
			);
			update_option( 'vk_font_awesome_options', $options );
			$return = VkFontAwesomeVersions::get_option_fa();
			$this->assertEquals( $value['correct'], $return );
		}
	}

	/**
	 * Test get_option_fa() : 旧 vk_font_awesome_version オプションの掃除
	 *
	 * 旧オプションが残っている場合、値を引き継いだか否かにかかわらず get_option_fa() 実行後に
	 * 旧オプションが削除され、DB にスタール値が残らないことを確認する。
	 *
	 * @return void
	 */
	function test_get_option_fa_deletes_legacy_version_option() {
		$tests = array(
			array(
				'test_condition_name' => '新形式に version が無く旧オプションだけある場合 => 旧オプションの値を引き継いで削除',
				'new_option'          => array( 'compatibility' => array() ),
				'legacy_version'      => '7_SVG_JS',
				'expected_version'    => '7_SVG_JS',
			),
			array(
				'test_condition_name' => '新形式が壊れたスカラー値かつ旧オプションもある場合 => デフォルトに寄せつつ旧オプションを削除',
				'new_option'          => 'broken-scalar',
				'legacy_version'      => '6_WebFonts_CSS',
				'expected_version'    => '7_WebFonts_CSS',
			),
		);

		foreach ( $tests as $case ) {
			// 新旧両方のオプションをセットする。
			update_option( 'vk_font_awesome_options', $case['new_option'] );
			update_option( 'vk_font_awesome_version', $case['legacy_version'] );

			$return = VkFontAwesomeVersions::get_option_fa();

			// version が期待どおり解決されていること。
			$this->assertEquals( $case['expected_version'], $return['version'], $case['test_condition_name'] );
			// 旧オプションが削除されていること（get_option は未設定時 false を返す）。
			$this->assertFalse( get_option( 'vk_font_awesome_version' ), $case['test_condition_name'] . '（旧オプション削除）' );
		}
	}

	/**
	 * Test current_info() method
	 *
	 * current_info() は get_option_fa() の正規化結果を基準にアセット情報を返す。
	 * レガシー値（4/5/6 系）は 7 系（SVG / CSS の別を保持）へ解決され、
	 * version キーが無い・配列・不正値など versions() に存在しないキーの場合は
	 * Fatal を起こさず 7 系 CSS（7_WebFonts_CSS）へフォールバックすることを確認する。
	 *
	 * @return void
	 */
	function test_current_info() {
		// versions() の実キー配列を基準にして期待値を組み立てる（URL は環境依存のため直書きしない）。
		$versions = VkFontAwesomeVersions::versions();

		// 各ケースは vk_font_awesome_options に保存する生の値（stored）と、current_info() が返すべき versions() のキー（expected_key）を持つ。
		$tests = array(
			// --- 正常系（7 系はそのまま解決） ---
			array(
				'test_condition_name' => '保存値が 7 系 Web Fonts の場合 => 7_WebFonts_CSS のアセット情報を返す',
				'stored'              => array(
					'version'       => '7_WebFonts_CSS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => '保存値が 7 系 SVG の場合 => 7_SVG_JS のアセット情報を返す',
				'stored'              => array(
					'version'       => '7_SVG_JS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_SVG_JS',
			),
			// --- レガシー値の移行（get_option_fa() 経由で 7 系へ。SVG / CSS の別は保持される） ---
			array(
				'test_condition_name' => '保存値がレガシー値（6 系 Web Fonts）の場合 => 7_WebFonts_CSS へ移行',
				'stored'              => array(
					'version'       => '6_WebFonts_CSS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => '保存値がレガシー値（6 系 SVG）の場合 => 7_SVG_JS へ移行（CSS に寄せない）',
				'stored'              => array(
					'version'       => '6_SVG_JS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_SVG_JS',
			),
			array(
				'test_condition_name' => '保存値がレガシー値（5 系 Web Fonts）の場合 => 7_WebFonts_CSS へ移行',
				'stored'              => array(
					'version'       => '5_WebFonts_CSS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => '保存値がレガシー値（5 系 SVG）の場合 => 7_SVG_JS へ移行（CSS に寄せない）',
				'stored'              => array(
					'version'       => '5_SVG_JS',
					'compatibility' => array(),
				),
				'expected_key'        => '7_SVG_JS',
			),
			array(
				'test_condition_name' => '保存値がレガシー値（4.7 系）の場合 => 7_WebFonts_CSS へ移行',
				'stored'              => array(
					'version'       => '4.7',
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			// --- 異常系・境界値（versions() に存在しないキーは Fatal を起こさず 7_WebFonts_CSS へフォールバック） ---
			array(
				'test_condition_name' => '保存値が未知の不正な文字列の場合 => 7_WebFonts_CSS へフォールバック',
				'stored'              => array(
					'version'       => 'invalid_value',
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => '保存値の配列に version キーが無い場合 => 7_WebFonts_CSS へフォールバック',
				'stored'              => array( 'compatibility' => array() ),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => 'version の値が文字列でなく配列の場合 => Fatal を起こさず 7_WebFonts_CSS へフォールバック',
				'stored'              => array(
					'version'       => array( 'broken' ),
					'compatibility' => array(),
				),
				'expected_key'        => '7_WebFonts_CSS',
			),
			array(
				'test_condition_name' => '保存オプション自体が配列でなく文字列の場合 => Fatal を起こさず 7_WebFonts_CSS へフォールバック',
				'stored'              => 'not-an-array',
				'expected_key'        => '7_WebFonts_CSS',
			),
		);

		foreach ( $tests as $case ) {
			// テスト対象の値を保存する。
			update_option( 'vk_font_awesome_options', $case['stored'] );
			$return = VkFontAwesomeVersions::current_info();
			// 期待するアセット情報（versions() の該当キー）と一致することを確認する。
			$this->assertEquals( $versions[ $case['expected_key'] ], $return, $case['test_condition_name'] );
		}
	}
}
