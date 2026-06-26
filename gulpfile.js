const gulp = require( 'gulp' );
const replace = require( 'gulp-replace' );
const fs = require( 'fs' );

/**
 * versions() 内にハードコードされた Font Awesome バージョン定数を、実際に同梱する
 * @fortawesome/fontawesome-free のバージョンへ自動反映する。
 *
 * 手動・自動どちらのビルドでもビルドのたびに同期されるため、手動更新漏れによる
 * 版ズレ（issue #46 / PR #44 で発生）が起きなくなる。
 */
function syncFontAwesomeVersion() {
	const faVersion = require( '@fortawesome/fontawesome-free/package.json' ).version;
	const phpPath = './src/VkFontAwesomeVersions.php';
	const php = fs.readFileSync( phpPath, 'utf8' );

	// versions() メソッドの本体範囲を波括弧の対応で特定し、その中だけを置換対象にする
	// （get_option_default() の 'version' => '7_WebFonts_CSS' などを巻き込まないため）。
	const start = /function\s+versions\s*\(/.exec( php );
	if ( ! start ) {
		throw new Error( `${ phpPath } 内に versions() メソッドが見つかりませんでした。` );
	}
	const bodyOpen = php.indexOf( '{', start.index );
	if ( bodyOpen === -1 ) {
		throw new Error( `${ phpPath } の versions() メソッド本体が見つかりませんでした。` );
	}
	let depth = 0;
	let bodyClose = -1;
	for ( let i = bodyOpen; i < php.length; i++ ) {
		if ( php[ i ] === '{' ) {
			depth++;
		} else if ( php[ i ] === '}' ) {
			depth--;
			if ( depth === 0 ) {
				bodyClose = i;
				break;
			}
		}
	}
	if ( bodyClose === -1 ) {
		throw new Error( `${ phpPath } の versions() メソッドの波括弧が対応していません。` );
	}

	const body = php.slice( bodyOpen, bodyClose );
	// 'version' => '<semver>' の semver 値のみを同梱バージョンへ差し替える。
	const updatedBody = body.replace(
		/('version'\s*=>\s*')\d+\.\d+\.\d+(')/g,
		`$1${ faVersion }$2`
	);
	if ( updatedBody !== body ) {
		fs.writeFileSync(
			phpPath,
			php.slice( 0, bodyOpen ) + updatedBody + php.slice( bodyClose )
		);
		console.log(
			`versions(): Font Awesome バージョン定数を ${ faVersion } に同期しました。`
		);
	}
}

/**
 * Copy Font Awesome
 */
gulp.task( 'copy_fa', function ( done ) {
	gulp.src( [
		'./node_modules/@fortawesome/fontawesome-free/css/all.min.css',
		'./node_modules/@fortawesome/fontawesome-free/css/v4-shims.min.css',
		'./node_modules/@fortawesome/fontawesome-free/css/v4-font-face.min.css',
		'./node_modules/@fortawesome/fontawesome-free/css/v5-font-face.min.css',
	] )
		.pipe(
			replace(
				'*/',
				'*/\n\n/* .editor-styles-wrapper がないと 5.9 のブロックパターン挿入プレビューやタブレットで読み込まれない(2022.2.1現在)ので応急対応 */\n.editor-styles-wrapper{}'
			)
		)
		.pipe( gulp.dest( './src/font-awesome/css/' ) );
	gulp.src( [
		'./node_modules/@fortawesome/fontawesome-free/js/all.min.js',
		'./node_modules/@fortawesome/fontawesome-free/js/v4-shims.min.js',
	] ).pipe( gulp.dest( [ './src/font-awesome/js/' ] ) );
	gulp.src( './node_modules/@fortawesome/fontawesome-free/webfonts/**' ).pipe(
		gulp.dest( './src/font-awesome/webfonts/' )
	);
	syncFontAwesomeVersion();
	done();
} );
