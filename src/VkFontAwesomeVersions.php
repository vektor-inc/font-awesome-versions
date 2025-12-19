<?php //phpcs:ignore
/**
 * VK_Font_Awesome_Versions
 *
 * @package vektor-inc/font-awesome-versions
 * @license GPL-2.0+
 *
 * @version 0.7.0
 */

namespace VektorInc\VK_Font_Awesome_Versions;

/**
 * VkFontAwesomeVersions
 */
class VkFontAwesomeVersions {

	/**
	 * Provide the default Font Awesome version key used by the plugin.
	 *
	 * The returned value may be modified by the `vk_font_awesome_version_default` filter.
	 *
	 * @return string The default Font Awesome version key (for example, '7_WebFonts_CSS').
	 */
	public static function get_version_default() {
		$default = '7_WebFonts_CSS';
		return apply_filters( 'vk_font_awesome_version_default', $default );
	}

	/**
	 * Provide the default compatibility flags for Font Awesome v4 and v5.
	 *
	 * @return array Associative array with keys 'v4' and 'v5' where each value is `true` if compatibility for that major version is enabled, `false` otherwise.
	 */
	public static function get_compatibilities_default() {
		$default = array(
			'v4' => false,
			'v5' => false,
		);
		return apply_filters( 'vk_font_awesome_compatibilities_default', $default );
	}

	/**
	 * 直接 new VkFontAwesomeVersions() している場所がありえるので fallback.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Initialise
	 *
	 * @return void
	 */
	public static function init() {
		/**
		 * テキストドメイン
		 */
		if ( did_action( 'init' ) ) {
			self::load_text_domain();
		} else {
			add_action( 'init', array( __CLASS__, 'load_text_domain' ) );
		}

		/**
		 * Reason of Using through the after_setup_theme is
		 * to be able to change the action hook point of css load from theme..
		 */
		add_action( 'after_setup_theme', array( __CLASS__, 'load_css_action' ) );

		add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );

		/* admin init だと use_block_editor_for_post が効かない */
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_admin_font_awesome' ) );

		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'load_gutenberg_font_awesome' ) );
		add_action( 'wp_head', array( __CLASS__, 'dynamic_css' ), 3 );
		add_filter( 'body_class', array( __CLASS__, 'add_body_class_fa_version' ) );
	}

	/**
	 * Load plugin text domain files.
	 *
	 * @return void
	 */
	public static function load_text_domain() {
		// We're not using load_plugin_textdomain() or its siblings because figuring out where
		// the library is located (plugin, mu-plugin, theme, custom wp-content paths) is messy.
		$domain = 'font-awesome-versions';
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using WP core filter.
		$locale = apply_filters(
			'plugin_locale',
			( is_admin() && function_exists( 'get_user_locale' ) ) ? get_user_locale() : get_locale(),
			$domain
		);
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$mo_file = $domain . '-' . $locale . '.mo';
		$path    = realpath( __DIR__ . '/languages' );
		if ( $path && file_exists( $path ) ) {
			load_textdomain( $domain, $path . '/' . $mo_file );
		}
	}

	/**
	 * Undocumented function
	 *
	 * @since 0.3.0
	 * @param string $path PHPUnit テスト用.
	 * @return string $uri.
	 */
	public static function get_directory_uri( $path = '' ) {

		$uri = '';

		if ( ! empty( $path ) ) {
			$path = wp_normalize_path( $path );
		} else {
			// このファイルのパス.
			$path = wp_normalize_path( __DIR__ );
		}

		// ファイルのパスの wp-content より前の部分を site_url() に置換する
		// ABSPATH の部分を site_url() に置換したいところだが、ABSPATHは WordPress.com で /wordpress/core/5.9.3/ のような返し方をされて、一般的なサーバーのパスとは異なるので、置換などには使用しない.
		preg_match( '/(.*)(wp-content.*)/', $path, $matches, PREG_OFFSET_CAPTURE );
		if ( ! empty( $matches[2][0] ) ) {
			$uri = site_url( '/' ) . $matches[2][0] . '/';
		}

		return $uri;
	}

	/**
	 * アイコンの class 名だけ保存されている場合も i タグに変換して出力する
	 *
	 * @param string $option : saved value.
	 * @param string $additional_class : i タグに追加する Font Awesome 以外のクラス名.
	 *
	 * @return string $icon_html : icon tag
	 */
	public static function get_icon_tag( $option = '', $additional_class = '' ) {
		if ( empty( $option ) ) {
			return;
		}
		if (
			false !== strpos( $option, '<i' ) &&
			false !== strpos( $option, '</i>' )
		) {
			$icon_html = $option;
			if ( $additional_class ) {
				preg_match( '/(<i class=\")(.*)(\"><\/i>)/', $option, $matches );
				if ( ! empty( $matches[2] ) ) {
					$icon_html = '<i class="' . esc_attr( $matches[2] ) . ' ' . esc_attr( $additional_class ) . '"></i>';
				}
			}
		} else {

			// 4.7 fall back.
			$print_fa = '';
			$print_fa = self::print_fa();

			$class = $print_fa . $option;

			// Font Awesome 以外のクラス名がある場合.
			if ( $additional_class ) {
				$class .= ' ' . $additional_class;
			}

			$icon_html = '<i class="' . esc_attr( $class ) . '"></i>';
		}
		return $icon_html;
	}

	/**
	 * Load Font Awesome Action
	 *
	 * @return void
	 */
	public static function load_css_action() {
		$hook_point = apply_filters( 'vkfa_enqueue_point', 'wp_enqueue_scripts' );
		add_action( $hook_point, array( __CLASS__, 'load_font_awesome' ) );
	}

	/**
	 * Supported Font Awesome versions and asset URLs.
	 *
	 * @return array Versions meta.
	 */
	public static function versions() {

		$font_awesome_directory_uri = self::get_directory_uri() . 'font-awesome/';

		$versions = array(
			'7_WebFonts_CSS' => array(
				'label'                => '7 Web Fonts with CSS',
				'version'              => '7.1.0',
				'type'                 => 'web-fonts-with-css',
				'url_css'              => $font_awesome_directory_uri . 'css/all.min.css',
				'url_js'               => '',
				'url_v4-shims_css'     => $font_awesome_directory_uri . 'css/v4-shims.min.css', // Font Awesome 4.7 用の shims 定義ファイル.
				'url_v4-shims_js'      => '',
				'url_v4-font-face_css' => $font_awesome_directory_uri . 'css/v4-font-face.min.css', // Font Awesome 4.7 用の font-face 定義ファイル.
				'url_v5-font-face_css' => $font_awesome_directory_uri . 'css/v5-font-face.min.css', // Font Awesome 5.0 用の font-face 定義ファイル.

			),
			'7_SVG_JS'       => array(
				'label'                => '7 SVG with JS ( ' . __( 'Not recommended', 'font-awesome-versions' ) . ' )',
				'version'              => '7.1.0',
				'type'                 => 'svg-with-js',
				/* [ Notice ] use editor css*/
				'url_css'              => $font_awesome_directory_uri . 'css/all.min.css',
				'url_js'               => $font_awesome_directory_uri . 'js/all.min.js',
				'url_v4-shims_css'     => $font_awesome_directory_uri . 'css/v4-shims.min.css', // Font Awesome 4.7 用の shims 定義ファイル.
				'url_v4-shims_js'      => $font_awesome_directory_uri . 'js/v4-shims.min.js',  // Font Awesome 4.7 用の shims 定義ファイル.
				'url_v4-font-face_css' => $font_awesome_directory_uri . 'css/v4-font-face.min.css', // Font Awesome 4.7 用の font-face 定義ファイル.
				'url_v5-font-face_css' => $font_awesome_directory_uri . 'css/v5-font-face.min.css', // Font Awesome 5.0 用の font-face 定義ファイル.
			),
		);
		return $versions;
	}

	/**
	 * Compatibility options for older FA class names.
	 *
	 * @return array Compatibility configuration.
	 */
	public static function compatibilities() {
		$compatibilities = array(
			'v4' => array(
				'label' => __( 'Compatibility mode 4', 'font-awesome-versions' ),
				'note'  => __( 'Enables support for Font Awesome 4 class names.', 'font-awesome-versions' ),
			),
			'v5' => array(
				'label' => __( 'Compatibility mode 5', 'font-awesome-versions' ),
				'note'  => __( 'Enables support for Font Awesome 5 class names.', 'font-awesome-versions' ),
			),
		);
		return $compatibilities;
	}


	/**
	 * Normalize and persist the current Font Awesome version and compatibility flags.
	 *
	 * Migrates legacy stored values (4.x, 5.x, 6.x) to the current format (7_WebFonts_CSS or 7_SVG_JS),
	 * enabling the appropriate v4/v5 compatibility flags when required, and updates the persisted options.
	 *
	 * @return string The normalized Font Awesome version identifier (for example, '7_WebFonts_CSS' or '7_SVG_JS').
	 */
	public static function get_option_fa() {

		// 基本の保存値（実際に読み込むアセットのバージョン）
		$stored_version         = get_option( 'vk_font_awesome_version' );
		$stored_compatibilities = get_option( 'vk_font_awesome_compatibilities' );

		$version = $stored_version;
		if ( false === $version || ! is_string( $version ) || '' === $version ) {
			$version = self::get_version_default();
		}

		$compatibilities = $stored_compatibilities;
		if ( false === $compatibilities || ! is_array( $compatibilities ) ) {
			$compatibilities = self::get_compatibilities_default();
		}
		$compatibilities = wp_parse_args( $compatibilities, self::get_compatibilities_default() );

		// 4系は7系へ移行しつつ4系互換モードを有効化
		if ( '4.7' === $version ) {
			$version               = '7_WebFonts_CSS';
			$compatibilities['v4'] = true;
		}

		// 5系は7系へ移行しつつ5系互換モードを有効化
		if ( '5.0_WebFonts_CSS' === $version || '5_WebFonts_CSS' === $version ) {
			$version               = '7_WebFonts_CSS';
			$compatibilities['v5'] = true;
		} elseif ( '5.0_SVG_JS' === $version || '5_SVG_JS' === $version ) {
			$version               = '7_SVG_JS';
			$compatibilities['v5'] = true;
		}

		// ６系は７系へ移行
		if ( '6_WebFonts_CSS' === $version ) {
			$version = '7_WebFonts_CSS';
		} elseif ( '6_SVG_JS' === $version ) {
			$version = '7_SVG_JS';
		}

		$versions = self::versions();
		if ( ! isset( $versions[ $version ] ) ) {
			$version = self::get_version_default();
		}

		// Persist only when normalization changed the stored values to avoid unnecessary DB writes.
		if ( $stored_version !== $version ) {
			update_option( 'vk_font_awesome_version', $version );
		}
		if ( ! is_array( $stored_compatibilities ) || $stored_compatibilities !== $compatibilities ) {
			update_option( 'vk_font_awesome_compatibilities', $compatibilities );
		}

		return $version;
	}

	/**
	 * Return the stored Font Awesome compatibility flags, creating defaults if absent.
	 *
	 * Ensures the option `vk_font_awesome_compatibilities` exists and returns an associative array
	 * with keys for each supported compatibility flag.
	 *
	 * @return array Associative array with keys:
	 *               - `v4`: `true` if v4 compatibility is enabled, `false` otherwise.
	 *               - `v5`: `true` if v5 compatibility is enabled, `false` otherwise.
	 */
	public static function get_option_compatibilities() {
		$compatibilities = get_option( 'vk_font_awesome_compatibilities' );
		if ( false === $compatibilities || ! is_array( $compatibilities ) ) {
			$compatibilities = self::get_compatibilities_default();
			update_option( 'vk_font_awesome_compatibilities', $compatibilities );
		}
		return $compatibilities;
	}

	/**
	 * Get current asset info for the selected FA version.
	 *
	 * @return array Asset info for the active version.
	 */
	public static function current_info() {
		$versions       = self::versions();
		$current_option = self::get_option_fa();
		$current_info   = $versions[ $current_option ];
		return $current_info;
	}

		/**
		 * Render a small HTML snippet showing an example Font Awesome icon and a link to the Font Awesome icon list.
		 *
		 * @param string   $type Either 'class' to output the icon class string or any other value to output an HTML <i> tag example.
		 * @param string[] $example_class_array Associative array of example classes keyed by version (e.g., ['v7' => 'fa-regular fa-file-lines']). If a version key is missing a default example is used.
		 * @return string Sanitized HTML containing the example icon text and a link to the Font Awesome icon list.
		 */
	public static function ex_and_link( $type = '', $example_class_array = array() ) {
		$current_option = self::get_option_fa();

		$version    = '7';
		$link       = 'https://fontawesome.com/search?ic=free-collection';
		$icon_class = 'fa-regular fa-file-lines';
		if ( ! empty( $example_class_array['v7'] ) ) {
			$icon_class = esc_attr( $example_class_array['v7'] );
		}

		if ( '7_WebFonts_CSS' !== $current_option && '7_SVG_JS' !== $current_option ) {
			$version = '';
			$link    = '';
		}

		$ex_and_link  = '<div style="margin-top:5px"><strong>Font Awesome ' . $version . '</strong></div>';
		$ex_and_link .= __( 'Ex ) ', 'font-awesome-versions' );
		if ( 'class' === $type ) {
			$ex_and_link .= $icon_class;
		} else {
			$ex_and_link .= esc_html( '<i class="' . $icon_class . '"></i>' );
		}
		$ex_and_link .= '<br>[ -> <a href="' . $link . '" target="_blank" rel="noreferrer">' . __( 'Font Awesome Icon list', 'font-awesome-versions' ) . '</a> ]';

		return wp_kses_post( $ex_and_link );
	}

	/**
	 * Provide the Font Awesome 4.x class prefix when the selected version is 4.7.
	 *
	 * @return string `'fa '` if the active Font Awesome version is `4.7`, otherwise an empty string.
	 */
	public static function print_fa() {
		$compatibilities = self::get_option_compatibilities();
		if ( ! empty( $compatibilities['v4'] ) ) {
			return 'fa ';
		}
		return '';
	}

	/**
	 * Enqueues the appropriate Font Awesome assets for the current selection.
	 *
	 * Loads either the SVG+JS bundle or the WebFonts/CSS assets and, when enabled,
	 * also enqueues compatibility shims and font-face styles for Font Awesome v4 or v5.
	 */
	public static function load_font_awesome() {
		$current         = self::current_info();
		$compatibilities = self::get_option_compatibilities();
		if ( 'svg-with-js' === $current['type'] ) {
			wp_enqueue_script( 'vk-font-awesome-js', $current['url_js'], array(), $current['version'], false );
			if ( ! empty( $compatibilities['v4'] ) ) {
				wp_enqueue_script( 'vk-font-awesome-v4-shims-js', $current['url_v4-shims_js'], array( 'vk-font-awesome-js' ), $current['version'], false );
			}
			// [ Danger ] This script now causes important errors
			// wp_add_inline_script( 'font-awesome-js', 'FontAwesomeConfig = { searchPseudoElements: true };', 'before' );
		} else {
			wp_enqueue_style( 'vk-font-awesome', $current['url_css'], array(), $current['version'] );
			if ( ! empty( $compatibilities['v4'] ) ) {
				wp_enqueue_style( 'vk-font-awesome-v4-shims', $current['url_v4-shims_css'], array( 'vk-font-awesome' ), $current['version'] );
				wp_enqueue_style( 'vk-font-awesome-v4-font-face', $current['url_v4-font-face_css'], array( 'vk-font-awesome' ), $current['version'] );
			}
			if ( ! empty( $compatibilities['v5'] ) ) {
				wp_enqueue_style( 'vk-font-awesome-v5-font-face', $current['url_v5-font-face_css'], array( 'vk-font-awesome' ), $current['version'] );
			}
		}
	}

	/**
	 * Load Font Awesome for Classic editor only.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public static function load_admin_font_awesome( $post ) {
		$current         = self::current_info();
		$compatibilities = self::get_option_compatibilities();
		// ブロックエディタでこれがあるとコンソールでエラー吐かれるのでclassicエディタのときだけ読み込み.
		if ( ! function_exists( 'use_block_editor_for_post' ) || ! use_block_editor_for_post( $post ) ) {
			add_editor_style( $current['url_css'] );
			if ( ! empty( $compatibilities['v4'] ) ) {
				add_editor_style( $current['url_v4-shims_css'] );
				add_editor_style( $current['url_v4-font-face_css'] );
			}
			if ( ! empty( $compatibilities['v5'] ) ) {
				add_editor_style( $current['url_v5-font-face_css'] );
			}
		}
	}

	/**
	 * Enqueue Font Awesome styles for the WordPress block editor and add compatibility styles when enabled.
	 *
	 * Enqueues the current Font Awesome CSS for the block editor and enqueues additional v4 shims/font-face
	 * and v5 font-face styles if the corresponding compatibility flags are enabled.
	 *
	 * @return void
	 */
	public static function load_gutenberg_font_awesome() {
		$current_info    = self::current_info();
		$compatibilities = self::get_option_compatibilities();
		wp_enqueue_style( 'gutenberg-font-awesome', $current_info['url_css'], array(), $current_info['version'] );
		if ( ! empty( $compatibilities['v4'] ) ) {
			wp_enqueue_style( 'gutenberg-font-awesome-v4-shims', $current_info['url_v4-shims_css'], array( 'gutenberg-font-awesome' ), $current_info['version'] );
			wp_enqueue_style( 'gutenberg-font-awesome-v4-font-face', $current_info['url_v4-font-face_css'], array( 'gutenberg-font-awesome' ), $current_info['version'] );
		}
		if ( ! empty( $compatibilities['v5'] ) ) {
			wp_enqueue_style( 'gutenberg-font-awesome-v5-font-face', $current_info['url_v5-font-face_css'], array( 'gutenberg-font-awesome' ), $current_info['version'] );
		}
	}

	/**
	 * Append a body class indicating the active Font Awesome version.
	 *
	 * @param array $classes Current array of body classes.
	 * @return array The body classes array with `fa_v7_css` added when the selected version is `7_WebFonts_CSS`, `fa_v7_svg` when the selected version is `7_SVG_JS`, or unchanged otherwise.
	 */
	public static function add_body_class_fa_version( $classes ) {
		$current_option = self::get_option_fa();
		if ( '7_WebFonts_CSS' === $current_option ) {
			$classes[] = 'fa_v7_css';
		} elseif ( '7_SVG_JS' === $current_option ) {
			$classes[] = 'fa_v7_svg';
		}

		return $classes;
	}

	/**
	 * Add version-specific inline CSS used for Font Awesome icon rendering.
	 *
	 * Generates a small, sanitized CSS snippet appropriate for the active Font Awesome mode
	 * and attaches it as inline styles to the plugin's enqueue handle.
	 */
	public static function dynamic_css() {
		$current     = self::get_option_fa();
		$dynamic_css = '';
		if ( '7_WebFonts_CSS' === $current ) {
			$dynamic_css = '.tagcloud a:before { font-family: "Font Awesome 7 Free";content: "\f02b";font-weight: bold; }';
		} elseif ( '7_SVG_JS' === $current ) {
			$dynamic_css = '.tagcloud a:before { content:"" }';
		}
		// delete before after space.
		$dynamic_css = trim( $dynamic_css );
		// convert tab and br to space.
		$dynamic_css = preg_replace( '/[\n\r\t]/', '', $dynamic_css );
		// Change multiple spaces to single space.
		$dynamic_css = preg_replace( '/\s(?=\s)/', '', $dynamic_css );

		global $vkfav_set_enqueue_handle_style;
		wp_add_inline_style( $vkfav_set_enqueue_handle_style, $dynamic_css );
	}

	/**
	 * Select the icon class name for the currently active Font Awesome configuration.
	 *
	 * @param string $class_v4 Class name to use for Font Awesome 4.x (e.g., 4.7).
	 * @param string $class_v7 Class name to use for Font Awesome 7.x.
	 * @return string The selected class name, or an empty string when no suitable class was provided.
	 */
	public static function class_switch( $class_v4 = '', $class_v7 = '' ) {
		return $class_v7;
	}


	/**
	 * Register the Font Awesome settings, controls, and section in the WordPress Customizer.
	 *
	 * Adds a "VK Font Awesome" Customizer section, a select control for choosing the Font Awesome
	 * version, and checkbox controls for each compatibility flag (v4 and v5), persisting values
	 * as options.
	 *
	 * @param WP_Customize_Manager $wp_customize The Customizer manager instance.
	 */
	public static function customize_register( $wp_customize ) {

		global $vkfav_customize_panel_prefix;
		global $vkfav_customize_panel_priority;
		if ( ! $vkfav_customize_panel_priority ) {
			$vkfav_customize_panel_priority = 450;
		}

		$versions = self::versions();
		foreach ( $versions as $key => $value ) {
			$choices[ $key ] = $value['label'];
		}

		$compatibilities = self::compatibilities();

		$wp_customize->add_section(
			'VK Font Awesome',
			array(
				'title'    => $vkfav_customize_panel_prefix . __( 'Font Awesome', 'font-awesome-versions' ),
				'priority' => $vkfav_customize_panel_priority,
			)
		);

		$wp_customize->add_setting(
			'vk_font_awesome_version',
			array(
				'default'           => '7_WebFonts_CSS',
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
			$wp_customize->add_control(
				'vk_font_awesome_version',
				array(
					'label'    => __( 'Font Awesome Version', 'font-awesome-versions' ),
					'section'  => 'VK Font Awesome',
					'settings' => 'vk_font_awesome_version',
					'type'     => 'select',
					'priority' => '',
					'choices'  => $choices,
				)
			);

		foreach ( $compatibilities as $key => $value ) {
			$wp_customize->add_setting(
				'vk_font_awesome_compatibilities[' . $key . ']',
				array(
					'default'           => false,
					'type'              => 'option',
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
			$wp_customize->add_control(
				'vk_font_awesome_compatibilities[' . $key . ']',
				array(
					'label'       => $value['label'],
					'section'     => 'VK Font Awesome',
					'settings'    => 'vk_font_awesome_compatibilities[' . $key . ']',
					'description' => $value['note'],
					'type'        => 'checkbox',
					'priority'    => '',
				)
			);
		}
	}
}
