// Font Awesome 自動更新ワークフロー用の changelog 追記スクリプト。
//
// README.md の changelog に未リリースエントリを追記する。
// （プラグイン自体のバージョン番号付与はリリース時に人間が行うため、ここでは付けない）
//
// versions() 内の Font Awesome バージョン定数は gulp copy_fa が自動同期するため、
// このスクリプトでは扱わない（issue #46 対応）。
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
