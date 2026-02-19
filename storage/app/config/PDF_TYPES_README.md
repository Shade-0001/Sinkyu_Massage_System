# pdf_types.json 使用ガイド

---

## ⚠️ 必読

新しいPDFタイプ追加前に、このドキュメント全体を必読。
手順を1つでもスキップすると、座標調整画面で不具合が発生する。

---

## 新しいPDFタイプを追加する際の必須チェックリスト

新しいPDFタイプを追加する場合、以下の手順を**すべて**実行すること。
いずれか1つでも欠けると、座標調整画面でフィールドラベルが英語になる、カテゴリが正しく表示されない、などの不具合が発生する。

### 1. pdf_types.json にエントリを追加

```json
"新しいpdf_type_key": {
  "name": "表示名",
  "coordinatesFile": "座標JSONファイル名.json",
  "templateFile": "テンプレートPDFファイル名.pdf",
  "templateDir": "テンプレートディレクトリ名（なければ空文字列）",
  "serviceClass": "PDFサービスクラス名",
  "fieldsFile": "フィールド定義JSファイル名.js"  // 【重要】空文字列にしない！
}
```

**【重要】fieldsFile の設定**
- `fieldsFile` が **空文字列 `""` の場合、座標調整画面でフィールドラベルが英語（キー名）になる**
- 既存のフィールド定義を使い回す場合も、必ず既存のJSファイル名を指定すること
  - 例: `"fieldsFile": "consent_request_letter_sample_acupuncture.js"`
- フィールド定義が不要な場合のみ空文字列にする（座標調整画面を使わない場合）

### 2. 座標JSONファイルを作成

`storage/app/config/` に座標設定JSONファイルを作成。

例: `storage/app/config/consent_request_letter_designated_acupuncture_coordinates.json`

**【重要】初期値は必ず `{}` にすること（`[]` は絶対NG）**

```json
{}
```

- `[]`（JSON配列）で作成すると、座標調整画面で調整してもリロードすると全てリセットされる
- 原因：PHPで `json_decode('[]', true)` → 空配列 → JSに `[]`（JS配列）として渡る → 文字列キーが `JSON.stringify` で消える → 保存リクエストが空になる → `[]` がファイルに書き込まれる（永久ループ）
- 他の座標JSONを参考にする場合も、必ず `{}` から始まっているか確認すること

### 3. フィールド定義ファイルを作成（または既存を使い回し）

`public/js/coordinate-adjuster_fields.js` にフィールド定義オブジェクトを追加。

既存のフィールド定義を使い回す場合は、この手順をスキップ。

### 4. coordinate-adjuster_core.js の getFieldDefinitions() に追加

**【重要】この手順を忘れると、フィールドラベルが英語になる**

`public/js/coordinate-adjuster_core.js` の `getFieldDefinitions()` 関数に新しいPDFタイプのケースを追加：

```javascript
function getFieldDefinitions() {
  // ...既存のコード...

  if (currentPdfType === 'therapy_benefit_massage') {
    return fieldCategoriesTherapyBenefitMassage;
  } else if (currentPdfType === 'treatment_receipt') {
    return fieldCategoriesTreatmentReceipt;
  } else if (currentPdfType === '新しいpdf_type_key') {  // ← ここに追加
    return 適切なフィールド定義オブジェクト;
  }
  // ...
}
```

### 5. coordinate-adjuster_categories.js にカテゴリ定義を追加

**【重要】この手順を忘れると、不適切なカテゴリが表示される**

`public/js/coordinate-adjuster_categories.js` の以下2つの関数に追加：

#### 5-1. getFieldCategories() に追加

```javascript
function getFieldCategories(pdfType) {
  // ...既存のコード...

  if (pdfType === 'therapy_benefit_massage') {
    return fieldCategoriesTherapyBenefitMassage;
  } else if (pdfType === 'treatment_receipt') {
    return fieldCategoriesTreatmentReceipt;
  } else if (pdfType === '新しいpdf_type_key') {  // ← ここに追加
    return 適切なカテゴリ定義オブジェクト;
  }
  // ...
}
```

#### 5-2. getCategoryOrder() に追加

```javascript
function getCategoryOrder(pdfType) {
  // ...既存のコード...

  if (pdfType === 'therapy_benefit_massage') {
    return categoryOrderTherapyBenefitMassage;
  } else if (pdfType === 'treatment_receipt') {
    return categoryOrderTreatmentReceipt;
  } else if (pdfType === '新しいpdf_type_key') {  // ← ここに追加
    return 適切なカテゴリ順序配列;
  }
  // ...
}
```

### 6. PDFサービスクラスを作成

`app/Services/Print/` にPDF生成サービスクラスを作成。

例: `app/Services/Print/ConsentRequestLetterDesignatedAcupuncturePdfService.php`

### 7. DocumentAssociationController.php の fixedDocuments に追加（文面関連付けが必要な場合）

文面関連付け機能を使う場合のみ、`app/Http/Controllers/DocumentAssociationController.php` の `fixedDocuments` 配列に追加。

---

## トラブルシューティング

### フィールドラベルが英語になる

**原因**:
1. `pdf_types.json` の `fieldsFile` が空文字列 `""`
2. `coordinate-adjuster_core.js` の `getFieldDefinitions()` に該当PDFタイプのケースが追加されていない

**解決方法**:
1. `pdf_types.json` の `fieldsFile` に正しいJSファイル名を設定
2. `getFieldDefinitions()` に該当PDFタイプのケースを追加

### 不適切なカテゴリが表示される

**原因**:
1. `coordinate-adjuster_categories.js` の `getFieldCategories()` に該当PDFタイプのケースが追加されていない
2. `coordinate-adjuster_categories.js` の `getCategoryOrder()` に該当PDFタイプのケースが追加されていない

**解決方法**:
1. `getFieldCategories()` に該当PDFタイプのケースを追加
2. `getCategoryOrder()` に該当PDFタイプのケースを追加

### フィールドが表示されない

**原因**:
1. 座標JSONファイルが存在しない
2. フィールド定義とカテゴリ定義が一致していない

**解決方法**:
1. 座標JSONファイルを作成
2. フィールド定義とカテゴリ定義を確認

### 座標を調整してもリロードすると全てリセットされる

**原因**:
座標JSONファイルの初期値が `{}` ではなく `[]`（JSON配列）になっている。

**バグの連鎖メカニズム**:
1. PHPが `json_decode('[]', true)` → 空のPHP配列 `[]`
2. `response()->json(['coordinates' => []])` → JSに `[]`（JS配列）として渡る
3. JSで `coordinates = []`（配列）に `coordinates['field_key'] = {...}` と文字列キーをセット
4. 保存時に `JSON.stringify(coordinates)` → JS配列は数値インデックスしかシリアライズしないため文字列キーが消えて `[]` が送信される
5. PHPで受け取った空配列を `json_encode([])` → `[]` がファイルに書き込まれる（元の状態に戻る）

**解決方法**:
座標JSONファイルの内容を `[]` から `{}` に修正する。

---

## 実装例: 同意書依頼状（医師指定）（はり・きゅう）

### 1. pdf_types.json に追加

```json
"consent_request_letter_designated_acupuncture": {
  "name": "同意書依頼状（医師指定）（はり･きゅう）",
  "coordinatesFile": "consent_request_letter_designated_acupuncture_coordinates.json",
  "templateFile": "汎用文書.pdf",
  "templateDir": "",
  "serviceClass": "ConsentRequestLetterDesignatedAcupuncturePdfService",
  "fieldsFile": "consent_request_letter_sample_acupuncture.js"  // サンプル版と同じフィールド定義を使い回し
}
```

### 2. coordinate-adjuster_core.js に追加

```javascript
// 同意書依頼状（サンプル版・医師指定版）はり・きゅう用
// ※座標とフィールドが同じなので、同じフィールド定義を使い回す
if (currentPdfType === 'consent_request_letter_sample_acupuncture' ||
    currentPdfType === 'consent_request_letter_designated_acupuncture') {
  return fieldDefinitionsConsentRequestLetterSampleAcupuncture;
}
```

### 3. coordinate-adjuster_categories.js に追加

```javascript
// getFieldCategories()
if (pdfType === 'consent_request_letter_sample_acupuncture' ||
    pdfType === 'consent_request_letter_designated_acupuncture') {
  return fieldCategoriesConsentRequestLetterSampleAcupuncture;
}

// getCategoryOrder()
if (pdfType === 'consent_request_letter_sample_acupuncture' ||
    pdfType === 'consent_request_letter_designated_acupuncture') {
  return categoryOrderConsentRequestLetterSampleAcupuncture;
}
```
