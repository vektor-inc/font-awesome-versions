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
// versions() メソッド内の `'version' => '<旧バージョン>'` のみを対象に置換する。
// 置換対象を versions() のブロックに限定し、将来 versions() 外に同じバージョン文字列の
// 設定が増えても巻き込まないようにする。
// indexOf による固定文字列一致は空白の入り方に弱いため、空白を許容する正規表現で
// versions() の開始位置と return 文の位置を検出する。
const versionsStartMatch = /function\s+versions\s*\(/.exec( php );
if ( ! versionsStartMatch ) {
	throw new Error( `${ phpPath } 内に versions() メソッドが見つかりませんでした。` );
}
const versionsStart = versionsStartMatch.index;
const versionsEndRegex = /return\s+\$versions\s*;/g;
versionsEndRegex.lastIndex = versionsStart;
const versionsEndMatch = versionsEndRegex.exec( php );
if ( ! versionsEndMatch ) {
	throw new Error( `${ phpPath } の versions() メソッド内に return $versions; が見つかりませんでした。` );
}
const versionsEnd = versionsEndMatch.index;
const phpPattern = new RegExp(
	`('version'\\s*=>\\s*')${ escapeRegExp( oldVersion ) }(')`,
	'g'
);
const versionsBlock = php.slice( versionsStart, versionsEnd );
const phpMatches = versionsBlock.match( phpPattern );
if ( ! phpMatches ) {
	throw new Error(
		`${ phpPath } の versions() 内に Font Awesome バージョン定数 '${ oldVersion }' が見つかりませんでした。`
	);
}
const updatedBlock = versionsBlock.replace( phpPattern, `$1${ newVersion }$2` );
php = php.slice( 0, versionsStart ) + updatedBlock + php.slice( versionsEnd );
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
	// changelog は「x.y.z」形式のバージョン見出しが並ぶセクション。末尾の "---" 区切りに依存すると
	// 将来 changelog の後ろに別の "---" が増えたとき誤った位置に挿入されるため、最初のバージョン
	// 見出し行を直接アンカーにして、その直前へ未リリースエントリとして挿入する。
	const versionHeading = /^\d+\.\d+(?:\.\d+)?[ \t]*$/m;
	const headingMatch = versionHeading.exec( readme );
	if ( ! headingMatch ) {
		throw new Error( `${ readmePath } に changelog のバージョン見出し（例: 0.7.3）が見つかりませんでした。` );
	}
	const insertPos = headingMatch.index;
	readme =
		readme.slice( 0, insertPos ) +
		`${ entry }\n\n` +
		readme.slice( insertPos );
	fs.writeFileSync( readmePath, readme );
	console.log( `${ readmePath }: changelog エントリを追記` );
}
