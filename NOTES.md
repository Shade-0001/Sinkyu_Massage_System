# NOTES.md — 注意点まとめ

---

## Vite / CSS

### CSS が突然効かなくなったとき
`public/hot` ファイルが残っていると、`@vite` ディレクティブが Vite dev サーバーへの接続を試み、dev サーバーが起動していない場合は CSS/JS が一切読み込まれない。

```bash
rm public/hot
```

dev サーバーを使うなら `npm run dev`、使わないなら上記コマンドで `hot` を削除してから `npm run build`。

### CSS の編集場所
`resources/css/app.css` のみ編集する。`public/css/app.css` への変更はブラウザに反映されない（Vite がビルド時に上書きするため）。

### CSS 変更後のビルド
dev サーバーが起動していない場合、CSS を変更したら毎回 `npm run build` が必要。

```bash
npm run build
```

---

## Views ディレクトリ構造

### URL パスと views フォルダ階層を一致させる
ルートの URL パスとビューファイルの配置は対応させる。

| ルート URL | views の配置 |
|---|---|
| `/master/*` | `resources/views/master/*` |
| `/submaster/*` | `resources/views/submaster/*` |
| `/records/*` | `resources/views/records/*` |

### ビューを移動したとき
コントローラーの `return view(...)` だけでなく、**Blade ファイル内の `@include` も漏れなく更新**する。どちらか一方だけ直すと実行時エラーになる。

```bash
# 確認コマンド
grep -rn "@include" resources/views/ | grep "移動前のパス"
```

---

## CSS コンポーネント設計

### btn-custom
ボタンには必ずベースクラス `btn-custom` を付与し、色・サイズを別クラスで指定する。

```html
<button class="btn-custom btn-custom-blue btn-custom-lg">ボタン</button>
```

| 種別 | クラス一覧 |
|---|---|
| 色 | `btn-custom-red` / `btn-custom-orange` / `btn-custom-yellow` / `btn-custom-lime` / `btn-custom-green` / `btn-custom-cyan` / `btn-custom-blue` / `btn-custom-purple` / `btn-custom-gray` |
| サイズ | `btn-custom-lg` / `btn-custom-sm` |

`btn-custom` がない状態で色クラスだけ付けても CSS 変数が適用されないため無効。

### btn-custom にオーバーレイ系クラスを使う場合
`btn-custom` は `position: relative; overflow: hidden` を持つ。`::after` 疑似要素でホバーハイライトを実装しているため、**内部に `position: absolute` な子要素を置くと表示が崩れる**場合がある。

### hover-highlight-* の注意
`hover-highlight-*` クラスは `::after` 疑似要素で白オーバーレイを実現している。そのため要素自身に `overflow: hidden` がないと**オーバーレイがカード外にはみ出す**。

```css
/* 必要に応じて追加 */
position: relative;
overflow: hidden;
```

---

## Laravel キャッシュ

### ビュー名を変更・移動した後
コンパイル済みビューキャッシュが古いパスを参照することがある。

```bash
php artisan view:clear
```
