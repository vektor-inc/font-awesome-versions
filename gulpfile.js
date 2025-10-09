const gulp = require( 'gulp' );
const replace = require( 'gulp-replace' );
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
	done();
} );
