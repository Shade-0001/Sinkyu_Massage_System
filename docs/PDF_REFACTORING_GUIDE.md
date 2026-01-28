# PDF生成サービス リファクタリング実装ガイド

## 📖 目的

巨大なPDFサービスファイル（2,700-2,900行）を、BasePdfServiceを継承してリファクタリングし、コードの保守性を向上させる。

---

## 🎯 対象ファイル

1. **MassageBenefitPdfService.php**（2,770行）
2. **AcupunctureBenefitPdfService.php**（2,915行）
3. **MedicalAssistanceAcupuncturePdfService.php**（2,888行）
4. **MedicalAssistanceMassagePdfService.php**（2,705行）

**合計：11,278行**

---

## ✅ Phase 1: BasePdfService継承の適用

### ステップ1：クラス宣言の変更

**変更前**：
```php
class MassageBenefitPdfService
{
  protected $coordinates;
  protected $sampleDataMode = false;
  protected $customSampleData = null;
  protected $customCoordinatesPath = null;
  protected $customTemplatePath = null;

  public function __construct()
  {
    $this->loadCoordinates();
  }

  // ... 多数のメソッド
}
```

**変更後**：
```php
class MassageBenefitPdfService extends BasePdfService
{
  // プロパティは全てBasePdfServiceに移動済み

  /**
   * デフォルト座標ファイルパスを取得
   */
  protected function getDefaultCoordinatesPath(): string
  {
    return storage_path('app/config/massage_benefit_coordinates.json');
  }

  /**
   * デフォルト座標を取得
   */
  protected function getDefaultCoordinates(): array
  {
    return [
      'title_year_era' => ['x' => 0, 'y' => 0, 'fontSize' => 10],
      // ... その他の座標
    ];
  }

  // ... 固有メソッドのみ残す
}
```

---

### ステップ2：削除するメソッド（BasePdfServiceに実装済み）

以下のメソッドは**完全に削除**してOK：

#### 座標管理メソッド
```php
// ❌ 削除
public function setSampleDataMode(bool $enabled): void
public function setCustomSampleData(array $data): void
public function setCoordinatesPath(string $path): void
public function setTemplatePath(string $path): void
protected function loadCoordinates(): void
protected function coord(string $key, string $property = 'x')
protected function hasCoord(string $key): bool
```

#### 描画ヘルパーメソッド
```php
// ❌ 削除（単純な実装の場合）
protected function fillBoxesByKey(Fpdi $pdf, string $key, string $text, int $boxCount, float $boxWidth): void
protected function fillBoxes(Fpdi $pdf, float $startX, float $y, string $text, ...): void
protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
protected function drawTextWithSpacing(Fpdi $pdf, float $startX, float $y, string $text, ...): void
protected function drawEllipseByKey(Fpdi $pdf, string $key): void
protected function drawCircleByKey(Fpdi $pdf, string $key): void
protected function drawDebugGrid(Fpdi $pdf): void
```

#### 和暦変換メソッド
```php
// ❌ 削除
protected function convertToJapaneseYear(int $year, int $month): array
protected function convertToJapaneseDate(string $date): string
```

---

### ステップ3：固有ロジックがある場合の対応

#### パターンA：ログ機能などの拡張がある場合

**例：drawTextByKey() にログ機能がある**

```php
// ✅ オーバーライドして拡張
protected function drawTextByKey(Fpdi $pdf, string $key, string $text): void
{
  // デバッグログ追加（固有ロジック）
  if ($this->coord($key, 'x') < 5 && $this->coord($key, 'y') < 5) {
    \Log::warning("座標0,0付近の描画検出", ['key' => $key, 'text' => $text]);
  }

  // 親クラスのメソッドを呼び出し
  parent::drawTextByKey($pdf, $key, $text);
}
```

#### パターンB：完全に異なる実装の場合

```php
// ✅ そのまま残す（親クラスのメソッドを使わない）
protected function fillServiceDates(Fpdi $pdf, $records): void
{
  // 固有の施術日描画ロジック
  // このメソッドはBasePdfServiceにないため、そのまま残す
}
```

---

### ステップ4：座標管理の移行

#### ensureRadioGroupIntegrity() がある場合

**AcupunctureBenefitPdfService、MedicalAssistanceAcupuncturePdfService**

```php
// ❌ 削除（BasePdfServiceに実装済み）
protected function ensureRadioGroupIntegrity(): void
{
  // ...
}
```

BasePdfServiceの`loadCoordinates()`が自動的に呼び出すため、削除してOK。

---

## ✅ Phase 2: 大きいメソッドの分割

### 対象メソッド：fillFormFields()

**現状**：1,000-1,500行の巨大メソッド

**問題点**：
- 可読性が低い
- 変更時の影響範囲が不明確
- テスト困難

### 分割パターン

```php
/**
 * フォームフィールドを埋める（エントリーポイント）
 */
protected function fillFormFields(Fpdi $pdf, array $data, string $submissionDate): void
{
  // 基本情報
  $this->fillBasicInfo($pdf, $data);

  // 保険情報
  $this->fillInsuranceInfo($pdf, $data);

  // 診療所情報
  $this->fillClinicInfo($pdf, $data);

  // 被保険者情報
  $this->fillInsuredInfo($pdf, $data);

  // 施術者情報
  $this->fillTherapistInfo($pdf, $data);

  // 施術日カレンダー
  $this->fillServiceDates($pdf, $data['records']);

  // 施術料金
  $this->fillTreatmentFees($pdf, $data);

  // 署名欄
  $this->fillSignatures($pdf, $submissionDate);

  // 摘要欄
  if (isset($data['abstract'])) {
    $this->fillAbstract($pdf, $data);
  }
}

/**
 * 基本情報を埋める
 */
protected function fillBasicInfo(Fpdi $pdf, array $data): void
{
  // タイトル（年号）
  $japanese = $this->convertToJapaneseYear(
    (int)substr($data['service_year_month'], 0, 4),
    (int)substr($data['service_year_month'], 5, 2)
  );

  $this->drawTextByKey($pdf, 'title_year_era', $japanese['era']);
  $this->drawTextByKey($pdf, 'title_year_number', (string)$japanese['year']);

  // 利用者氏名
  $fullName = $data['clinic_user']['last_name'] . ' ' . $data['clinic_user']['first_name'];
  $this->drawTextByKey($pdf, 'user_name', $fullName);

  // ... その他の基本情報
}

/**
 * 保険情報を埋める
 */
protected function fillInsuranceInfo(Fpdi $pdf, array $data): void
{
  if (!isset($data['insurance'])) {
    return;
  }

  $insurance = $data['insurance'];

  // 保険者番号
  $this->fillBoxesByKey($pdf, 'insurer_number', (string)$insurance['insurer_number'], 8, 4.5);

  // 記号
  $this->fillBoxesByKey($pdf, 'code_number', (string)$insurance['code_number'], 10, 4.5);

  // ... その他の保険情報
}

/**
 * 診療所情報を埋める
 */
protected function fillClinicInfo(Fpdi $pdf, array $data): void
{
  // 診療所名
  $this->drawTextByKey($pdf, 'clinic_name', $data['clinic_info']['name']);

  // 住所
  $address = $data['clinic_info']['prefecture'] . $data['clinic_info']['city'] . $data['clinic_info']['address'];
  $this->drawTextByKey($pdf, 'clinic_address', $address);

  // ... その他の診療所情報
}

/**
 * 被保険者情報を埋める
 */
protected function fillInsuredInfo(Fpdi $pdf, array $data): void
{
  // 被保険者氏名
  $this->drawTextByKey($pdf, 'insured_name', $data['insurance']['insured_name'] ?? '');

  // 生年月日
  if (isset($data['clinic_user']['birthday'])) {
    $japaneseDate = $this->convertToJapaneseDate($data['clinic_user']['birthday']);
    $this->drawTextByKey($pdf, 'birthday', $japaneseDate);
  }

  // ... その他の被保険者情報
}

/**
 * 施術者情報を埋める
 */
protected function fillTherapistInfo(Fpdi $pdf, array $data): void
{
  // 施術者氏名
  $therapistName = $data['therapist']['last_name'] . ' ' . $data['therapist']['first_name'];
  $this->drawTextByKey($pdf, 'therapist_name', $therapistName);

  // 資格番号
  $this->drawTextByKey($pdf, 'license_number', $data['therapist']['license_number'] ?? '');

  // ... その他の施術者情報
}

/**
 * 施術料金を埋める
 */
protected function fillTreatmentFees(Fpdi $pdf, array $data): void
{
  // 施術料
  $this->fillBoxesByKey($pdf, 'treatment_fee', (string)$data['treatment_fee'], 6, 4.5);

  // 往療料
  $this->fillBoxesByKey($pdf, 'housecall_fee', (string)$data['housecall_fee'], 6, 4.5);

  // 合計
  $total = $data['treatment_fee'] + $data['housecall_fee'];
  $this->fillBoxesByKey($pdf, 'total_fee', (string)$total, 6, 4.5);

  // ... その他の料金情報
}

/**
 * 署名欄を埋める
 */
protected function fillSignatures(Fpdi $pdf, string $submissionDate): void
{
  // 提出年月日
  $japaneseDate = $this->convertToJapaneseDate($submissionDate);
  $this->drawTextByKey($pdf, 'submission_date', $japaneseDate);

  // ... その他の署名欄
}

/**
 * 摘要欄を埋める
 */
protected function fillAbstract(Fpdi $pdf, array $data): void
{
  if (empty($data['abstract'])) {
    return;
  }

  $this->drawTextByKey($pdf, 'abstract', $data['abstract']);
}
```

---

## 📝 実装チェックリスト

### ファイルごとのチェックリスト

#### MassageBenefitPdfService.php

- [x] クラス宣言を`extends BasePdfService`に変更
- [ ] getDefaultCoordinatesPath()を実装
- [ ] getDefaultCoordinates()を実装
- [ ] 共通メソッドを削除
  - [ ] setSampleDataMode()
  - [ ] setCustomSampleData()
  - [ ] setCoordinatesPath()
  - [ ] setTemplatePath()
  - [ ] loadCoordinates()
  - [ ] coord()
  - [ ] hasCoord()
  - [ ] fillBoxesByKey() ※固有ロジックがなければ削除
  - [ ] fillBoxes() ※固有ロジックがなければ削除
  - [ ] drawTextByKey() ※固有ロジックがあればオーバーライド
  - [ ] drawTextWithSpacing() ※固有ロジックがなければ削除
  - [ ] drawEllipseByKey() ※固有ロジックがなければ削除
  - [ ] convertToJapaneseYear()
  - [ ] convertToJapaneseDate() ※存在すれば削除
- [ ] fillFormFields()を分割
  - [ ] fillBasicInfo()
  - [ ] fillInsuranceInfo()
  - [ ] fillClinicInfo()
  - [ ] fillInsuredInfo()
  - [ ] fillTherapistInfo()
  - [ ] fillServiceDates()
  - [ ] fillTreatmentFees()
  - [ ] fillSignatures()
  - [ ] fillAbstract()

#### AcupunctureBenefitPdfService.php

- [ ] クラス宣言を`extends BasePdfService`に変更
- [ ] getDefaultCoordinatesPath()を実装
- [ ] getDefaultCoordinates()を実装
- [ ] ensureRadioGroupIntegrity()を削除（BasePdfServiceに実装済み）
- [ ] 共通メソッドを削除（上記と同様）
- [ ] fillFormFields()を分割（上記と同様）

#### MedicalAssistanceAcupuncturePdfService.php

- [ ] 上記と同様の手順

#### MedicalAssistanceMassagePdfService.php

- [ ] 上記と同様の手順

---

## ⚠️ 注意事項

### 1. 固有ロジックの判別

メソッドを削除する前に、必ず内容を確認。

**削除してOKなパターン**：
- BasePdfServiceと完全に同じ実装
- 単純な座標取得のみ

**削除してはいけないパターン**：
- ログ機能が追加されている
- パラメータチェックが追加されている
- 特殊な計算ロジックがある

### 2. テスト方法

各ファイルをリファクタリング後、必ずPDF出力テストを実施。

```bash
# サンプルデータでPDF生成
php artisan tinker
```

```php
$service = new \App\Services\Print\MassageBenefitPdfService();
$service->setSampleDataMode(true);
$pdf = $service->generate([1], '2024-01', '2024-01-31');
file_put_contents('/tmp/test.pdf', $pdf);
```

### 3. バックアップ

リファクタリング前に必ずバックアップを取得。

```bash
cp app/Services/Print/MassageBenefitPdfService.php app/Services/Print/MassageBenefitPdfService.php.backup
```

---

## 📊 期待効果

| ファイル | 変更前 | 変更後（推定） | 削減行数 |
|---------|-------|--------------|---------|
| MassageBenefitPdfService.php | 2,770行 | 約2,300行 | 約470行 |
| AcupunctureBenefitPdfService.php | 2,915行 | 約2,400行 | 約515行 |
| MedicalAssistanceAcupuncturePdfService.php | 2,888行 | 約2,400行 | 約488行 |
| MedicalAssistanceMassagePdfService.php | 2,705行 | 約2,300行 | 約405行 |
| **合計** | **11,278行** | **約9,400行** | **約1,878行（17%削減）** |

### 追加効果（Phase 2実施後）

- **可読性**：1,000-1,500行のメソッドが10-15個の100-200行メソッドに分割
- **保守性**：変更影響範囲が明確化
- **テスト容易性**：小さいメソッドは単体テスト可能

---

## 🚀 実施推奨順序

1. **MassageBenefitPdfService.php**（2,770行）
   - 最初のテストケースとして実施
   - パターンを確立

2. **テスト・検証**
   - PDF出力の正確性を確認
   - 既存機能が壊れていないか確認

3. **他の3ファイルに適用**
   - 確立したパターンを適用
   - 1ファイルずつ実施

4. **最終テスト**
   - 全PDFタイプの出力確認

---

## 📖 参考

- [BasePdfService.php](../app/Services/Print/BasePdfService.php)
- [MassageBenefitPdfService.php](../app/Services/Print/MassageBenefitPdfService.php)（既に一部リファクタリング済み）
