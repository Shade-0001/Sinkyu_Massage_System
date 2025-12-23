<x-app-layout>
<div class="container-fluid mt-4">
  <h4 class="mb-4">PDFレイアウト調整ツール｜{{ $pdfTypeName }}</h4>

  <div class="row">
    <!-- 左側: 設定パネル -->
    <div class="col-md-3">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">フィールド設定</h5>
        </div>
        <div class="card-body p-0" style="max-height: 80vh; overflow-y: auto;">
          <!-- PDFタイプ選択 -->
          <div class="p-3 border-bottom bg-light">
            <label for="pdf-type-select" class="form-label mb-2">PDFタイプ</label>
            <select id="pdf-type-select" class="form-control">
              <option value="acupuncture" {{ $pdfType === 'acupuncture' ? 'selected' : '' }}>はり・きゅう</option>
              <option value="massage" {{ $pdfType === 'massage' ? 'selected' : '' }}>あんま・マッサージ</option>
            </select>
          </div>

          <!-- 利用者選択 -->
          <div class="p-3 border-bottom bg-light">
            <label for="clinic-user-select" class="form-label mb-2">プレビュー利用者</label>
            <select id="clinic-user-select" class="form-control">
              @foreach($clinicUsers as $user)
                <option value="{{ $user->id }}">
                  {{ $user->last_name }} {{ $user->first_name }} ({{ $user->last_kana }} {{ $user->first_kana }})
                </option>
              @endforeach
            </select>
          </div>

          <!-- サンプル表示オプション -->
          <div class="p-3 border-bottom" style="background-color: #f8f9fa;">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="show-sample-data">
              <label class="form-check-label" for="show-sample-data">
                サンプルデータ表示
              </label>
            </div>
          </div>

          <div id="field-settings">
            <!-- JavaScriptで動的に生成 -->
          </div>

          <div class="mt-4">
            <div id="save-status" class="alert alert-success" style="display: none; padding: 8px; margin-bottom: 10px; font-size: 0.9em;">
              自動保存済み
            </div>
            <button id="btn-reset" class="btn btn-secondary btn-block">
              元に戻す
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 右側: PDFプレビュー -->
    <div class="col-md-9">
      <div class="card">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">
            PDFプレビュー
            <span id="preview-loading" class="badge badge-light ml-2" style="display: none;">更新中･･･</span>
            <span id="save-indicator" class="badge badge-success ml-2" style="display: none;">保存中･･･</span>
          </h5>
        </div>
        <div class="card-body">
          <div id="pdf-preview" style="width: 100%; height: 80vh; border: 1px solid #ddd; position: relative;">
            <iframe id="pdf-iframe" style="width: 100%; height: 100%; border: none;"></iframe>
            <div id="preview-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; align-items: center; justify-content: center;">
              <div class="spinner-border text-info" role="status">
                <span class="sr-only"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.field-group {
  margin-bottom: 10px;
  padding: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 5px;
  background-color: #f9f9f9;
}

.field-header {
  margin-bottom: 0;
  padding: 5px;
  color: #333;
  font-weight: bold;
  border-radius: 3px;
  transition: background-color 0.2s;
}

.field-header:hover {
  background-color: #e8e8e8;
}

.toggle-icon {
  display: inline-block;
  width: 15px;
  font-size: 12px;
  transition: transform 0.2s;
}

.field-controls {
  margin-top: 10px;
  padding-left: 20px;
  overflow: hidden;
  transition: max-height 0.3s ease;
  max-height: 0;
}

.field-controls.show {
  max-height: 500px;
}

.coordinate-input {
  margin-bottom: 10px;
}

.coordinate-input label {
  display: inline-block;
  width: 100px;
  font-weight: normal;
  font-size: 0.9em;
}

.coordinate-input input {
  width: 80px;
  display: inline-block;
}

.btn-adjust {
  width: 30px;
  height: 30px;
  padding: 0;
  font-size: 16px;
  line-height: 1;
}

.btn-group-sm {
  margin-top: 5px;
}

.btn-group-sm .btn {
  font-size: 0.85em;
  padding: 4px 8px;
}

.btn-group-sm .btn.active {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
</style>

<script>
let coordinates = {};
let originalCoordinates = {};
let currentPdfType = '{{ $pdfType }}';

// マスタデータ
const masterData = {
  genders: @json($masterData['genders']),
  relationships: @json($masterData['relationships'])
};

// 施術料金データ
const treatmentFees = @json($treatmentFees ?? null);

let customSampleData = {
  // タイトル情報
  title_year_era: '令和',
  title_year_number: '7',
  title_month: '12',
  // 公費・受給者情報
  public_funds_payer_number: '',
  public_funds_recipient_number: '',
  locality_code: '',
  recipient_number: '',
  // 患者情報
  last_name: '田中',
  first_name: '太郎',
  last_kana: 'タナカ',
  first_kana: 'タロウ',
  gender: '男',
  birthdate: '1955-03-15',
  birthday_year: '30',
  birthday_month: '3',
  birthday_day: '15',
  address: '東京都千代田区丸の内1-1-1',
  phone: '03-1234-5678',
  insurer_number: '12345678',
  insurance_symbol: 'ABC123',
  insurance_symbol_kigou: '12345',
  insurance_symbol_bangou: '67890',
  insurance_number: '9876543210',
  relationship: '本人',
  office_name: '株式会社〇〇',
  // 発病または負傷年月日
  onset_date_year: '7',
  onset_date_month: '11',
  onset_date_day: '15',
  onset_illness_name: '腰痛症',
  // 初療・施術期間
  first_treatment_year: '7',
  first_treatment_month: '11',
  first_treatment_day: '20',
  treatment_start_year: '7',
  treatment_start_month: '12',
  treatment_start_day: '1',
  treatment_end_year: '7',
  treatment_end_month: '12',
  treatment_end_day: '31',
  // 医師・同意書情報
  doctor_name: '山田太郎',
  medical_institution: '〇〇病院',
  doctor_address: '東京都新宿区〇〇1-2-3',
  medical_institution_location_type: '1',
  consent_date: '',
  consent_year: '',
  consent_month: '',
  consent_day: '',
  consent_date_year: '7',
  consent_date_month: '11',
  consent_date_day: '25',
  consent_doctor_name: '山田太郎',
  consent_illness_name: '腰痛症',
  therapy_period: '3ヶ月',
  disease: '腰痛症',
  bodypart: '腰部',
  treatment_year_month: '',
  // 代理人情報
  agent_postal_code: '1000001',
  agent_address: '東京都千代田区〇〇2-3-4',
  agent_name: '田中花子',
  // 申請者情報
  applicant_postal_code: '1600022',
  // 支払機関情報
  payment_institution_postal_code: '1000005',
  payment_institution_address: '東京都千代田区〇〇3-4-5',
  payment_institution_name: '〇〇健康保険組合',
  payment_institution_phone: '03-1234-5678',
  payment_method: '振込',
  deposit_type: '普通',
  financial_institution_type: '銀行',
  financial_institution_name: '〇〇銀行',
  branch_type: '支店',
  branch_name: '新宿支店',
  account_number: '1234567',
  account_holder: 'タナカタロウ',
  // 被保険者情報
  temporary_insurer_name: '田中太郎',
  treatment_start_date: '',
  treatment_period: '',
  treatment_days: '15',
  // 施術日カレンダー（1-31日）
  treatment_day_1: '1',
  treatment_day_2: '2',
  treatment_day_3: '3',
  treatment_day_4: '4',
  treatment_day_5: '5',
  treatment_day_6: '6',
  treatment_day_7: '7',
  treatment_day_8: '8',
  treatment_day_9: '9',
  treatment_day_10: '10',
  treatment_day_11: '11',
  treatment_day_12: '12',
  treatment_day_13: '13',
  treatment_day_14: '14',
  treatment_day_15: '15',
  treatment_day_16: '16',
  treatment_day_17: '17',
  treatment_day_18: '18',
  treatment_day_19: '19',
  treatment_day_20: '20',
  treatment_day_21: '21',
  treatment_day_22: '22',
  treatment_day_23: '23',
  treatment_day_24: '24',
  treatment_day_25: '25',
  treatment_day_26: '26',
  treatment_day_27: '27',
  treatment_day_28: '28',
  treatment_day_29: '29',
  treatment_day_30: '30',
  treatment_day_31: '31',
  clinic_postal_code: '1500001',
  clinic_name: '〇〇鍼灸マッサージ院',
  clinic_address: '東京都渋谷区〇〇1-2-3',
  clinic_manager: '田中一郎',
  clinic_phone: '03-9876-5432',
  institution_code: '1234567890',
  therapist_registration_number: '1234567',
  health_center_registration: '施術所所在地',
  clinic_date_year: '7',
  clinic_date_month: '12',
  clinic_date_day: '31',
  submission_date_year: '7',
  submission_date_month: '12',
  submission_date_day: '31',
  outcome: '継続',
  work_scope_type: '業務上',
  birthday_era: '昭和',
  bill_category: '新規',
  claim_number: '2025-0001',
  bank_name: '〇〇銀行',
  branch_name: '△△支店',
  account_type: '普通',
  account_number: '1234567',
  account_holder: 'タナカ イチロウ',
  // 施術料金関連（鍼灸）
  fee_hari_unit: '1500',
  fee_hari_count: '10',
  fee_hari_total: '15000',
  fee_kyu_unit: '1500',
  fee_kyu_count: '5',
  fee_kyu_total: '7500',
  fee_hari_kyu_unit: '2500',
  fee_hari_kyu_count: '0',
  fee_hari_kyu_total: '0',
  fee_electric_unit: '100',
  fee_electric_count: '0',
  fee_electric_total: '0',
  fee_housecall_unit: '250',
  fee_housecall_count: '15',
  fee_housecall_total: '3750',
  fee_housecall_additional_unit: '125',
  fee_housecall_additional_count: '0',
  fee_housecall_additional_total: '0',
  fee_previous_payment_unit: '0',
  fee_previous_payment_count: '0',
  fee_previous_payment_total: '0',
  fee_subtotal: '26250',
  fee_partial_payment: '2625',
  fee_total_claim: '23625',
  // 施術料金関連（マッサージ）
  fee_massage_unit: '2000',
  fee_massage_count: '12',
  fee_massage_total: '24000',
  // 傷病名
  illness_name: '1', // 1:神経痛, 2:リウマチ, 3:頸腕症候群, 4:五十肩, 5:腰痛症, 6:頸椎捻挫後遺症, 7:その他
  illness_name_other_text: '脊柱管狭窄症',
  // 発病負傷の原因･経過
  condition: '自宅の階段で転倒し腰部を打撲。その後徐々に腰痛が悪化。',
  // 摘要
  abstract: '特記事項なし'
};

// サンプルデータフィールドマッピング（座標キーとサンプルデータキーの対応）
// 申請書の上から下への記載順序に合わせて整理
const sampleDataFieldMapping = {
  // === 1. タイトル・機関コード ===
  'title_year_era': { field: 'title_year_era', label: '元号', type: 'select', options: ['令和', '平成', '昭和'], compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'title_year_number': { field: 'title_year_number', label: '年', type: 'number', compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'title_month': { field: 'title_month', label: '月', type: 'number', compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'institution_code': { field: 'institution_code', label: '機関コード', type: 'text' },

  // === 2. 公費・受給者番号 ===
  'public_funds_payer_number': { field: 'public_funds_payer_number', label: '公費負担者番号', type: 'text' },
  'public_funds_recipient_number': { field: 'public_funds_recipient_number', label: '公費受給者番号', type: 'text' },
  'locality_code': { field: 'locality_code', label: '区市町村番号', type: 'text' },
  'recipient_number': { field: 'recipient_number', label: '受給者番号', type: 'text' },

  // === 3. 保険者番号 ===
  'insurer_number': { field: 'insurer_number', label: '保険者番号', type: 'text' },

  // === 4. 被保険者証記号・番号、発病年月日、傷病名 ===
  'insurance_symbol': { field: 'insurance_symbol_kigou', label: '被保険者証等記号番号', type: 'text', combine: ['insurance_symbol_kigou', 'insurance_symbol_bangou'] },
  'insurance_number': { field: 'insurance_number', label: '被保険者番号', type: 'text' },
  'onset_date_year': { field: 'onset_date_year', label: '発病または負傷年月日（年）', type: 'number' },
  'onset_date_month': { field: 'onset_date_month', label: '発病または負傷年月日（月）', type: 'number' },
  'onset_date_day': { field: 'onset_date_day', label: '発病または負傷年月日（日）', type: 'number' },
  'onset_illness_name': { field: 'onset_illness_name', label: '傷病名（発病または負傷年月日の隣）', type: 'text' },

  // === 5. 患者氏名カナ・続柄・氏名 ===
  'patient_last_kana': { field: 'last_kana', label: '氏名カナ（姓）', type: 'text' },
  'patient_first_kana': { field: 'first_kana', label: '氏名カナ（名）', type: 'text' },
  'patient_name_kana': { field: 'last_kana', label: '氏名カナ（姓名）', type: 'text', combine: ['last_kana', 'first_kana'] },
  'patient_relationship': { field: 'relationship', label: '続柄', type: 'select', masterKey: 'relationships', valueField: 'relationship' },
  'patient_last_name': { field: 'last_name', label: '氏名（姓）', type: 'text' },
  'patient_first_name': { field: 'first_name', label: '氏名（名）', type: 'text' },
  'patient_name': { field: 'last_name', label: '氏名（姓名）', type: 'text', combine: ['last_name', 'first_name'] },

  // === 6. 性別・業務上第三者行為・生年月日 ===
  'patient_gender_male': { field: 'gender', label: '性別', type: 'select', masterKey: 'genders', valueField: 'gender', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'gender', optionLabel: '男' },
  'patient_gender_female': { field: 'gender', label: '性別', type: 'select', masterKey: 'genders', valueField: 'gender', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'gender', optionLabel: '女' },
  'work_scope_type_1': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: '業務上' },
  'work_scope_type_2': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: '第三者行為' },
  'work_scope_type_3': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: 'その他' },
  'birthday_era_meiji': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成', '令和'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '明治', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_taisho': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成', '令和'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '大正', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_showa': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成', '令和'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '昭和', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_heisei': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成', '令和'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '平成', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_reiwa': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成', '令和'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '令和', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_year': { field: 'birthday_year', label: '年', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_month': { field: 'birthday_month', label: '月', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_day': { field: 'birthday_day', label: '日', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },

  // === 7. 初療年月日・施術期間 ===
  'treatment_start_date': { field: 'treatment_start_date', label: '初療年月日', type: 'date' },
  'first_treatment_year': { field: 'first_treatment_year', label: '年', type: 'number', compositeGroup: 'first_treatment_date', compositeLabel: '初療年月日' },
  'first_treatment_month': { field: 'first_treatment_month', label: '月', type: 'number', compositeGroup: 'first_treatment_date', compositeLabel: '初療年月日' },
  'first_treatment_day': { field: 'first_treatment_day', label: '日', type: 'number', compositeGroup: 'first_treatment_date', compositeLabel: '初療年月日' },
  'treatment_period': { field: 'treatment_period', label: '施術期間', type: 'text' },
  'treatment_start_year': { field: 'treatment_start_year', label: '年', type: 'number', compositeGroup: 'treatment_start_date', compositeLabel: '施術開始年月日' },
  'treatment_start_month': { field: 'treatment_start_month', label: '月', type: 'number', compositeGroup: 'treatment_start_date', compositeLabel: '施術開始年月日' },
  'treatment_start_day': { field: 'treatment_start_day', label: '日', type: 'number', compositeGroup: 'treatment_start_date', compositeLabel: '施術開始年月日' },
  'treatment_end_year': { field: 'treatment_end_year', label: '年', type: 'number', compositeGroup: 'treatment_end_date', compositeLabel: '施術終了年月日' },
  'treatment_end_month': { field: 'treatment_end_month', label: '月', type: 'number', compositeGroup: 'treatment_end_date', compositeLabel: '施術終了年月日' },
  'treatment_end_day': { field: 'treatment_end_day', label: '日', type: 'number', compositeGroup: 'treatment_end_date', compositeLabel: '施術終了年月日' },

  // === 8. 実日数・請求区分・傷病名・転帰 ===
  'treatment_days': { field: 'treatment_days', label: '実日数', type: 'number' },
  'bill_category_new': { field: 'bill_category', label: '請求区分', type: 'select', options: ['新規', '継続'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'bill_category', optionLabel: '新規' },
  'bill_category_continued': { field: 'bill_category', label: '請求区分', type: 'select', options: ['新規', '継続'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'bill_category', optionLabel: '継続' },
  'illness_name_1': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '神経痛' },
  'illness_name_2': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'リウマチ' },
  'illness_name_3': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸腕症候群' },
  'illness_name_4': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '五十肩' },
  'illness_name_5': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '腰痛症' },
  'illness_name_6': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸椎捻挫後遺症' },
  'illness_name_7': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'その他' },
  'illness_name_other_text': { field: 'illness_name_other_text', label: '傷病名（その他の内容）', type: 'text' },
  'outcome_continued': { field: 'outcome', label: '転帰', type: 'select', options: ['継続', '治癒', '中止', '転医'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'outcome', optionLabel: '継続' },
  'outcome_cured': { field: 'outcome', label: '転帰', type: 'select', options: ['継続', '治癒', '中止', '転医'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'outcome', optionLabel: '治癒' },
  'outcome_discontinued': { field: 'outcome', label: '転帰', type: 'select', options: ['継続', '治癒', '中止', '転医'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'outcome', optionLabel: '中止' },
  'outcome_transferred': { field: 'outcome', label: '転帰', type: 'select', options: ['継続', '治癒', '中止', '転医'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'outcome', optionLabel: '転医' },
  'condition': { field: 'condition', label: '発病負傷の原因･経過', type: 'text' },
  'abstract': { field: 'abstract', label: '摘要', type: 'text', width: 180, lineHeight: 5 },

  // === 9. 施術日カレンダー（1-31日） ===
  'treatment_day_1': { field: 'treatment_day_1', label: '施術日1日', type: 'text' },
  'treatment_day_2': { field: 'treatment_day_2', label: '施術日2日', type: 'text' },
  'treatment_day_3': { field: 'treatment_day_3', label: '施術日3日', type: 'text' },
  'treatment_day_4': { field: 'treatment_day_4', label: '施術日4日', type: 'text' },
  'treatment_day_5': { field: 'treatment_day_5', label: '施術日5日', type: 'text' },
  'treatment_day_6': { field: 'treatment_day_6', label: '施術日6日', type: 'text' },
  'treatment_day_7': { field: 'treatment_day_7', label: '施術日7日', type: 'text' },
  'treatment_day_8': { field: 'treatment_day_8', label: '施術日8日', type: 'text' },
  'treatment_day_9': { field: 'treatment_day_9', label: '施術日9日', type: 'text' },
  'treatment_day_10': { field: 'treatment_day_10', label: '施術日10日', type: 'text' },
  'treatment_day_11': { field: 'treatment_day_11', label: '施術日11日', type: 'text' },
  'treatment_day_12': { field: 'treatment_day_12', label: '施術日12日', type: 'text' },
  'treatment_day_13': { field: 'treatment_day_13', label: '施術日13日', type: 'text' },
  'treatment_day_14': { field: 'treatment_day_14', label: '施術日14日', type: 'text' },
  'treatment_day_15': { field: 'treatment_day_15', label: '施術日15日', type: 'text' },
  'treatment_day_16': { field: 'treatment_day_16', label: '施術日16日', type: 'text' },
  'treatment_day_17': { field: 'treatment_day_17', label: '施術日17日', type: 'text' },
  'treatment_day_18': { field: 'treatment_day_18', label: '施術日18日', type: 'text' },
  'treatment_day_19': { field: 'treatment_day_19', label: '施術日19日', type: 'text' },
  'treatment_day_20': { field: 'treatment_day_20', label: '施術日20日', type: 'text' },
  'treatment_day_21': { field: 'treatment_day_21', label: '施術日21日', type: 'text' },
  'treatment_day_22': { field: 'treatment_day_22', label: '施術日22日', type: 'text' },
  'treatment_day_23': { field: 'treatment_day_23', label: '施術日23日', type: 'text' },
  'treatment_day_24': { field: 'treatment_day_24', label: '施術日24日', type: 'text' },
  'treatment_day_25': { field: 'treatment_day_25', label: '施術日25日', type: 'text' },
  'treatment_day_26': { field: 'treatment_day_26', label: '施術日26日', type: 'text' },
  'treatment_day_27': { field: 'treatment_day_27', label: '施術日27日', type: 'text' },
  'treatment_day_28': { field: 'treatment_day_28', label: '施術日28日', type: 'text' },
  'treatment_day_29': { field: 'treatment_day_29', label: '施術日29日', type: 'text' },
  'treatment_day_30': { field: 'treatment_day_30', label: '施術日30日', type: 'text' },
  'treatment_day_31': { field: 'treatment_day_31', label: '施術日31日', type: 'text' },

  // === 10. 施術料金（鍼灸） ===
  'fee_hari_unit': { field: 'fee_hari_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり料金' },
  'fee_hari_count': { field: 'fee_hari_count', label: '回数', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり料金' },
  'fee_hari_total': { field: 'fee_hari_total', label: '合計', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり料金' },
  'fee_kyu_unit': { field: 'fee_kyu_unit', label: '単価', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう料金' },
  'fee_kyu_count': { field: 'fee_kyu_count', label: '回数', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう料金' },
  'fee_kyu_total': { field: 'fee_kyu_total', label: '合計', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう料金' },
  'fee_hari_kyu_unit': { field: 'fee_hari_kyu_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり・きゅう併用料金' },
  'fee_hari_kyu_count': { field: 'fee_hari_kyu_count', label: '回数', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり・きゅう併用料金' },
  'fee_hari_kyu_total': { field: 'fee_hari_kyu_total', label: '合計', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり・きゅう併用料金' },
  'fee_electric_unit': { field: 'fee_electric_unit', label: '単価', type: 'number', compositeGroup: 'fee_electric', compositeLabel: '電療料' },
  'fee_electric_count': { field: 'fee_electric_count', label: '回数', type: 'number', compositeGroup: 'fee_electric', compositeLabel: '電療料' },
  'fee_electric_total': { field: 'fee_electric_total', label: '合計', type: 'number', compositeGroup: 'fee_electric', compositeLabel: '電療料' },
  'fee_housecall_unit': { field: 'fee_housecall_unit', label: '単価', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料' },
  'fee_housecall_count': { field: 'fee_housecall_count', label: '回数', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料' },
  'fee_housecall_total': { field: 'fee_housecall_total', label: '合計', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料' },
  'fee_housecall_additional_unit': { field: 'fee_housecall_additional_unit', label: '単価', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金' },
  'fee_housecall_additional_count': { field: 'fee_housecall_additional_count', label: '回数', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金' },
  'fee_housecall_additional_total': { field: 'fee_housecall_additional_total', label: '合計', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金' },
  'fee_previous_payment_unit': { field: 'fee_previous_payment_unit', label: '単価', type: 'number', compositeGroup: 'fee_previous_payment', compositeLabel: '施術報告書交付料' },
  'fee_previous_payment_count': { field: 'fee_previous_payment_count', label: '回数', type: 'number', compositeGroup: 'fee_previous_payment', compositeLabel: '施術報告書交付料' },
  'fee_previous_payment_total': { field: 'fee_previous_payment_total', label: '合計', type: 'number', compositeGroup: 'fee_previous_payment', compositeLabel: '施術報告書交付料' },
  'fee_subtotal': { field: 'fee_subtotal', label: '合計', type: 'number' },
  'fee_partial_payment': { field: 'fee_partial_payment', label: '一部負担金', type: 'number' },
  'fee_total_claim': { field: 'fee_total_claim', label: '請求額', type: 'number' },
  
  // === 6. 施術料金（マッサージ） ===
  'fee_massage_unit': { field: 'fee_massage_unit', label: 'マッサージ料金（単価）', type: 'number' },
  'fee_massage_count': { field: 'fee_massage_count', label: 'マッサージ料金（回数）', type: 'number' },
  'fee_massage_total': { field: 'fee_massage_total', label: 'マッサージ料金（合計）', type: 'number' },

  // === 12. 施術所情報 ===
  'clinic_postal_code': { field: 'clinic_postal_code', label: '施術所郵便番号', type: 'text' },
  'clinic_address': { field: 'clinic_address', label: '施術所所在地', type: 'text' },
  'clinic_name': { field: 'clinic_name', label: '施術所名称', type: 'text' },
  'clinic_manager': { field: 'clinic_manager', label: '施術管理者氏名', type: 'text' },
  'clinic_phone': { field: 'clinic_phone', label: '電話番号', type: 'text' },
  'institution_code': { field: 'institution_code', label: '機関コード', type: 'text' },
  'therapist_registration_number': { field: 'therapist_registration_number', label: '施術者登録番号', type: 'text' },
  'health_center_registration_1': { field: 'health_center_registration', label: '保健所登録区分', type: 'select', options: ['施術所所在地', '出張専門施術者住所地'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'health_center_registration', optionLabel: '施術所所在地' },
  'health_center_registration_2': { field: 'health_center_registration', label: '保健所登録区分', type: 'select', options: ['施術所所在地', '出張専門施術者住所地'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'health_center_registration', optionLabel: '出張専門施術者住所地' },
  'clinic_date_year': { field: 'clinic_date_year', label: '年', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },
  'clinic_date_month': { field: 'clinic_date_month', label: '月', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },
  'clinic_date_day': { field: 'clinic_date_day', label: '日', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },
  'submission_date_year': { field: 'submission_date_year', label: '提出年月日（年）', type: 'number' },
  'submission_date_month': { field: 'submission_date_month', label: '提出年月日（月）', type: 'number' },
  'submission_date_day': { field: 'submission_date_day', label: '提出年月日（日）', type: 'number' },

  // === 13. 申請者情報 ===
  'applicant_postal_code': { field: 'applicant_postal_code', label: '申請者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'applicant_address': { field: 'address', label: '申請者住所', type: 'text' },
  'applicant_name': { field: 'last_name', label: '申請者氏名', type: 'text', combine: ['last_name', 'first_name'] },
  'patient_address': { field: 'address', label: '住所', type: 'text' },
  'patient_phone': { field: 'phone', label: '電話番号', type: 'text' },
  'office_name': { field: 'office_name', label: '事業所名称', type: 'text' },

  // === 14. 医師情報・同意書 ===
  'consent_date': { field: 'consent_date', label: '同意年月日', type: 'date' },
  'consent_year': { field: 'consent_year', label: '同意年', type: 'number' },
  'consent_month': { field: 'consent_month', label: '同意月', type: 'number' },
  'consent_day': { field: 'consent_day', label: '同意日', type: 'number' },
  'consent_date_year': { field: 'consent_date_year', label: '年', type: 'number', compositeGroup: 'consent_date', compositeLabel: '同意年月日' },
  'consent_date_month': { field: 'consent_date_month', label: '月', type: 'number', compositeGroup: 'consent_date', compositeLabel: '同意年月日' },
  'consent_date_day': { field: 'consent_date_day', label: '日', type: 'number', compositeGroup: 'consent_date', compositeLabel: '同意年月日' },
  'consent_doctor_name': { field: 'consent_doctor_name', label: '同意書医師氏名', type: 'text' },
  'consent_illness_name': { field: 'consent_illness_name', label: '同意書傷病名', type: 'text' },
  'therapy_period': { field: 'therapy_period', label: '要加療期間', type: 'text' },
  'doctor_address': { field: 'doctor_address', label: '医師所在地', type: 'text' },
  'medical_institution': { field: 'medical_institution', label: '医療機関名', type: 'text' },
  'doctor_name': { field: 'doctor_name', label: '医師氏名', type: 'text' },
  'medical_institution_location_type_1': { field: 'medical_institution_location_type', label: '医療機関所在地区分', type: 'select', options: ['1', '2'], optionLabels: ['区郡市府県庁所在地', '出張所等指定都市所在地域'] },
  'medical_institution_location_type_2': { field: 'medical_institution_location_type', label: '医療機関所在地区分', type: 'select', options: ['1', '2'], optionLabels: ['区郡市府県庁所在地', '出張所等指定都市所在地域'] },

  // === 15. 振込口座情報 ===
  'bank_name': { field: 'bank_name', label: '銀行名', type: 'text' },
  'branch_name': { field: 'branch_name', label: '支店名', type: 'text' },
  'account_type': { field: 'account_type', label: '口座種別', type: 'select', options: ['普通', '当座'] },
  'account_number': { field: 'account_number', label: '口座番号', type: 'text' },
  'account_holder': { field: 'account_holder', label: '口座名義', type: 'text' },

  // === 16. 代理人情報 ===
  'agent_postal_code': { field: 'agent_postal_code', label: '代理人郵便番号', type: 'text' },
  'agent_address': { field: 'agent_address', label: '代理人住所', type: 'text' },
  'agent_name': { field: 'agent_name', label: '代理人氏名', type: 'text' },

  // === 17. 支払機関欄 ===
  'payment_method_transfer': { field: 'payment_method', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当座払'], optionLabels: ['振込', '銀行送金', '郵便局送金', '当座払'] },
  'payment_method_bank': { field: 'payment_method', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当座払'], optionLabels: ['振込', '銀行送金', '郵便局送金', '当座払'] },
  'payment_method_post': { field: 'payment_method', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当座払'], optionLabels: ['振込', '銀行送金', '郵便局送金', '当座払'] },
  'payment_method_checking': { field: 'payment_method', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当座払'], optionLabels: ['振込', '銀行送金', '郵便局送金', '当座払'] },
  'deposit_type_normal': { field: 'deposit_type', label: '預金の種類', type: 'select', options: ['普通', '当座', '通知'], optionLabels: ['普通', '当座', '通知'] },
  'deposit_type_checking': { field: 'deposit_type', label: '預金の種類', type: 'select', options: ['普通', '当座', '通知'], optionLabels: ['普通', '当座', '通知'] },
  'deposit_type_notice': { field: 'deposit_type', label: '預金の種類', type: 'select', options: ['普通', '当座', '通知'], optionLabels: ['普通', '当座', '通知'] },
  'financial_institution_type_bank': { field: 'financial_institution_type', label: '金融機関種類', type: 'select', options: ['銀行', '金庫', '農協'], optionLabels: ['銀行', '金庫', '農協'] },
  'financial_institution_type_credit': { field: 'financial_institution_type', label: '金融機関種類', type: 'select', options: ['銀行', '金庫', '農協'], optionLabels: ['銀行', '金庫', '農協'] },
  'financial_institution_type_coop': { field: 'financial_institution_type', label: '金融機関種類', type: 'select', options: ['銀行', '金庫', '農協'], optionLabels: ['銀行', '金庫', '農協'] },
  'financial_institution_name': { field: 'financial_institution_name', label: '金融機関名', type: 'text' },
  'branch_type_head': { field: 'branch_type', label: '本店支店出張所', type: 'select', options: ['本店', '支店', '出張所'], optionLabels: ['本店', '支店', '出張所'] },
  'branch_type_branch': { field: 'branch_type', label: '本店支店出張所', type: 'select', options: ['本店', '支店', '出張所'], optionLabels: ['本店', '支店', '出張所'] },
  'branch_type_office': { field: 'branch_type', label: '本店支店出張所', type: 'select', options: ['本店', '支店', '出張所'], optionLabels: ['本店', '支店', '出張所'] },
  'branch_name': { field: 'branch_name', label: '支店名', type: 'text' },
  'account_number': { field: 'account_number', label: '口座番号', type: 'text' },
  'account_holder': { field: 'account_holder', label: '口座名義', type: 'text' },

  // === 18. 被保険者情報 ===
  'temporary_insurer_name': { field: 'temporary_insurer_name', label: '被保険者氏名', type: 'text' },

  // === 19. その他 ===
  'claim_number': { field: 'claim_number', label: '請求書番号', type: 'text' },
  'treatment_year_month': { field: 'treatment_year_month', label: '施術年月', type: 'text' }
};

// 初期化
document.addEventListener('DOMContentLoaded', function() {
  loadCoordinates();
  loadCustomSampleData();
  displayTreatmentFees();

  // イベントリスナー
  document.getElementById('btn-reset').addEventListener('click', resetCoordinates);
  document.getElementById('clinic-user-select').addEventListener('change', function() {
    previewPdf();
  });
  document.getElementById('pdf-type-select').addEventListener('change', function() {
    const newPdfType = this.value;
    // ページをリロードして新しいPDFタイプを適用
    window.location.href = '/prints/coordinate-adjuster?pdf_type=' + newPdfType;
  });
  document.getElementById('show-sample-data').addEventListener('change', function() {
    const isChecked = this.checked;
    // チェックを外してもラジオグループはリセットせず、プレビュー利用者の実データから判定する
    renderFieldSettings(); // サンプルデータ入力欄の表示/非表示を更新
    previewPdf();
  });
});

// ラジオグループをデフォルト状態にリセット
function resetRadioGroupsToDefault() {
  // 各radioGroupの最初のフィールドをisSelected: trueに設定
  const processedGroups = new Set();
  
  Object.keys(coordinates).forEach(key => {
    const field = coordinates[key];
    if (field.radioGroup && !processedGroups.has(field.radioGroup)) {
      processedGroups.add(field.radioGroup);
      
      // グループ内のフィールドをリセット
      Object.keys(coordinates).forEach(k => {
        const f = coordinates[k];
        if (f.radioGroup === field.radioGroup) {
          f.isSelected = false;
        }
      });
      
      // 最初のフィールドをisSelected: trueに
      coordinates[key].isSelected = true;
    }
  });
}

// 座標読み込み
function loadCoordinates() {
  fetch('/prints/get-coordinates?pdf_type=' + currentPdfType)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        coordinates = data.coordinates;

        // フィールド定義のデフォルト値をマージ
        Object.keys(sampleDataFieldMapping).forEach(key => {
          if (coordinates[key]) {
            const definition = sampleDataFieldMapping[key];
            // ellipseWidth, ellipseHeight, circleRadius, lineWidth などのデフォルト値を設定
            if (definition.ellipseWidth !== undefined && coordinates[key].ellipseWidth === undefined) {
              coordinates[key].ellipseWidth = definition.ellipseWidth;
            }
            if (definition.ellipseHeight !== undefined && coordinates[key].ellipseHeight === undefined) {
              coordinates[key].ellipseHeight = definition.ellipseHeight;
            }
            if (definition.circleRadius !== undefined && coordinates[key].circleRadius === undefined) {
              coordinates[key].circleRadius = definition.circleRadius;
            }
            if (definition.lineWidth !== undefined && coordinates[key].lineWidth === undefined) {
              coordinates[key].lineWidth = definition.lineWidth;
            }
            if (definition.width !== undefined && coordinates[key].width === undefined) {
              coordinates[key].width = definition.width;
            }
            if (definition.lineHeight !== undefined && coordinates[key].lineHeight === undefined) {
              coordinates[key].lineHeight = definition.lineHeight;
            }
            // radioGroup, optionLabel, label, type などのメタデータをマージ
            if (definition.radioGroup !== undefined) {
              coordinates[key].radioGroup = definition.radioGroup;
            }
            if (definition.optionLabel !== undefined) {
              coordinates[key].optionLabel = definition.optionLabel;
            }
            if (definition.label !== undefined) {
              coordinates[key].label = definition.label;
            }
            if (definition.type !== undefined) {
              coordinates[key].type = definition.type;
            }
            if (definition.postalCodeGap !== undefined && coordinates[key].postalCodeGap === undefined) {
              coordinates[key].postalCodeGap = definition.postalCodeGap;
            }
            // compositeGroup, compositeLabel のマージ
            if (definition.compositeGroup !== undefined) {
              coordinates[key].compositeGroup = definition.compositeGroup;
            }
            if (definition.compositeLabel !== undefined) {
              coordinates[key].compositeLabel = definition.compositeLabel;
            }
          }
        });

        originalCoordinates = JSON.parse(JSON.stringify(coordinates));
        renderFieldSettings();
        // 初回プレビュー表示
        previewPdf();
      }
    })
    .catch(error => {
      console.error('座標読み込みエラー:', error);
      alert('座標設定の読み込みに失敗しました');
    });
}

// フィールド設定UI生成
function renderFieldSettings() {
  const container = document.getElementById('field-settings');
  container.innerHTML = '';

  // radioGroupとcompositeGroupでグループ化されたフィールドを追跡
  const processedGroups = new Set();
  const processedCompositeGroups = new Set();
  const processedKeys = new Set();

  // sampleDataFieldMappingの順序でフィールドを処理
  const orderedKeys = Object.keys(sampleDataFieldMapping).filter(key => coordinates[key]);
  
  // coordinatesに存在するが、sampleDataFieldMappingにないキーも追加
  Object.keys(coordinates).forEach(key => {
    if (!orderedKeys.includes(key)) {
      orderedKeys.push(key);
    }
  });

  orderedKeys.forEach(key => {
    if (processedKeys.has(key)) return;
    
    const field = coordinates[key];
    if (!field) return;
    
    // compositeGroupが定義されている場合、グループの最初のフィールドでセレクトボックスを表示
    if (field.compositeGroup && !processedCompositeGroups.has(field.compositeGroup)) {
      processedCompositeGroups.add(field.compositeGroup);
      
      // グループ内のすべてのフィールドを取得
      const groupFields = Object.entries(coordinates)
        .filter(([k, v]) => v.compositeGroup === field.compositeGroup)
        .sort((a, b) => {
          const indexA = orderedKeys.indexOf(a[0]);
          const indexB = orderedKeys.indexOf(b[0]);
          return indexA - indexB;
        });

      // グループ内のすべてのキーを処理済みとしてマーク
      groupFields.forEach(([k]) => processedKeys.add(k));

      // グループの最初のフィールドを基準にセレクトボックスを作成
      const firstKey = groupFields[0][0];
      
      // 現在選択されているフィールドを判定（デフォルトは最初のフィールド）
      let selectedKey = firstKey;

      const div = document.createElement('div');
      div.className = 'field-group';
      div.setAttribute('data-composite-group', field.compositeGroup);

      // グループラベルを取得
      const groupLabel = field.compositeLabel || field.compositeGroup;

      // オプションを生成
      const options = groupFields.map(([k, v]) => {
        const mapping = sampleDataFieldMapping[k];
        // radioGroupの場合はoptionLabelを優先、それ以外はlabelを使用
        let optionLabel = v.label;
        if (mapping) {
          if (mapping.optionLabel) {
            optionLabel = mapping.optionLabel;
          } else if (mapping.label) {
            optionLabel = mapping.label;
          }
        }
        return `<option value="${k}" ${selectedKey === k ? 'selected' : ''}>${optionLabel}</option>`;
      }).join('');

      div.innerHTML = `
        <h6 class="field-header" onclick="toggleField('${field.compositeGroup}')" style="cursor: pointer; user-select: none;">
          <span class="toggle-icon" id="toggle-${field.compositeGroup}">▶</span> ${groupLabel}
        </h6>

        <div class="field-controls" id="controls-${field.compositeGroup}">
          <div class="coordinate-input">
            <label>要素選択:</label>
            <select onchange="updateCompositeGroupSelection('${field.compositeGroup}', this.value)"
                    class="form-control form-control-sm"
                    style="width: auto; display: inline-block; margin-left: 10px;">
              ${options}
            </select>
          </div>

          <div id="compositegroup-fields-${field.compositeGroup}">
            <!-- 選択された要素の詳細設定をここに表示 -->
          </div>
        </div>
      `;

      container.appendChild(div);
      
      // 選択された要素の詳細設定を表示
      updateCompositeGroupSelection(field.compositeGroup, selectedKey);
      return;
    }
    
    // compositeGroupが定義されている場合はスキップ（既に処理済み）
    if (field.compositeGroup) {
      return;
    }
    
    // radioGroupが定義されている場合、グループの最初のフィールドでセレクトボックスを表示
    if (field.radioGroup && !processedGroups.has(field.radioGroup)) {
      processedGroups.add(field.radioGroup);
      
      // グループ内のすべてのフィールドを取得
      const groupFields = Object.entries(coordinates)
        .filter(([k, v]) => v.radioGroup === field.radioGroup)
        .sort((a, b) => {
          // sampleDataFieldMappingの順序でソート
          const indexA = orderedKeys.indexOf(a[0]);
          const indexB = orderedKeys.indexOf(b[0]);
          if (indexA !== -1 && indexB !== -1) return indexA - indexB;
          
          // フォールバック: キーの番号順でソート
          const numA = parseInt(a[0].match(/\d+$/)?.[0] || 0);
          const numB = parseInt(b[0].match(/\d+$/)?.[0] || 0);
          return numA - numB;
        });

      // グループ内のすべてのキーを処理済みとしてマーク
      groupFields.forEach(([k]) => processedKeys.add(k));

      // グループの最初のフィールドを基準にセレクトボックスを作成
      const firstField = groupFields[0][1];
      const firstKey = groupFields[0][0];

      // 現在選択されているオプションを判定
      let selectedKey = firstKey;
      for (const [k, v] of groupFields) {
        if (v.isSelected) {
          selectedKey = k;
          break;
        }
      }

      const div = document.createElement('div');
      div.className = 'field-group';
      div.setAttribute('data-radio-group', field.radioGroup);

      // グループラベルを取得
      const groupLabel = field.label || field.radioGroup;

      // オプションを生成
      const options = groupFields.map(([k, v]) => {
        return `<option value="${k}" ${selectedKey === k ? 'selected' : ''}>${v.optionLabel || v.label}</option>`;
      }).join('');

      div.innerHTML = `
        <h6 class="field-header" onclick="toggleField('${field.radioGroup}')" style="cursor: pointer; user-select: none;">
          <span class="toggle-icon" id="toggle-${field.radioGroup}">▶</span> ${groupLabel}
        </h6>

        <div class="field-controls" id="controls-${field.radioGroup}">
          <div class="coordinate-input">
            <label>選択:</label>
            <select onchange="updateRadioGroupSelection('${field.radioGroup}', this.value)"
                    class="form-control form-control-sm"
                    style="width: auto; display: inline-block; margin-left: 10px;">
              ${options}
            </select>
          </div>

          <div id="radiogroup-fields-${field.radioGroup}">
            <!-- 選択されたオプションの詳細設定をここに表示 -->
          </div>
        </div>
      `;

      container.appendChild(div);
      
      // 選択されたオプションの詳細設定を表示
      updateRadioGroupSelection(field.radioGroup, selectedKey);
      return;
    }
    
    // radioGroupが定義されている場合はスキップ（既に処理済み）
    if (field.radioGroup) {
      return;
    }
    
    // 処理済みとしてマーク
    processedKeys.add(key);

    const div = document.createElement('div');
    div.className = 'field-group';

    div.innerHTML = `
      <h6 class="field-header" onclick="toggleField('${key}')" style="cursor: pointer; user-select: none;">
        <span class="toggle-icon" id="toggle-${key}">▶</span> ${field.label || key}
      </h6>

      <div class="field-controls" id="controls-${key}">
        <div class="coordinate-input">
          <label>X座標:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'x', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'x', -0.5)"
                  ontouchend="stopLongPress()">←</button>
          <input type="number" step="0.5" value="${field.x}"
                 onchange="updateCoordinate('${key}', 'x', this.value)"
                 class="form-control form-control-sm" data-property="x">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'x', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'x', 0.5)"
                  ontouchend="stopLongPress()">→</button>
        </div>

        <div class="coordinate-input">
          <label>Y座標:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'y', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'y', -0.5)"
                  ontouchend="stopLongPress()">↑</button>
          <input type="number" step="0.5" value="${field.y}"
                 onchange="updateCoordinate('${key}', 'y', this.value)"
                 class="form-control form-control-sm" data-property="y">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'y', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'y', 0.5)"
                  ontouchend="stopLongPress()">↓</button>
        </div>

        <div class="coordinate-input">
          <label>フォントサイズ:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'fontSize', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'fontSize', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.fontSize}"
                 onchange="updateCoordinate('${key}', 'fontSize', this.value)"
                 class="form-control form-control-sm" data-property="fontSize">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'fontSize', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'fontSize', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>

        <div class="coordinate-input">
          <label>文字間隔:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'letterSpacing', -0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'letterSpacing', -0.1)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.1" value="${field.letterSpacing || 0}"
                 onchange="updateCoordinate('${key}', 'letterSpacing', this.value)"
                 class="form-control form-control-sm" data-property="letterSpacing">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'letterSpacing', 0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'letterSpacing', 0.1)"
                  ontouchend="stopLongPress()">+</button>
        </div>

        ${field.width !== undefined ? `
        <div class="coordinate-input">
          <label>折り返し幅:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'width', -5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'width', -5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="5" value="${field.width || 180}"
                 onchange="updateCoordinate('${key}', 'width', this.value)"
                 class="form-control form-control-sm" data-property="width">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'width', 5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'width', 5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.lineHeight !== undefined ? `
        <div class="coordinate-input">
          <label>行間:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'lineHeight', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'lineHeight', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.lineHeight || 5}"
                 onchange="updateCoordinate('${key}', 'lineHeight', this.value)"
                 class="form-control form-control-sm" data-property="lineHeight">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'lineHeight', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'lineHeight', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        <div class="coordinate-input">
          <label>テキスト配置:</label>
          <div class="btn-group btn-group-sm d-flex" role="group">
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'left' ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'left')"
                    title="左揃え">左</button>
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'center' || !field.textAlign ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'center')"
                    title="中央揃え">中央</button>
            <button type="button" class="btn btn-outline-secondary flex-fill ${field.textAlign === 'right' ? 'active' : ''}"
                    onclick="updateCoordinate('${key}', 'textAlign', 'right')"
                    title="右揃え">右</button>
          </div>
        </div>

        ${field.ellipseWidth !== undefined ? `
        <div class="coordinate-input">
          <label>楕円幅:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseWidth', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseWidth', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.ellipseWidth || 8}"
                 onchange="updateCoordinate('${key}', 'ellipseWidth', this.value)"
                 class="form-control form-control-sm" data-property="ellipseWidth">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseWidth', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseWidth', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.ellipseHeight !== undefined ? `
        <div class="coordinate-input">
          <label>楕円高さ:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseHeight', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseHeight', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.ellipseHeight || 5}"
                 onchange="updateCoordinate('${key}', 'ellipseHeight', this.value)"
                 class="form-control form-control-sm" data-property="ellipseHeight">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'ellipseHeight', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'ellipseHeight', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.circleRadius !== undefined && field.ellipseWidth === undefined && field.ellipseHeight === undefined ? `
        <div class="coordinate-input">
          <label>○半径:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'circleRadius', -0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'circleRadius', -0.1)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.1" value="${field.circleRadius || 1.2}"
                 onchange="updateCoordinate('${key}', 'circleRadius', this.value)"
                 class="form-control form-control-sm" data-property="circleRadius">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'circleRadius', 0.1)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'circleRadius', 0.1)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.doubleCircleInnerRadius !== undefined ? `
        <div class="coordinate-input">
          <label>◎内円半径:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', -0.05)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', -0.05)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.05" value="${field.doubleCircleInnerRadius || 0.4}"
                 onchange="updateCoordinate('${key}', 'doubleCircleInnerRadius', this.value)"
                 class="form-control form-control-sm" data-property="doubleCircleInnerRadius">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', 0.05)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', 0.05)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${field.type === 'postal_code' ? `
        <div class="coordinate-input">
          <label>郵便番号間隔:</label>
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'postalCodeGap', -0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'postalCodeGap', -0.5)"
                  ontouchend="stopLongPress()">−</button>
          <input type="number" step="0.5" value="${field.postalCodeGap || 2}"
                 onchange="updateCoordinate('${key}', 'postalCodeGap', this.value)"
                 class="form-control form-control-sm" data-property="postalCodeGap">
          <button class="btn btn-sm btn-outline-secondary btn-adjust"
                  onmousedown="startLongPress('${key}', 'postalCodeGap', 0.5)"
                  onmouseup="stopLongPress()"
                  onmouseleave="stopLongPress()"
                  ontouchstart="startLongPress('${key}', 'postalCodeGap', 0.5)"
                  ontouchend="stopLongPress()">+</button>
        </div>
        ` : ''}

        ${getSampleDataInput(key)}
      </div>
    `;

    container.appendChild(div);
  });
}

// compositeGroupの選択を更新
function updateCompositeGroupSelection(groupName, selectedKey) {
  // radioGroupの場合、isSelectedを更新
  const selectedField = coordinates[selectedKey];
  if (selectedField && selectedField.radioGroup) {
    Object.keys(coordinates).forEach(key => {
      const field = coordinates[key];
      if (field.radioGroup === selectedField.radioGroup) {
        field.isSelected = (key === selectedKey);
      }
    });
  }

  const fieldsContainer = document.getElementById(`compositegroup-fields-${groupName}`);
  if (!fieldsContainer) return;

  fieldsContainer.innerHTML = '';

  if (!selectedField) return;

  const detailsDiv = document.createElement('div');
  detailsDiv.style.borderTop = '1px solid #ddd';
  detailsDiv.style.marginTop = '10px';
  detailsDiv.style.paddingTop = '10px';

  // 選択されたフィールドのマッピング情報を取得
  const mapping = sampleDataFieldMapping[selectedKey];
  const fieldLabel = mapping ? (mapping.optionLabel || mapping.label) : selectedField.label;

  // フィールド名表示
  const labelDiv = document.createElement('div');
  labelDiv.style.marginBottom = '10px';
  labelDiv.innerHTML = `<strong>調整対象: ${fieldLabel}</strong>`;
  detailsDiv.appendChild(labelDiv);

  // X座標
  const xDiv = document.createElement('div');
  xDiv.className = 'coordinate-input';
  xDiv.innerHTML = `<label>X座標:</label>`;
  
  const xBtnLeft = document.createElement('button');
  xBtnLeft.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnLeft.innerHTML = '←';
  xBtnLeft.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', -0.5));
  xBtnLeft.addEventListener('mouseup', stopLongPress);
  xBtnLeft.addEventListener('mouseleave', stopLongPress);
  xBtnLeft.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', -0.5));
  xBtnLeft.addEventListener('touchend', stopLongPress);
  
  const xInput = document.createElement('input');
  xInput.type = 'number';
  xInput.step = '0.5';
  xInput.value = selectedField.x;
  xInput.className = 'form-control form-control-sm';
  xInput.style.width = '80px';
  xInput.style.display = 'inline-block';
  xInput.style.marginLeft = '5px';
  xInput.style.marginRight = '5px';
  xInput.setAttribute('data-property', 'x');
  xInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'x', this.value);
  });
  
  const xBtnRight = document.createElement('button');
  xBtnRight.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnRight.innerHTML = '→';
  xBtnRight.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('mouseup', stopLongPress);
  xBtnRight.addEventListener('mouseleave', stopLongPress);
  xBtnRight.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('touchend', stopLongPress);
  
  xDiv.appendChild(xBtnLeft);
  xDiv.appendChild(xInput);
  xDiv.appendChild(xBtnRight);
  detailsDiv.appendChild(xDiv);

  // Y座標
  const yDiv = document.createElement('div');
  yDiv.className = 'coordinate-input';
  yDiv.innerHTML = `<label>Y座標:</label>`;
  
  const yBtnUp = document.createElement('button');
  yBtnUp.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnUp.innerHTML = '↑';
  yBtnUp.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('mouseup', stopLongPress);
  yBtnUp.addEventListener('mouseleave', stopLongPress);
  yBtnUp.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('touchend', stopLongPress);
  
  const yInput = document.createElement('input');
  yInput.type = 'number';
  yInput.step = '0.5';
  yInput.value = selectedField.y;
  yInput.className = 'form-control form-control-sm';
  yInput.style.width = '80px';
  yInput.style.display = 'inline-block';
  yInput.style.marginLeft = '5px';
  yInput.style.marginRight = '5px';
  yInput.setAttribute('data-property', 'y');
  yInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'y', this.value);
  });
  
  const yBtnDown = document.createElement('button');
  yBtnDown.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnDown.innerHTML = '↓';
  yBtnDown.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('mouseup', stopLongPress);
  yBtnDown.addEventListener('mouseleave', stopLongPress);
  yBtnDown.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('touchend', stopLongPress);
  
  yDiv.appendChild(yBtnUp);
  yDiv.appendChild(yInput);
  yDiv.appendChild(yBtnDown);
  detailsDiv.appendChild(yDiv);

  // フォントサイズ
  const fsDiv = document.createElement('div');
  fsDiv.className = 'coordinate-input';
  fsDiv.innerHTML = `<label>フォントサイズ:</label>`;
  
  const fsBtnMinus = document.createElement('button');
  fsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnMinus.innerHTML = '−';
  fsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('mouseup', stopLongPress);
  fsBtnMinus.addEventListener('mouseleave', stopLongPress);
  fsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const fsInput = document.createElement('input');
  fsInput.type = 'number';
  fsInput.step = '0.5';
  fsInput.value = selectedField.fontSize;
  fsInput.className = 'form-control form-control-sm';
  fsInput.style.width = '80px';
  fsInput.style.display = 'inline-block';
  fsInput.style.marginLeft = '5px';
  fsInput.style.marginRight = '5px';
  fsInput.setAttribute('data-property', 'fontSize');
  fsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'fontSize', this.value);
  });
  
  const fsBtnPlus = document.createElement('button');
  fsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnPlus.innerHTML = '+';
  fsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('mouseup', stopLongPress);
  fsBtnPlus.addEventListener('mouseleave', stopLongPress);
  fsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('touchend', stopLongPress);
  
  fsDiv.appendChild(fsBtnMinus);
  fsDiv.appendChild(fsInput);
  fsDiv.appendChild(fsBtnPlus);
  detailsDiv.appendChild(fsDiv);

  // 文字間隔
  const lsDiv = document.createElement('div');
  lsDiv.className = 'coordinate-input';
  lsDiv.innerHTML = `<label>文字間隔:</label>`;
  
  const lsBtnMinus = document.createElement('button');
  lsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnMinus.innerHTML = '−';
  lsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('mouseup', stopLongPress);
  lsBtnMinus.addEventListener('mouseleave', stopLongPress);
  lsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const lsInput = document.createElement('input');
  lsInput.type = 'number';
  lsInput.step = '0.1';
  lsInput.value = selectedField.letterSpacing || 0;
  lsInput.className = 'form-control form-control-sm';
  lsInput.style.width = '80px';
  lsInput.style.display = 'inline-block';
  lsInput.style.marginLeft = '5px';
  lsInput.style.marginRight = '5px';
  lsInput.setAttribute('data-property', 'letterSpacing');
  lsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'letterSpacing', this.value);
  });
  
  const lsBtnPlus = document.createElement('button');
  lsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnPlus.innerHTML = '+';
  lsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('mouseup', stopLongPress);
  lsBtnPlus.addEventListener('mouseleave', stopLongPress);
  lsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('touchend', stopLongPress);
  
  lsDiv.appendChild(lsBtnMinus);
  lsDiv.appendChild(lsInput);
  lsDiv.appendChild(lsBtnPlus);
  detailsDiv.appendChild(lsDiv);

  // テキスト配置
  const taDiv = document.createElement('div');
  taDiv.className = 'coordinate-input';
  taDiv.innerHTML = `<label>テキスト配置:</label>`;
  
  const taBtnGroup = document.createElement('div');
  taBtnGroup.className = 'btn-group btn-group-sm d-flex';
  taBtnGroup.setAttribute('role', 'group');
  taBtnGroup.style.marginLeft = '10px';
  
  const taLeft = document.createElement('button');
  taLeft.type = 'button';
  taLeft.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'left' ? 'active' : ''}`;
  taLeft.innerHTML = '左';
  taLeft.title = '左揃え';
  taLeft.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'left'));
  
  const taCenter = document.createElement('button');
  taCenter.type = 'button';
  taCenter.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'center' || !selectedField.textAlign ? 'active' : ''}`;
  taCenter.innerHTML = '中央';
  taCenter.title = '中央揃え';
  taCenter.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'center'));
  
  const taRight = document.createElement('button');
  taRight.type = 'button';
  taRight.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'right' ? 'active' : ''}`;
  taRight.innerHTML = '右';
  taRight.title = '右揃え';
  taRight.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'right'));
  
  taBtnGroup.appendChild(taLeft);
  taBtnGroup.appendChild(taCenter);
  taBtnGroup.appendChild(taRight);
  taDiv.appendChild(taBtnGroup);
  detailsDiv.appendChild(taDiv);

  // 折り返し幅（複数行テキストの場合）
  if (selectedField.width !== undefined) {
    const wDiv = document.createElement('div');
    wDiv.className = 'coordinate-input';
    wDiv.innerHTML = `<label>折り返し幅:</label>`;

    const wBtnMinus = document.createElement('button');
    wBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    wBtnMinus.innerHTML = '−';
    wBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'width', -5));
    wBtnMinus.addEventListener('mouseup', stopLongPress);
    wBtnMinus.addEventListener('mouseleave', stopLongPress);
    wBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'width', -5));
    wBtnMinus.addEventListener('touchend', stopLongPress);

    const wInput = document.createElement('input');
    wInput.type = 'number';
    wInput.step = '5';
    wInput.value = selectedField.width || 180;
    wInput.className = 'form-control form-control-sm';
    wInput.style.width = '80px';
    wInput.style.display = 'inline-block';
    wInput.style.marginLeft = '5px';
    wInput.style.marginRight = '5px';
    wInput.setAttribute('data-property', 'width');
    wInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'width', this.value);
    });

    const wBtnPlus = document.createElement('button');
    wBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    wBtnPlus.innerHTML = '+';
    wBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'width', 5));
    wBtnPlus.addEventListener('mouseup', stopLongPress);
    wBtnPlus.addEventListener('mouseleave', stopLongPress);
    wBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'width', 5));
    wBtnPlus.addEventListener('touchend', stopLongPress);

    wDiv.appendChild(wBtnMinus);
    wDiv.appendChild(wInput);
    wDiv.appendChild(wBtnPlus);
    detailsDiv.appendChild(wDiv);
  }

  // 行間（複数行テキストの場合）
  if (selectedField.lineHeight !== undefined) {
    const lhDiv = document.createElement('div');
    lhDiv.className = 'coordinate-input';
    lhDiv.innerHTML = `<label>行間:</label>`;

    const lhBtnMinus = document.createElement('button');
    lhBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    lhBtnMinus.innerHTML = '−';
    lhBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'lineHeight', -0.5));
    lhBtnMinus.addEventListener('mouseup', stopLongPress);
    lhBtnMinus.addEventListener('mouseleave', stopLongPress);
    lhBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'lineHeight', -0.5));
    lhBtnMinus.addEventListener('touchend', stopLongPress);

    const lhInput = document.createElement('input');
    lhInput.type = 'number';
    lhInput.step = '0.5';
    lhInput.value = selectedField.lineHeight || 5;
    lhInput.className = 'form-control form-control-sm';
    lhInput.style.width = '80px';
    lhInput.style.display = 'inline-block';
    lhInput.style.marginLeft = '5px';
    lhInput.style.marginRight = '5px';
    lhInput.setAttribute('data-property', 'lineHeight');
    lhInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'lineHeight', this.value);
    });

    const lhBtnPlus = document.createElement('button');
    lhBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    lhBtnPlus.innerHTML = '+';
    lhBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'lineHeight', 0.5));
    lhBtnPlus.addEventListener('mouseup', stopLongPress);
    lhBtnPlus.addEventListener('mouseleave', stopLongPress);
    lhBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'lineHeight', 0.5));
    lhBtnPlus.addEventListener('touchend', stopLongPress);

    lhDiv.appendChild(lhBtnMinus);
    lhDiv.appendChild(lhInput);
    lhDiv.appendChild(lhBtnPlus);
    detailsDiv.appendChild(lhDiv);
  }

  // 楽円設定（radioGroupの場合のみ）
  if (selectedField.ellipseWidth !== undefined) {
    const ewDiv = document.createElement('div');
    ewDiv.className = 'coordinate-input';
    ewDiv.innerHTML = `<label>楽円幅:</label>`;
    
    const ewBtnMinus = document.createElement('button');
    ewBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnMinus.innerHTML = '−';
    ewBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('mouseup', stopLongPress);
    ewBtnMinus.addEventListener('mouseleave', stopLongPress);
    ewBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ewInput = document.createElement('input');
    ewInput.type = 'number';
    ewInput.step = '0.5';
    ewInput.value = selectedField.ellipseWidth || 8;
    ewInput.className = 'form-control form-control-sm';
    ewInput.style.width = '80px';
    ewInput.style.display = 'inline-block';
    ewInput.style.marginLeft = '5px';
    ewInput.style.marginRight = '5px';
    ewInput.setAttribute('data-property', 'ellipseWidth');
    ewInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseWidth', this.value);
    });
    
    const ewBtnPlus = document.createElement('button');
    ewBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnPlus.innerHTML = '+';
    ewBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('mouseup', stopLongPress);
    ewBtnPlus.addEventListener('mouseleave', stopLongPress);
    ewBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('touchend', stopLongPress);
    
    ewDiv.appendChild(ewBtnMinus);
    ewDiv.appendChild(ewInput);
    ewDiv.appendChild(ewBtnPlus);
    detailsDiv.appendChild(ewDiv);
  }

  if (selectedField.ellipseHeight !== undefined) {
    const ehDiv = document.createElement('div');
    ehDiv.className = 'coordinate-input';
    ehDiv.innerHTML = `<label>楽円高さ:</label>`;
    
    const ehBtnMinus = document.createElement('button');
    ehBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnMinus.innerHTML = '−';
    ehBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('mouseup', stopLongPress);
    ehBtnMinus.addEventListener('mouseleave', stopLongPress);
    ehBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ehInput = document.createElement('input');
    ehInput.type = 'number';
    ehInput.step = '0.5';
    ehInput.value = selectedField.ellipseHeight || 5;
    ehInput.className = 'form-control form-control-sm';
    ehInput.style.width = '80px';
    ehInput.style.display = 'inline-block';
    ehInput.style.marginLeft = '5px';
    ehInput.style.marginRight = '5px';
    ehInput.setAttribute('data-property', 'ellipseHeight');
    ehInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseHeight', this.value);
    });
    
    const ehBtnPlus = document.createElement('button');
    ehBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnPlus.innerHTML = '+';
    ehBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('mouseup', stopLongPress);
    ehBtnPlus.addEventListener('mouseleave', stopLongPress);
    ehBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('touchend', stopLongPress);
    
    ehDiv.appendChild(ehBtnMinus);
    ehDiv.appendChild(ehInput);
    ehDiv.appendChild(ehBtnPlus);
    detailsDiv.appendChild(ehDiv);
  }

  // サンプルデータ入力（楽円フィールド以外）
  const isEllipseField = selectedField.ellipseWidth !== undefined || selectedField.ellipseHeight !== undefined;
  if (!isEllipseField) {
    const sampleDataHtml = getSampleDataInput(selectedKey);
    if (sampleDataHtml) {
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = sampleDataHtml;
      detailsDiv.appendChild(tempDiv.firstElementChild);
    }
  }

  fieldsContainer.appendChild(detailsDiv);

  autoSave();
  autoPreview();
}

// ラジオグループの選択を更新
function updateRadioGroupSelection(groupName, selectedKey) {
  // 座標のisSelectedを更新
  Object.keys(coordinates).forEach(key => {
    const field = coordinates[key];
    if (field.radioGroup === groupName) {
      field.isSelected = (key === selectedKey);
    }
  });

  // グループ内の設定詳細を表示
  const fieldsContainer = document.getElementById(`radiogroup-fields-${groupName}`);
  if (!fieldsContainer) return;

  fieldsContainer.innerHTML = '';

  const selectedField = coordinates[selectedKey];
  if (!selectedField) return;

  const detailsDiv = document.createElement('div');
  detailsDiv.style.borderTop = '1px solid #ddd';
  detailsDiv.style.marginTop = '10px';
  detailsDiv.style.paddingTop = '10px';

  // X座標
  const xDiv = document.createElement('div');
  xDiv.className = 'coordinate-input';
  xDiv.innerHTML = `
    <label>X座標:</label>
  `;
  const xBtnLeft = document.createElement('button');
  xBtnLeft.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnLeft.innerHTML = '←';
  xBtnLeft.addEventListener('mousedown', function() {
    startLongPress(selectedKey, 'x', -0.5);
  });
  xBtnLeft.addEventListener('mouseup', stopLongPress);
  xBtnLeft.addEventListener('mouseleave', stopLongPress);
  xBtnLeft.addEventListener('touchstart', function() {
    startLongPress(selectedKey, 'x', -0.5);
  });
  xBtnLeft.addEventListener('touchend', stopLongPress);
  
  const xInput = document.createElement('input');
  xInput.type = 'number';
  xInput.step = '0.5';
  xInput.value = selectedField.x;
  xInput.className = 'form-control form-control-sm';
  xInput.style.width = '80px';
  xInput.style.display = 'inline-block';
  xInput.style.marginLeft = '5px';
  xInput.style.marginRight = '5px';
  xInput.setAttribute('data-property', 'x');
  xInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'x', this.value);
  });
  
  const xBtnRight = document.createElement('button');
  xBtnRight.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnRight.innerHTML = '→';
  xBtnRight.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('mouseup', stopLongPress);
  xBtnRight.addEventListener('mouseleave', stopLongPress);
  xBtnRight.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('touchend', stopLongPress);
  
  xDiv.appendChild(xBtnLeft);
  xDiv.appendChild(xInput);
  xDiv.appendChild(xBtnRight);
  detailsDiv.appendChild(xDiv);

  // Y座標
  const yDiv = document.createElement('div');
  yDiv.className = 'coordinate-input';
  yDiv.innerHTML = `<label>Y座標:</label>`;
  
  const yBtnUp = document.createElement('button');
  yBtnUp.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnUp.innerHTML = '↑';
  yBtnUp.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('mouseup', stopLongPress);
  yBtnUp.addEventListener('mouseleave', stopLongPress);
  yBtnUp.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('touchend', stopLongPress);
  
  const yInput = document.createElement('input');
  yInput.type = 'number';
  yInput.step = '0.5';
  yInput.value = selectedField.y;
  yInput.className = 'form-control form-control-sm';
  yInput.style.width = '80px';
  yInput.style.display = 'inline-block';
  yInput.style.marginLeft = '5px';
  yInput.style.marginRight = '5px';
  yInput.setAttribute('data-property', 'y');
  yInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'y', this.value);
  });
  
  const yBtnDown = document.createElement('button');
  yBtnDown.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnDown.innerHTML = '↓';
  yBtnDown.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('mouseup', stopLongPress);
  yBtnDown.addEventListener('mouseleave', stopLongPress);
  yBtnDown.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('touchend', stopLongPress);
  
  yDiv.appendChild(yBtnUp);
  yDiv.appendChild(yInput);
  yDiv.appendChild(yBtnDown);
  detailsDiv.appendChild(yDiv);

  // 楕円・円のみのフィールドかどうかを判定
  const isShapeOnly = selectedField.ellipseWidth !== undefined || selectedField.ellipseHeight !== undefined || selectedField.circleRadius !== undefined;

  // フォントサイズ（楕円・円のみのフィールドでは非表示）
  if (!isShapeOnly) {
    const fsDiv = document.createElement('div');
    fsDiv.className = 'coordinate-input';
    fsDiv.innerHTML = `<label>フォントサイズ:</label>`;
  
  const fsBtnMinus = document.createElement('button');
  fsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnMinus.innerHTML = '−';
  fsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('mouseup', stopLongPress);
  fsBtnMinus.addEventListener('mouseleave', stopLongPress);
  fsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const fsInput = document.createElement('input');
  fsInput.type = 'number';
  fsInput.step = '0.5';
  fsInput.value = selectedField.fontSize;
  fsInput.className = 'form-control form-control-sm';
  fsInput.style.width = '80px';
  fsInput.style.display = 'inline-block';
  fsInput.style.marginLeft = '5px';
  fsInput.setAttribute('data-property', 'fontSize');
  fsInput.style.marginRight = '5px';
  fsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'fontSize', this.value);
  });
  
  const fsBtnPlus = document.createElement('button');
  fsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnPlus.innerHTML = '+';
  fsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('mouseup', stopLongPress);
  fsBtnPlus.addEventListener('mouseleave', stopLongPress);
  fsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('touchend', stopLongPress);
  
    fsDiv.appendChild(fsBtnMinus);
    fsDiv.appendChild(fsInput);
    fsDiv.appendChild(fsBtnPlus);
    detailsDiv.appendChild(fsDiv);
  }

  // 文字間隔（楕円・円のみのフィールドでは非表示）
  if (!isShapeOnly) {
    const lsDiv = document.createElement('div');
    lsDiv.className = 'coordinate-input';
    lsDiv.innerHTML = `<label>文字間隔:</label>`;
  
  const lsBtnMinus = document.createElement('button');
  lsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnMinus.innerHTML = '−';
  lsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('mouseup', stopLongPress);
  lsBtnMinus.addEventListener('mouseleave', stopLongPress);
  lsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const lsInput = document.createElement('input');
  lsInput.type = 'number';
  lsInput.step = '0.1';
  lsInput.value = selectedField.letterSpacing || 0;
  lsInput.className = 'form-control form-control-sm';
  lsInput.style.width = '80px';
  lsInput.style.display = 'inline-block';
  lsInput.style.marginLeft = '5px';
  lsInput.setAttribute('data-property', 'letterSpacing');
  lsInput.style.marginRight = '5px';
  lsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'letterSpacing', this.value);
  });
  
  const lsBtnPlus = document.createElement('button');
  lsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnPlus.innerHTML = '+';
  lsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('mouseup', stopLongPress);
  lsBtnPlus.addEventListener('mouseleave', stopLongPress);
  lsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('touchend', stopLongPress);
  
    lsDiv.appendChild(lsBtnMinus);
    lsDiv.appendChild(lsInput);
    lsDiv.appendChild(lsBtnPlus);
    detailsDiv.appendChild(lsDiv);
  }

  // 円半径（楕円を使用している場合は表示しない）
  if (selectedField.circleRadius !== undefined && selectedField.ellipseWidth === undefined && selectedField.ellipseHeight === undefined) {
    const crDiv = document.createElement('div');
    crDiv.className = 'coordinate-input';
    crDiv.innerHTML = `<label>○半径:</label>`;
    
    const crBtnMinus = document.createElement('button');
    crBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnMinus.innerHTML = '−';
    crBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('mouseup', stopLongPress);
    crBtnMinus.addEventListener('mouseleave', stopLongPress);
    crBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('touchend', stopLongPress);
    
    const crInput = document.createElement('input');
    crInput.type = 'number';
    crInput.step = '0.1';
    crInput.value = selectedField.circleRadius || 1.2;
    crInput.className = 'form-control form-control-sm';
    crInput.style.width = '80px';
    crInput.style.display = 'inline-block';
    crInput.setAttribute('data-property', 'circleRadius');
    crInput.style.marginLeft = '5px';
    crInput.style.marginRight = '5px';
    crInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'circleRadius', this.value);
    });
    
    const crBtnPlus = document.createElement('button');
    crBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnPlus.innerHTML = '+';
    crBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('mouseup', stopLongPress);
    crBtnPlus.addEventListener('mouseleave', stopLongPress);
    crBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('touchend', stopLongPress);
    
    crDiv.appendChild(crBtnMinus);
    crDiv.appendChild(crInput);
    crDiv.appendChild(crBtnPlus);
    detailsDiv.appendChild(crDiv);
  }

  // 楕円幅
  if (selectedField.ellipseWidth !== undefined) {
    const ewDiv = document.createElement('div');
    ewDiv.className = 'coordinate-input';
    ewDiv.innerHTML = `<label>楕円幅:</label>`;
    
    const ewBtnMinus = document.createElement('button');
    ewBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnMinus.innerHTML = '−';
    ewBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('mouseup', stopLongPress);
    ewBtnMinus.addEventListener('mouseleave', stopLongPress);
    ewBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ewInput = document.createElement('input');
    ewInput.type = 'number';
    ewInput.step = '0.5';
    ewInput.value = selectedField.ellipseWidth || 8;
    ewInput.className = 'form-control form-control-sm';
    ewInput.style.width = '80px';
    ewInput.style.display = 'inline-block';
    ewInput.setAttribute('data-property', 'ellipseWidth');
    ewInput.style.marginLeft = '5px';
    ewInput.style.marginRight = '5px';
    ewInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseWidth', this.value);
    });
    
    const ewBtnPlus = document.createElement('button');
    ewBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnPlus.innerHTML = '+';
    ewBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('mouseup', stopLongPress);
    ewBtnPlus.addEventListener('mouseleave', stopLongPress);
    ewBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('touchend', stopLongPress);
    
    ewDiv.appendChild(ewBtnMinus);
    ewDiv.appendChild(ewInput);
    ewDiv.appendChild(ewBtnPlus);
    detailsDiv.appendChild(ewDiv);
  }

  // 楕円高さ
  if (selectedField.ellipseHeight !== undefined) {
    const ehDiv = document.createElement('div');
    ehDiv.className = 'coordinate-input';
    ehDiv.innerHTML = `<label>楕円高さ:</label>`;
    
    const ehBtnMinus = document.createElement('button');
    ehBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnMinus.innerHTML = '−';
    ehBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('mouseup', stopLongPress);
    ehBtnMinus.addEventListener('mouseleave', stopLongPress);
    ehBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ehInput = document.createElement('input');
    ehInput.type = 'number';
    ehInput.step = '0.5';
    ehInput.value = selectedField.ellipseHeight || 5;
    ehInput.className = 'form-control form-control-sm';
    ehInput.style.width = '80px';
    ehInput.style.display = 'inline-block';
    ehInput.setAttribute('data-property', 'ellipseHeight');
    ehInput.style.marginLeft = '5px';
    ehInput.style.marginRight = '5px';
    ehInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseHeight', this.value);
    });
    
    const ehBtnPlus = document.createElement('button');
    ehBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnPlus.innerHTML = '+';
    ehBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('mouseup', stopLongPress);
    ehBtnPlus.addEventListener('mouseleave', stopLongPress);
    ehBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('touchend', stopLongPress);
    
    ehDiv.appendChild(ehBtnMinus);
    ehDiv.appendChild(ehInput);
    ehDiv.appendChild(ehBtnPlus);
    detailsDiv.appendChild(ehDiv);
  }

  fieldsContainer.appendChild(detailsDiv);

  autoSave();
  autoPreview();
}

// サンプルデータ入力欄を生成
function getSampleDataInput(key) {
  const showSampleData = document.getElementById('show-sample-data')?.checked;
  if (!showSampleData) return '';

  const mapping = sampleDataFieldMapping[key];
  if (!mapping) return '';

  let inputHtml = '';

  // combine属性がある場合は複数のフィールドの入力欄を作成
  if (mapping.combine && Array.isArray(mapping.combine)) {
    inputHtml = '<div>';
    mapping.combine.forEach(fieldName => {
      const currentValue = customSampleData[fieldName] || '';
      let fieldLabel = '';
      let labelPrefix = '';
      
      // 氏名系の判定
      if (fieldName.includes('last') || fieldName.includes('first')) {
        fieldLabel = fieldName.includes('last') ? '姓' : '名';
        const isKana = fieldName.includes('kana');
        labelPrefix = isKana ? '氏名カナ' : '氏名';
      }
      // 記号番号系の判定
      else if (fieldName.includes('kigou')) {
        fieldLabel = '記号';
        labelPrefix = '記号番号';
      }
      else if (fieldName.includes('bangou')) {
        fieldLabel = '番号';
        labelPrefix = '記号番号';
      }
      else {
        fieldLabel = fieldName;
        labelPrefix = mapping.label || '';
      }
      
      inputHtml += `
        <div class="coordinate-input">
          <label>サンプル${labelPrefix}（${fieldLabel}）:</label>
          <input type="text"
                 value="${currentValue}"
                 onchange="updateSampleData('${fieldName}', this.value)"
                 class="form-control form-control-sm">
        </div>
      `;
    });
    inputHtml += '</div>';
    return inputHtml;
  }

  const currentValue = customSampleData[mapping.field] || '';

  if (mapping.type === 'text' || mapping.type === 'number') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="${mapping.type}"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm">
      </div>
    `;
  } else if (mapping.type === 'date') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="text"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm"
               placeholder="例: 2024-01-01">
      </div>
    `;
  } else if (mapping.type === 'select') {
    let options = '';
    
    // masterKeyからmasterDataを参照してオプション生成
    if (mapping.masterKey) {
      const masterKey = mapping.masterKey;
      const valueField = mapping.valueField;
      const masterOptions = masterData[masterKey] || [];
      
      options = masterOptions.map(opt => {
        const optValue = opt[valueField] || opt;
        return `<option value="${optValue}" ${currentValue === optValue ? 'selected' : ''}>${optValue}</option>`;
      }).join('');
    }
    // optionsから直接オプション生成
    else if (mapping.options) {
      options = mapping.options.map((opt, index) => {
        const label = mapping.optionLabels ? mapping.optionLabels[index] : opt;
        return `<option value="${opt}" ${currentValue === opt ? 'selected' : ''}>${label}</option>`;
      }).join('');
    }
    
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <select onchange="updateSampleData('${mapping.field}', this.value)"
                class="form-control form-control-sm">
          ${options}
        </select>
      </div>
    `;
  } else if (mapping.type === 'postal_code') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="text"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm"
               placeholder="例: 1600022">
      </div>
    `;
  }

  return inputHtml;
}

// 座標更新
function updateCoordinate(key, property, value) {
  // テキスト配置の場合は文字列、その他は数値に変換
  if (property === 'textAlign') {
    coordinates[key][property] = value;
  } else {
    coordinates[key][property] = parseFloat(value);
  }
  
  // postal_codeタイプの場合、x/y/postalCodeGap変更時にfirstX/firstY/lastXも同期
  const mapping = sampleDataFieldMapping[key];
  if (mapping && mapping.type === 'postal_code') {
    if (property === 'x') {
      coordinates[key]['firstX'] = parseFloat(value);
      // lastXも再計算
      const gap = coordinates[key]['postalCodeGap'] || 2;
      coordinates[key]['lastX'] = parseFloat(value) + gap;
    } else if (property === 'y') {
      coordinates[key]['firstY'] = parseFloat(value);
      coordinates[key]['lastY'] = parseFloat(value);
    } else if (property === 'postalCodeGap') {
      // postalCodeGap変更時はlastXを再計算
      const firstX = coordinates[key]['firstX'] || coordinates[key]['x'] || 0;
      coordinates[key]['lastX'] = firstX + parseFloat(value);
    }
  }
  
  // テキスト配置更新の場合はボタンのアクティブ状態を更新
  if (property === 'textAlign') {
    const radioGroupName = coordinates[key].radioGroup;
    const compositeGroupName = coordinates[key].compositeGroup;
    
    let controls = document.getElementById('controls-' + key);
    if (!controls && radioGroupName) {
      controls = document.getElementById('radiogroup-fields-' + radioGroupName);
    }
    if (!controls && compositeGroupName) {
      controls = document.getElementById('compositegroup-fields-' + compositeGroupName);
    }
    
    if (controls) {
      const buttons = controls.querySelectorAll('.btn-group .btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      
      const activeIndex = ['left', 'center', 'right'].indexOf(value);
      if (activeIndex >= 0 && buttons[activeIndex]) {
        buttons[activeIndex].classList.add('active');
      }
    }
  }
  
  autoPreview();
  autoSave();
}

// 微調整ボタン
function adjustValue(key, property, delta) {
  const currentValue = coordinates[key][property] || 0;
  
  // delta の精度に応じて丸め桁を決定
  let roundDigits = 1; // デフォルトは小数第1位
  if (Math.abs(delta) === 0.05) {
    roundDigits = 2; // ±0.05 の場合は小数第2位
  } else if (Math.abs(delta) === 0.1) {
    roundDigits = 1; // ±0.1 の場合は小数第1位
  }
  
  const multiplier = Math.pow(10, roundDigits);
  const newValue = Math.round((currentValue + delta) * multiplier) / multiplier;
  coordinates[key][property] = newValue;

  // postal_codeタイプの場合、x/y/postalCodeGap変更時にfirstX/firstY/lastXも同期
  const mapping = sampleDataFieldMapping[key];
  if (mapping && mapping.type === 'postal_code') {
    if (property === 'x') {
      coordinates[key]['firstX'] = newValue;
      // lastXも再計算
      const gap = coordinates[key]['postalCodeGap'] || 2;
      coordinates[key]['lastX'] = newValue + gap;
    } else if (property === 'y') {
      coordinates[key]['firstY'] = newValue;
      coordinates[key]['lastY'] = newValue;
    } else if (property === 'postalCodeGap') {
      // postalCodeGap変更時はlastXを再計算
      const firstX = coordinates[key]['firstX'] || coordinates[key]['x'] || 0;
      coordinates[key]['lastX'] = firstX + newValue;
    }
  }

  // 該当のinput要素を data-property 属性で探して更新
  // ラジオグループとcompositeGroupの場合とそうでない場合の両方に対応
  const controlsId = 'controls-' + key;
  const radioGroupName = coordinates[key].radioGroup;
  const compositeGroupName = coordinates[key].compositeGroup;
  
  let controls = document.getElementById(controlsId);
  if (!controls && radioGroupName) {
    // ラジオグループの場合
    controls = document.getElementById('radiogroup-fields-' + radioGroupName);
  }
  if (!controls && compositeGroupName) {
    // compositeGroupの場合
    controls = document.getElementById('compositegroup-fields-' + compositeGroupName);
  }
  
  if (controls) {
    const input = controls.querySelector(`input[data-property="${property}"]`);
    if (input) {
      input.value = newValue;
    }
  }

  autoPreview();
  autoSave();
}

// 自動プレビュー（デバウンス付き）
let previewTimeout = null;
function autoPreview() {
  clearTimeout(previewTimeout);
  previewTimeout = setTimeout(() => {
    previewPdf();
  }, 500); // 500ms後にプレビュー更新
}

// 自動保存（デバウンス付き）
let saveTimeout = null;
function autoSave() {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    saveCoordinates(true); // 自動保存フラグ
  }, 1000); // 1秒後に自動保存
}

// 長押し処理
let longPressInterval = null;
let longPressTimeout = null;

function startLongPress(key, property, delta) {
  // 即座に1回実行
  adjustValue(key, property, delta);

  // 100ms後から連続実行開始
  longPressTimeout = setTimeout(() => {
    longPressInterval = setInterval(() => {
      adjustValue(key, property, delta);
    }, 1); // 5msごとに実行
  }, 100);
}

function stopLongPress() {
  if (longPressTimeout) {
    clearTimeout(longPressTimeout);
    longPressTimeout = null;
  }
  if (longPressInterval) {
    clearInterval(longPressInterval);
    longPressInterval = null;
  }
}

// 保存
function saveCoordinates(isAuto = false) {
  const saveIndicator = document.getElementById('save-indicator');
  const saveStatus = document.getElementById('save-status');

  // 保存中インジケーター表示
  if (isAuto) {
    saveIndicator.style.display = 'inline-block';
  }

  // isSelectedフラグを除外した座標データを作成
  const coordinatesToSave = {};
  Object.keys(coordinates).forEach(key => {
    coordinatesToSave[key] = {};
    Object.keys(coordinates[key]).forEach(prop => {
      if (prop !== 'isSelected') {
        coordinatesToSave[key][prop] = coordinates[key][prop];
      }
    });
  });

  fetch('/prints/save-coordinates', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      coordinates: coordinatesToSave,
      pdf_type: currentPdfType
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        originalCoordinates = JSON.parse(JSON.stringify(coordinates));

        if (isAuto) {
          // 自動保存時は控えめな通知
          saveIndicator.style.display = 'none';
          saveStatus.style.display = 'block';
          setTimeout(() => {
            saveStatus.style.display = 'none';
          }, 2000);
        }
      } else {
        if (isAuto) {
          saveIndicator.style.display = 'none';
        }
        console.error('保存失敗:', data.message);
      }
    })
    .catch(error => {
      if (isAuto) {
        saveIndicator.style.display = 'none';
      }
      console.error('保存エラー:', error);
    });
}

// プレビュー
function previewPdf() {
  const iframe = document.getElementById('pdf-iframe');
  const loadingBadge = document.getElementById('preview-loading');
  const overlay = document.getElementById('preview-overlay');
  const clinicUserSelect = document.getElementById('clinic-user-select');
  const clinicUserId = clinicUserSelect ? clinicUserSelect.value : null;
  const showSampleData = document.getElementById('show-sample-data').checked;

  // ローディング表示
  loadingBadge.style.display = 'inline-block';
  overlay.style.display = 'flex';

  // プレビュー用の座標データを作成
  let coordinatesForPreview = coordinates;

  // サンプルデータ表示がOFFの場合は、isSelectedフラグを除外
  if (!showSampleData) {
    coordinatesForPreview = {};
    Object.keys(coordinates).forEach(key => {
      coordinatesForPreview[key] = {};
      Object.keys(coordinates[key]).forEach(prop => {
        if (prop !== 'isSelected') {
          coordinatesForPreview[key][prop] = coordinates[key][prop];
        }
      });
    });
  }

  const requestBody = {
    coordinates: coordinatesForPreview,
    clinic_user_id: clinicUserId,
    pdf_type: currentPdfType,
    show_sample_data: showSampleData
  };

  // カスタムサンプルデータがある場合は追加
  if (showSampleData && customSampleData) {
    requestBody.custom_sample_data = customSampleData;
  }

  fetch('/prints/preview-pdf', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(requestBody)
  })
    .then(response => response.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob);
      iframe.src = url;

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    })
    .catch(error => {
      console.error('プレビューエラー:', error);
      alert('プレビュー表示に失敗しました');

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    });
}

// フィールドの展開/折りたたみ
function toggleField(key) {
  const controls = document.getElementById('controls-' + key);
  const toggle = document.getElementById('toggle-' + key);

  if (controls.classList.contains('show')) {
    // 格納
    controls.style.maxHeight = controls.scrollHeight + 'px';
    setTimeout(() => {
      controls.style.maxHeight = '0';
    }, 10);
    controls.classList.remove('show');
    toggle.textContent = '▶';
  } else {
    // 展開
    controls.classList.add('show');
    controls.style.maxHeight = controls.scrollHeight + 'px';
    toggle.textContent = '▼';

    // アニメーション完了後にmax-heightをnoneに設定（リサイズ対応）
    controls.addEventListener('transitionend', function handler() {
      if (controls.classList.contains('show')) {
        controls.style.maxHeight = 'none';
      }
      controls.removeEventListener('transitionend', handler);
    });
  }
}

// リセット
function resetCoordinates() {
  if (!confirm('変更を破棄して元に戻しますか？')) return;

  coordinates = JSON.parse(JSON.stringify(originalCoordinates));
  renderFieldSettings();
  previewPdf();
}

// カスタムサンプルデータをlocalStorageから読み込み
function loadCustomSampleData() {
  const storageKey = 'customSampleData_' + currentPdfType;
  const stored = localStorage.getItem(storageKey);
  if (stored) {
    try {
      const savedData = JSON.parse(stored);
      // デフォルト値とマージ
      customSampleData = { ...customSampleData, ...savedData };
    } catch (e) {
      console.error('サンプルデータの読み込みエラー:', e);
    }
  }

  // consent_dateのデフォルト値を設定
  if (!customSampleData.consent_date) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    customSampleData.consent_date = `${yyyy}-${mm}-${dd}`;
  }
}

// サンプルデータを更新
function updateSampleData(field, value) {
  customSampleData[field] = value;

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));

  // プレビューを自動更新
  autoPreview();
}

// 施術料金データを表示
function displayTreatmentFees() {
  const container = document.getElementById('treatment-fees-display');
  if (!container) return;

  if (!treatmentFees) {
    container.innerHTML = '<div style="color: #999;">施術料金データなし</div>';
    return;
  }

  // 料金項目のラベルマッピング
  const feeLabels = {
    // 鍼灸関連
    hari_first: 'はり（初検）',
    hari_normal: 'はり（2回目以降）',
    hari_and_elec_needle_first: 'はり+電気鍼（初検）',
    hari_and_elec_needle_normal: 'はり+電気鍼（2回目以降）',
    kyu_first: 'きゅう（初検）',
    kyu_normal: 'きゅう（2回目以降）',
    kyu_and_elec_moxa_heater_first: 'きゅう+電気温灸器（初検）',
    kyu_and_elec_moxa_heater_normal: 'きゅう+電気温灸器（2回目以降）',
    hari_and_kyu_first: 'はり+きゅう（初検）',
    hari_and_kyu_normal: 'はり+きゅう（2回目以降）',
    hari_and_kyu_elec_first: 'はり+きゅう+電気（初検）',
    hari_and_kyu_elec_normal: 'はり+きゅう+電気（2回目以降）',
    housecall_max_2km_first: '往療（2km以内・初検）',
    housecall_max_2km_normal: '往療（2km以内・2回目以降）',
    housecall_additional_max_4km_first: '往療加算（4km以内・初検）',
    housecall_additional_max_4km_normal: '往療加算（4km以内・2回目以降）',

    // マッサージ関連
    massage_trunk_first: 'マッサージ 体幹（初検）',
    massage_trunk_normal: 'マッサージ 体幹（2回目以降）',
    massage_upper_limb_r_first: 'マッサージ 上肢右（初検）',
    massage_upper_limb_r_normal: 'マッサージ 上肢右（2回目以降）',
    massage_upper_limb_l_first: 'マッサージ 上肢左（初検）',
    massage_upper_limb_l_normal: 'マッサージ 上肢左（2回目以降）',
    massage_lower_limb_r_first: 'マッサージ 下肢右（初検）',
    massage_lower_limb_r_normal: 'マッサージ 下肢右（2回目以降）',
    massage_lower_limb_l_first: 'マッサージ 下肢左（初検）',
    massage_lower_limb_l_normal: 'マッサージ 下肢左（2回目以降）',
    manual_correction_first: '変形徒手矯正術（初検）',
    manual_correction_normal: '変形徒手矯正術（2回目以降）',
    fomentation_first: '温罨法（初検）',
    fomentation_normal: '温罨法（2回目以降）',
    fomentation_and_elec_ray_first: '温罨法+電気光線（初検）',
    fomentation_and_elec_ray_normal: '温罨法+電気光線（2回目以降）'
  };

  // PDFタイプに応じて表示する項目をフィルタリング
  let html = '<div style="max-height: 300px; overflow-y: auto;">';

  // 適用期間を表示
  if (treatmentFees.period_start && treatmentFees.period_end) {
    html += `<div style="margin-bottom: 8px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 4px;">`;
    html += `適用期間: ${treatmentFees.period_start} 〜 ${treatmentFees.period_end}`;
    html += `</div>`;
  }

  Object.keys(feeLabels).forEach(key => {
    // PDFタイプに応じてフィルタリング
    if (currentPdfType === 'acupuncture') {
      // 鍼灸用PDFでは鍼灸関連の料金のみ表示
      if (!key.startsWith('hari_') && !key.startsWith('kyu_') && !key.startsWith('housecall_')) {
        return;
      }
    } else if (currentPdfType === 'massage') {
      // マッサージ用PDFではマッサージ関連の料金のみ表示
      if (!key.startsWith('massage_') && !key.startsWith('manual_') && !key.startsWith('fomentation_')) {
        return;
      }
    }

    const value = treatmentFees[key];
    if (value !== null && value !== undefined) {
      html += `<div style="margin-bottom: 4px; display: flex; justify-content: space-between;">`;
      html += `<span style="flex: 1; font-size: 0.75em;">${feeLabels[key]}:</span>`;
      html += `<span style="font-weight: bold; min-width: 60px; text-align: right;">${Number(value).toLocaleString()}円</span>`;
      html += `</div>`;
    }
  });

  html += '</div>';
  container.innerHTML = html;
}
</script>
</x-app-layout>
