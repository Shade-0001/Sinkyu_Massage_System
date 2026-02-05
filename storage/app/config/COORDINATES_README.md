# PDF座標設定ファイル設計原則

## 概要
このディレクトリには、各PDFタイプの要素配置座標を定義するJSONファイルが格納されている。

## 座標定義の重要な原則

### 1. 楕円描画フィールド（type: "select"）の座標設定

#### ❌ 誤った設定例
```json
{
  "deposit_type_ordinary": {
    "x": 0.5,
    "y": 0,
    "ellipseX": 72,
    "ellipseY": 212,
    "type": "select"
  }
}
```

**問題点:**
- `x`/`y`と`ellipseX`/`ellipseY`の両方が定義されている
- `BasePdfService::coord()`メソッドは、存在しないプロパティに対してデフォルト値`0`を返す
- `drawEllipseByKey()`内の`??`演算子が機能せず、常に`0`が採用される
- 結果：すべての楕円が座標(0, 0)に描画される

#### ✅ 正しい設定例（推奨）
```json
{
  "deposit_type_ordinary": {
    "ellipseX": 72,
    "ellipseY": 212,
    "ellipseWidth": 7,
    "ellipseHeight": 2.5,
    "type": "select"
  }
}
```

#### ✅ 正しい設定例（後方互換）
```json
{
  "patient_gender_male": {
    "x": 84.5,
    "y": 45.5,
    "ellipseWidth": 2.5,
    "ellipseHeight": 2.5,
    "type": "select"
  }
}
```

### 2. 座標プロパティの優先順位

楕円描画時の座標取得優先順位：
1. `ellipseX` / `ellipseY`（存在する場合）
2. `x` / `y`（フォールバック）

### 3. 座標プロパティの使い分け

| フィールドタイプ | 使用する座標プロパティ | 備考 |
|---|---|---|
| text, number | `x`, `y` | テキスト描画位置 |
| select（楕円） | `ellipseX`, `ellipseY` | 楕円中心位置（推奨） |
| select（楕円・旧） | `x`, `y` | 後方互換性のため |
| calendar | `x`, `y` | カレンダー開始位置 |
| postal_code | `firstX`, `firstY`, `lastX`, `lastY` | 郵便番号の前半・後半位置 |

## 過去の不具合事例

### 事例1: 楕円が(0, 0)に描画される問題（2026-02-05）

**症状:**
- `medical_assistance_acupuncture`で複数の楕円が座標(0, 0)に描画
- `deposit_type_*`と`payment_category_*`のみ正常

**原因:**
1. `deposit_type_*`などに`x: 0.5, y: 0`と`ellipseX, ellipseY`が両方定義されていた
2. `coord()`メソッドが存在しないプロパティに対して`0`を返す仕様
3. `$x = $this->coord($key, 'ellipseX') ?? $this->coord($key, 'x')`というコードで、
   `ellipseX`が存在しないフィールドでは`0`が返され、`??`演算子が機能しなかった

**修正:**
1. 座標JSONから`x`と`y`を削除（`ellipseX`/`ellipseY`のみ残す）
2. `drawEllipseByKey()`のロジックを`isset()`による明示的な存在確認に変更

**教訓:**
- 楕円フィールドでは`ellipseX`/`ellipseY`と`x`/`y`を混在させない
- `coord()`メソッドのデフォルト値挙動に注意
- `??`演算子は`null`のみをフォールバック対象とし、`0`は有効な値として扱われる

## 座標調整時の注意事項

1. **新しい楕円フィールドを追加する場合**
   - `ellipseX`, `ellipseY`, `ellipseWidth`, `ellipseHeight`を使用
   - `x`, `y`は定義しない

2. **既存の楕円フィールドを編集する場合**
   - `ellipseX`/`ellipseY`が存在する場合、`x`/`y`は削除
   - `x`/`y`のみが存在する場合はそのまま使用可能（後方互換）

3. **座標調整ツール使用時**
   - `/prints/coordinate-adjuster`で調整
   - 変更は自動保存される
   - 実際のPDFで位置を確認

## 関連ファイル

- `app/Services/Print/BasePdfService.php` - 描画ロジック本体
- `app/Services/Print/Traits/*FormFieldsTrait.php` - 各PDFタイプの描画処理
- `public/js/coordinate-adjuster_*.js` - 座標調整ツールのフロントエンド
