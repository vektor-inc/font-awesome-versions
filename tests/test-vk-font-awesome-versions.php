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
			// どのディレクトリにもマッチしないパスは、ドメイン直結の壊れた URL を避けるため
			// 最後の手段として content_url()（末尾スラッシュ付き）を返す（空文字は返さない。issue #56）.
			array(
				'path'    => '/opt/custom/path/vektor-inc/font-awesome-versions/src',
				'correct' => trailingslashit( content_url() ),
			),
		);
		foreach ( $tests as $key => $value ) {
			$return = VkFontAwesomeVersions::get_directory_uri( $value['path'] );
			$this->assertEquals( $value['correct'], $return );
		}
	}

	/**
	 * private static メソッドをテストから呼び出すためのヘルパー.
	 *
	 * resolve_symlinked_plugin_path() / realpath_directories() / match_directory_uri() は
	 * ライブラリ外部から呼ぶ用途を想定していないため private static にしている（安藤のレビュー指摘 R2）。
	 * Composer パッケージとして配布しており public にすると破壊的変更なしに変更できなくなるため。
	 * テストからは ReflectionMethod 経由で呼び出す.
	 *
	 * @param string $method_name 呼び出す private static メソッド名.
	 * @param array  $args 引数の配列.
	 * @return mixed メソッドの戻り値.
	 */
	private function call_private_static_method( $method_name, array $args = array() ) {
		$reflection = new ReflectionMethod( VkFontAwesomeVersions::class, $method_name );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( null, $args );
	}

	/**
	 * Test get_directory_uri() : シンボリックリンクで WordPress が配置された環境（AWS Bitnami 等）の再現テスト.
	 *
	 * __DIR__ はシンボリックリンクを辿って実体のパスを返す一方、WordPress の定数（WP_PLUGIN_DIR 等）は
	 * シンボリックリンクを辿らない文字列のままのため、単純な前方一致ではどの基準ディレクトリにも一致せず、
	 * URL が壊れる不具合の再現テスト（issue #56）。
	 *
	 * 対応表 $wp_plugin_paths のキーは、WordPress 本体（wp_register_plugin_realpath()。wp-includes/plugin.php）が
	 * 「プラグイン 1 個分のディレクトリ」単位でしか登録しない。WP_PLUGIN_DIR / WPMU_PLUGIN_DIR そのものが
	 * キーになることは無い（プラグインのディレクトリが WP_PLUGIN_DIR 自身と一致する場合の早期 return がある
	 * ため）。このテストでもプラグイン単位のキー（WP_PLUGIN_DIR . '/プラグインディレクトリ名'）を使う。
	 *
	 * なお mu-plugins 配下のシンボリックリンクは、mu-plugins の一覧取得が WPMU_PLUGIN_DIR 直下のファイルしか
	 * 見ないため dirname() が WPMU_PLUGIN_DIR 自身と一致し、$wp_plugin_paths にエントリが登録されない
	 * （実環境では段階 (2) の realpath_directories() 側だけが解決する）。そちらは
	 * test_get_directory_uri_with_realpath_symlinked_mu_plugins() で検証する.
	 *
	 * @return void
	 */
	function test_get_directory_uri_with_symlinked_plugin_paths() {
		global $wp_plugin_paths;
		// テスト終了後に元へ戻すため退避しておく.
		$original_wp_plugin_paths = $wp_plugin_paths;

		// Bitnami 環境を模した、プラグイン 1 個分のディレクトリ単位のシンボリックリンク対応
		// （論理パス（定数ベース） => 実体パス）.
		$symlinked_plugin_dir = wp_normalize_path( WP_PLUGIN_DIR ) . '/vk-blocks';
		$real_plugin_dir      = '/bitnami/wordpress/wp-content/plugins/vk-blocks';

		$tests = array(
			array(
				'test_condition_name' => 'プラグイン配下がシンボリックリンク構成の場合 => plugins_url() ベースの正しい URL になる',
				'wp_plugin_paths'     => array(
					$symlinked_plugin_dir => $real_plugin_dir,
				),
				'path'                => $real_plugin_dir . '/vendor/vektor-inc/font-awesome-versions/src',
				'correct'             => plugins_url() . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src/',
			),
			array(
				'test_condition_name' => '対応表に無関係な（今回のパスに関係しない）エントリしか無く、通常構成のパスを渡した場合 => 従来どおり解決される（既存挙動への回帰が無いこと）',
				'wp_plugin_paths'     => array(
					$symlinked_plugin_dir => $real_plugin_dir,
				),
				'path'                => WP_PLUGIN_DIR . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
				'correct'             => plugins_url() . '/vk-blocks/vendor/vektor-inc/font-awesome-versions/src/',
			),
		);

		try {
			foreach ( $tests as $case ) {
				$wp_plugin_paths = $case['wp_plugin_paths'];
				$actual          = VkFontAwesomeVersions::get_directory_uri( $case['path'] );
				$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
			}
		} finally {
			// アサーション失敗で例外が飛んだ場合でも、後続テストへ偽の対応表を残さないよう必ず復元する
			// （WP_UnitTestCase は backupGlobals を有効にしていないため。安藤のレビュー指摘 M2）.
			$wp_plugin_paths = $original_wp_plugin_paths;
		}
	}

	/**
	 * Test get_directory_uri() : mu-plugins 配下がシンボリックリンク構成の場合の実環境再現テスト.
	 *
	 * mu-plugins 配下のシンボリックリンクは $wp_plugin_paths に登録されない
	 * （test_get_directory_uri_with_symlinked_plugin_paths() の PHPDoc 参照）ため、実環境では
	 * 段階 (2)（realpath_directories()）だけがこのケースを救う。$path の変換だけでなく、
	 * get_directory_uri() が参照する基準ディレクトリ（WPMU_PLUGIN_DIR）自体が実際に
	 * シンボリックリンクである状態を検証するため、このテストでは実際に symlink() を使って
	 * WPMU_PLUGIN_DIR のパスそのものにシンボリックリンクを作成する（安藤のレビュー指摘 M1）。
	 *
	 * WPMU_PLUGIN_DIR は既定では作成されない（mu-plugins は任意で使うディレクトリ）ため、
	 * このテストでは「存在しない場合にのみ」シンボリックリンクを作成し、テスト後に必ず元の
	 * 「存在しない」状態へ戻す。既に何か存在する場合（実ディレクトリ・別のシンボリックリンク問わず）は、
	 * 共有のテスト用 WordPress 環境を壊さないよう上書きせずスキップする。symlink() が使えない環境でも
	 * 同様にスキップする.
	 *
	 * なお、テーマルート（get_theme_root()）についても同じ理由（段階 (2) が唯一の救済経路）が成り立つが、
	 * テーマルートには実際に使用中のテーマ一式が既定で置かれており、そのパスをシンボリックリンクへ
	 * 差し替えるのは既存のテスト用 WordPress 環境を壊すリスクが高いため、本 PR ではテーマルートに対する
	 * 実シンボリックリンクの統合テストは追加していない（安藤の指摘どおり、テーマだけ個別に symlink する
	 * 構成は今回のスコープでも解決されず段階 (3) の content_url() フォールバックに落ちる）。
	 * realpath_directories() 自体がテーマルートを含む全ディレクトリに同一ロジックで適用されることは
	 * test_realpath_directories() の直接テストで担保している.
	 *
	 * @return void
	 */
	function test_get_directory_uri_with_realpath_symlinked_mu_plugins() {
		$mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );

		// 共有のテスト環境（WPMU_PLUGIN_DIR そのもの）を壊さないよう、既に何か存在する場合は作成しない.
		if ( file_exists( $mu_plugin_dir ) || is_link( $mu_plugin_dir ) ) {
			$this->markTestSkipped( 'WPMU_PLUGIN_DIR が既に存在するため、このテスト環境ではシンボリックリンクを作成しません。' );
			return;
		}

		// 実体ディレクトリを用意し、WPMU_PLUGIN_DIR のパスそのものをそこへのシンボリックリンクにする.
		$real_mu_plugin_dir = wp_normalize_path( sys_get_temp_dir() ) . '/vkfav-test-mu-real-' . uniqid();
		$mkdir_result       = mkdir( $real_mu_plugin_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- テスト用一時ディレクトリの作成.
		if ( ! $mkdir_result ) {
			// mkdir() の失敗を確認せずに進むと、symlink() が dangling link のまま「成功」してしまい、
			// realpath() が false を返して原因不明のまま assertion が落ちる（安藤のレビュー指摘 R7）.
			$this->markTestSkipped( 'このテスト環境ではテスト用の一時ディレクトリを作成できませんでした。' );
			return;
		}

		// sys_get_temp_dir() 自体がシンボリックリンクを含む環境（macOS の /var => /private/var 等）で
		// ネイティブ実行すると、realpath( WPMU_PLUGIN_DIR ) は最後まで解決した実体パスを返す一方
		// $real_mu_plugin_dir が未解決のままだと前方一致せず、段階 (3) に落ちて原因追跡しづらい形で
		// assertion が失敗する。$real_mu_plugin_dir 自体を realpath() へ通しておく（安藤のレビュー指摘 LOW-2）.
		$real_mu_plugin_dir = wp_normalize_path( realpath( $real_mu_plugin_dir ) );

		$symlink_created = @symlink( $real_mu_plugin_dir, $mu_plugin_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- symlink() が使えない実行環境向けの判定のため.

		if ( ! $symlink_created || ! is_link( $mu_plugin_dir ) ) {
			rmdir( $real_mu_plugin_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			$this->markTestSkipped( 'このテスト環境では symlink() を作成できませんでした。' );
			return;
		}

		// WordPress の起動処理（wp_get_mu_plugins() の is_dir( WPMU_PLUGIN_DIR )）とこのテストの
		// file_exists() で「存在しない」という stat 結果がキャッシュされたあとに symlink() しているため、
		// 環境によっては直後の realpath() 等が古い結果を返しうる。保険として明示的にクリアする
		// （安藤のレビュー指摘 LOW-3）.
		clearstatcache( true, $mu_plugin_dir );

		try {
			// __DIR__ 相当（実体パスのまま）を模したパスを渡す。$wp_plugin_paths は空のままなので、
			// 段階 (0)（従来どおりの前方一致）・段階 (1)（$wp_plugin_paths）はどちらも一致せず、
			// 段階 (2)（realpath_directories()）だけが解決できることを確認する.
			$path     = $real_mu_plugin_dir . '/some-mu-plugin/vendor/vektor-inc/font-awesome-versions/src';
			$actual   = VkFontAwesomeVersions::get_directory_uri( $path );
			$expected = WPMU_PLUGIN_URL . '/some-mu-plugin/vendor/vektor-inc/font-awesome-versions/src/';

			$this->assertEquals( $expected, $actual, 'mu-plugins 配下のシンボリックリンクが段階 (2)（realpath_directories()）経由で正しい URL に解決されること' );
		} finally {
			// WPMU_PLUGIN_DIR を元の「存在しない」状態へ確実に戻す.
			unlink( $mu_plugin_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink
			rmdir( $real_mu_plugin_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
	}

	/**
	 * Test resolve_symlinked_plugin_path() method.
	 *
	 * WordPress 本体の $wp_plugin_paths（論理パス（定数ベース）=> 実体パス（realpath）の対応表）を使って、
	 * 実体パス表記のパスを論理パス表記へ変換できることを確認する。
	 * ディレクトリ境界の誤マッチ（例: /srv/foo が /srv/foo-bar に誤マッチしない）と、
	 * 対応表の値が空文字の場合に全マッチしないこと（PHP 8 の strpos( $path, '' ) === 0 対策）も確認する
	 * （安藤のレビュー指摘 R1）.
	 *
	 * @return void
	 */
	function test_resolve_symlinked_plugin_path() {
		global $wp_plugin_paths;
		$original_wp_plugin_paths = $wp_plugin_paths;

		$tests = array(
			array(
				'test_condition_name' => '対応表に一致するエントリがある場合 => 実体パスの接頭辞を論理パスへ変換する',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins' => '/bitnami/wordpress/wp-content/plugins',
				),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
				'correct'             => '/opt/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
			),
			array(
				'test_condition_name' => '対応表に複数エントリがある場合 => より長い（より具体的な）実体パスに優先的にマッチする',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins'                  => '/bitnami/wordpress/wp-content/plugins',
					'/opt/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor' => '/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor-real',
				),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor-real/vektor-inc/font-awesome-versions/src',
				'correct'             => '/opt/bitnami/wordpress/wp-content/plugins/vk-blocks/vendor/vektor-inc/font-awesome-versions/src',
			),
			array(
				'test_condition_name' => '対応表が空の場合 => $path をそのまま返す（境界値）',
				'wp_plugin_paths'     => array(),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks/src',
				'correct'             => '/bitnami/wordpress/wp-content/plugins/vk-blocks/src',
			),
			array(
				'test_condition_name' => '対応表に一致するエントリが無い場合 => $path をそのまま返す（境界値）',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins' => '/bitnami/wordpress/wp-content/plugins',
				),
				'path'                => '/var/www/other/vk-blocks/src',
				'correct'             => '/var/www/other/vk-blocks/src',
			),
			array(
				'test_condition_name' => 'ディレクトリ境界外の誤マッチが起きないこと（境界値。R1） => /srv/foo-bar は /srv/foo に誤マッチせず $path をそのまま返す',
				'wp_plugin_paths'     => array(
					'/opt/foo' => '/srv/foo',
				),
				'path'                => '/srv/foo-bar/vk-blocks/src',
				'correct'             => '/srv/foo-bar/vk-blocks/src',
			),
			array(
				'test_condition_name' => '対応表の値（実体パス）が空文字の場合 => 全マッチを避けて $path をそのまま返す（境界値。R1。PHP 8 では strpos( $path, "" ) が 0 を返すため要注意）',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins' => '',
				),
				'path'                => '/any/path/at/all',
				'correct'             => '/any/path/at/all',
			),
		);

		try {
			foreach ( $tests as $case ) {
				$wp_plugin_paths = $case['wp_plugin_paths'];
				$actual          = $this->call_private_static_method( 'resolve_symlinked_plugin_path', array( $case['path'] ) );
				$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
			}
		} finally {
			// アサーション失敗で例外が飛んだ場合でも必ず復元する（M2）.
			$wp_plugin_paths = $original_wp_plugin_paths;
		}
	}

	/**
	 * Test realpath_directories() method.
	 *
	 * 基準ディレクトリの dir 側を realpath() で実体パスへ解決できること、
	 * 存在しないディレクトリ（realpath() が false を返すケース）は候補から除外されることを確認する.
	 *
	 * @return void
	 */
	function test_realpath_directories() {
		// テスト用に実体ディレクトリとそのシンボリックリンクを用意する.
		$real_dir     = wp_normalize_path( sys_get_temp_dir() ) . '/vkfav-test-real-' . uniqid();
		$mkdir_result = mkdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- テスト用一時ディレクトリの作成.

		if ( ! $mkdir_result ) {
			// mkdir() の失敗を見ずに進むと、symlink() が dangling link のまま「成功」扱いになり、
			// realpath() が false を返して assertCount() が原因不明のまま落ちる（安藤のレビュー指摘 R7）.
			$this->markTestSkipped( 'このテスト環境ではテスト用の一時ディレクトリを作成できませんでした。' );
			return;
		}

		$symlink_dir      = wp_normalize_path( sys_get_temp_dir() ) . '/vkfav-test-symlink-' . uniqid();
		$symlink_created = @symlink( $real_dir, $symlink_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- symlink() が使えない実行環境向けの判定のため.

		if ( ! $symlink_created || ! is_link( $symlink_dir ) ) {
			rmdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			$this->markTestSkipped( 'このテスト環境では symlink() を作成できませんでした。' );
			return;
		}

		$nonexistent_dir = wp_normalize_path( sys_get_temp_dir() ) . '/vkfav-test-missing-' . uniqid();

		// 後片付け前に期待値を確定させておく.
		$expected_real_dir = wp_normalize_path( realpath( $real_dir ) );

		$directories = array(
			// 正常系1: シンボリックリンクを realpath() で実体パスへ解決できる.
			array(
				'dir' => $symlink_dir,
				'url' => 'https://example.com/symlinked',
			),
			// 正常系2: 元々シンボリックリンクでない実体ディレクトリはそのまま解決される.
			array(
				'dir' => $real_dir,
				'url' => 'https://example.com/real',
			),
			// 異常系（境界値）: 存在しないディレクトリは realpath() が false を返すため候補から除外される.
			array(
				'dir' => $nonexistent_dir,
				'url' => 'https://example.com/missing',
			),
		);

		$result = $this->call_private_static_method( 'realpath_directories', array( $directories ) );

		// テスト用ファイルの後片付け.
		unlink( $symlink_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_unlink
		rmdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir

		$this->assertCount( 2, $result, '存在しないディレクトリは候補から除外され、2件だけ残ること' );
		$this->assertEquals( $expected_real_dir, $result[0]['dir'], 'シンボリックリンクが realpath() で実体パスへ解決されること' );
		$this->assertEquals( 'https://example.com/symlinked', $result[0]['url'], 'url はそのまま保持されること（シンボリックリンク側）' );
		$this->assertEquals( $expected_real_dir, $result[1]['dir'], '元々実体ディレクトリだったパスは変化しないこと' );
		$this->assertEquals( 'https://example.com/real', $result[1]['url'], 'url はそのまま保持されること（実体ディレクトリ側）' );
	}

	/**
	 * Test match_directory_uri() method.
	 *
	 * $path が基準ディレクトリの前方一致で判定され、URL に変換されること、
	 * ディレクトリ境界の誤マッチ（例: plugins と plugins-extra）が起きないことを確認する.
	 * 基準ディレクトリが末尾スラッシュ付きで渡された場合でも相対パスの先頭が欠けないことも確認する
	 * （安藤のレビュー指摘 R5）.
	 *
	 * @return void
	 */
	function test_match_directory_uri() {
		$directories = array(
			array(
				'dir' => '/wp-content/plugins',
				'url' => 'https://example.com/wp-content/plugins',
			),
			array(
				'dir' => '/wp-content',
				'url' => 'https://example.com/wp-content',
			),
		);

		$tests = array(
			array(
				'test_condition_name' => '基準ディレクトリ配下のパスは URL に変換される',
				'path'                => '/wp-content/plugins/sample-plugin/src',
				'correct'             => 'https://example.com/wp-content/plugins/sample-plugin/src/',
			),
			array(
				'test_condition_name' => '基準ディレクトリそのものを渡した場合も URL に変換される',
				'path'                => '/wp-content/plugins',
				'correct'             => 'https://example.com/wp-content/plugins/',
			),
			array(
				'test_condition_name' => '境界判定: /wp-content/plugins-extra は /wp-content/plugins に誤マッチせず、より広い /wp-content にマッチする',
				'path'                => '/wp-content/plugins-extra/sample-plugin/src',
				'correct'             => 'https://example.com/wp-content/plugins-extra/sample-plugin/src/',
			),
			array(
				'test_condition_name' => 'どの基準ディレクトリにも一致しない場合は空文字を返す（境界値）',
				'path'                => '/opt/custom/path',
				'correct'             => '',
			),
		);

		foreach ( $tests as $case ) {
			$actual = $this->call_private_static_method( 'match_directory_uri', array( $case['path'], $directories ) );
			$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
		}

		// R5: 基準ディレクトリが末尾スラッシュ付きで渡された場合でも、相対パスの先頭が欠けないこと.
		// （以前は比較にだけ untrailingslashit 後の値を使い、substr() は元の $directory['dir']（スラッシュ付き）の
		// 長さを使っていたため、WP_CONTENT_DIR 等が末尾スラッシュ付きで define() されている環境で
		// 相対パスの先頭が1文字欠ける不具合があった）.
		$trailing_slash_directories = array(
			array(
				'dir' => '/wp-content/plugins/',
				'url' => 'https://example.com/wp-content/plugins',
			),
		);
		$actual = $this->call_private_static_method(
			'match_directory_uri',
			array( '/wp-content/plugins/sample-plugin/src', $trailing_slash_directories )
		);
		$this->assertEquals(
			'https://example.com/wp-content/plugins/sample-plugin/src/',
			$actual,
			'基準ディレクトリが末尾スラッシュ付きで渡された場合でも、相対パスの先頭が欠けないこと（R5）'
		);

		// LOW-4: dir が '/' や '//' のように untrailingslashit() 後に空文字になる場合、
		// 全マッチ（strpos( $path, '/' ) === 0）を避けて空文字を返すこと（境界値）.
		$root_directories = array(
			array(
				'dir' => '/',
				'url' => 'https://example.com',
			),
			array(
				'dir' => '//',
				'url' => 'https://example.com',
			),
		);
		$actual = $this->call_private_static_method(
			'match_directory_uri',
			array( '/anything/at/all', $root_directories )
		);
		$this->assertEquals(
			'',
			$actual,
			'dir が "/" や "//" のように untrailingslashit() 後に空文字になる場合は全マッチせず空文字を返すこと（LOW-4）'
		);

		// LOW-5: url 側が末尾スラッシュ付きで渡された場合でも、スラッシュが重複しないこと.
		$trailing_slash_url_directories = array(
			array(
				'dir' => '/wp-content/plugins',
				'url' => 'https://example.com/wp-content/plugins/',
			),
		);
		$actual = $this->call_private_static_method(
			'match_directory_uri',
			array( '/wp-content/plugins/sample-plugin/src', $trailing_slash_url_directories )
		);
		$this->assertEquals(
			'https://example.com/wp-content/plugins/sample-plugin/src/',
			$actual,
			'url 側が末尾スラッシュ付きで渡された場合でもスラッシュが重複しないこと（LOW-5）'
		);
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
