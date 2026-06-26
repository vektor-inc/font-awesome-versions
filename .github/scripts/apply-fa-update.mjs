// Font Awesome 自動更新ワークフロー用のファイル書き換えスクリプト。
//
// gulp copy_fa でアセットを再生成した後に実行し、以下を更新する:
//   1. src/VkFontAwesomeVersions.php の versions() 内にハードコードされた
//      Font Awesome バージョン定数 ('version' => 'X.Y.Z') を新バージョンへ更新
//   2. README.md の changelog に未リリースエントリを追記
//      （プラグイン自体のバージョン番号付与はリリース時に人間が行うため、ここでは付けない）
//
// 環境変数 OLD_VERSION / NEW_VERSION に更新前後の Font Awesome バージョンを渡すこと。

import fs from 'node:fs';

const oldVersion = process.env.OLD_VERSION;
const newVersion = process.env.NEW_VERSION;

if ( ! oldVersion || ! newVersion ) {
	throw new Error( 'OLD_VERSION と NEW_VERSION の両方を指定してください。' );
}
if ( oldVersion === newVersion ) {
	throw new Error( 'OLD_VERSION と NEW_VERSION が同一です。更新がありません。' );
}

// 正規表現で使うためにバージョン文字列をエスケープする。
const escapeRegExp = ( str ) => str.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

// --- 1. PHP のバージョン定数を更新 ---
const phpPath = 'src/VkFontAwesomeVersions.php';
let php = fs.readFileSync( phpPath, 'utf8' );
// versions() 内の `'version' => '<旧バージョン>'` のみを対象に置換する。
// option default の `'version' => '7_WebFonts_CSS'` 等は旧バージョン文字列に一致しないため影響しない。
const phpPattern = new RegExp(
	`('version'\\s*=>\\s*')${ escapeRegExp( oldVersion ) }(')`,
	'g'
);
const phpMatches = php.match( phpPattern );
if ( ! phpMatches ) {
	throw new Error(
		`${ phpPath } 内に Font Awesome バージョン定数 '${ oldVersion }' が見つかりませんでした。`
	);
}
php = php.replace( phpPattern, `$1${ newVersion }$2` );
fs.writeFileSync( phpPath, php );
console.log(
	`${ phpPath }: バージョン定数を ${ oldVersion } → ${ newVersion } に更新 (${ phpMatches.length } 箇所)`
);

// --- 2. README.md の changelog に未リリースエントリを追記 ---
const readmePath = 'README.md';
let readme = fs.readFileSync( readmePath, 'utf8' );
const entry = `- [ 仕様変更 ] Font Awesome を ${ oldVersion } から ${ newVersion } に更新`;

if ( readme.includes( entry ) ) {
	console.log( `${ readmePath }: 同一の changelog エントリが既に存在するためスキップ` );
} else {
	// changelog は本文末尾の "---" 区切りの下にある。区切り直後へ未リリースエントリとして挿入する。
	const marker = '\n---\n';
	const markerIndex = readme.lastIndexOf( marker );
	if ( markerIndex === -1 ) {
		throw new Error( `${ readmePath } に changelog の区切り "---" が見つかりませんでした。` );
	}
	const insertPos = markerIndex + marker.length;
	readme =
		readme.slice( 0, insertPos ) +
		`\n${ entry }\n` +
		readme.slice( insertPos );
	fs.writeFileSync( readmePath, readme );
	console.log( `${ readmePath }: changelog エントリを追記` );
}
