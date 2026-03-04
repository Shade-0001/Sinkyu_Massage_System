// フィールド定義（座標調整ツール用）
//
// 【役割】
// 1. フィールドのメタデータ定義（type, label, options等）
// 2. UI表示順序の決定（オブジェクトの定義順 = 表示順）
// 3. サンプルデータとのマッピング（fieldプロパティ）
//
// ============================================================
// 【重要】新しいフィールドを追加する際の必須作業チェックリスト
// ============================================================
// 1. このファイル（coordinate-adjuster_fields.js）にフィールド定義を追加
// 2. 座標JSONファイル（*_coordinates.json）に座標情報を追加
// 3. coordinate-adjuster_categories.js にカテゴリ定義を追加
// 4. PHPサービスクラス（*PdfService.php）に描画ロジックを追加
// 5. coordinate-adjuster_data.js の customSampleData にキーを追加
//    ※ここに追加しないとサンプルモードでの入力テキストが描画されない
//    ※PHPの getSampleData() も $this->customSampleData を参照する実装にすること
//
// ※1~5全て必須。どれか1つでも欠けると正しく動作しない
// ============================================================
//
// ============================================================
// 【重要】フィールドの表示順序を変更する際の必須作業
// ============================================================
// フィールドの表示順序は以下の2つの要素で決定される：
//
// 1. このファイル（fieldDefinitions）のオブジェクト定義順
//    → カテゴリ内での並び順を決定（最重要！）
//
// 2. coordinate-adjuster_categories.js のカテゴリマッピング
//    → 各フィールドがどのカテゴリに属するかを決定
//
// 【誤り】カテゴリマッピングだけを変更しても順序は変わらない
// 【正解】このファイルの定義順とカテゴリマッピングの両方を変更する
//
// 【例】施術内容欄の順序を変更する場合：
// 1. このファイルで該当フィールドを希望の順序に並べ替える
// 2. coordinate-adjuster_categories.js でカテゴリマッピングも同じ順序に更新
//
// ※どちらか一方だけでは不十分。必ず両方を修正すること
// ============================================================
//
// ============================================================
// 【重要】select型フィールド（radioGroup）の表記統一ルール
// ============================================================
// select型フィールドで複数の選択肢を持つ場合、以下3箇所の表記を
// 完全に一致させる必要がある。1文字でも異なるとマッピングが失敗する。
//
// 1. このファイル（coordinate-adjuster_fields.js）の options 配列
// 2. 座標JSONファイル（*_coordinates.json）の options / optionLabels 配列
// 3. PHPサービスクラス（*FormFieldsTrait.php）の $keyMap 連想配列
//
// 【例】初検料（サークル）フィールドの場合:
//
// ✅ 正しい統一表記:
//   - 'はり（電気鍼併用）'
//   - 'きゅう（電気温灸器併用）'
//
// ❌ 短縮形は使用禁止:
//   - 'はり（電気鍼）'     → マッピング失敗
//   - 'きゅう（電気温灸器）' → マッピング失敗
//
// 【修正が必要なファイル】
// - public/js/coordinate-adjuster_fields.js（このファイル）
// - storage/app/config/*_coordinates.json
// - app/Services/Print/Traits/*FormFieldsTrait.php
//
// ※表記を変更する場合は、必ず上記3ファイル全てを同時に修正すること
// ============================================================
//
const fieldDefinitions = {
  // === 1. タイトル・機関コード ===
  'custom_title_text': { field: 'custom_title_text', label: 'タイトル', type: 'text' },
  'submission_count': { field: 'submission_count', label: '第ｎ回', type: 'number' },
  'title_year_era': { field: 'title_year_era', label: '元号', type: 'select', options: ['令和', '平成', '昭和'], compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'title_year_number': { field: 'title_year_number', label: '年', type: 'number', compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'title_month': { field: 'title_month', label: '月', type: 'number', compositeGroup: 'title_date', compositeLabel: 'タイトル年月' },
  'institution_code': { field: 'institution_code', label: '機関コード', type: 'text' },

  // === 2. 公費・受給者番号 ===
  'public_funds_payer_number': { field: 'public_funds_payer_number', label: '公費負担者番号', type: 'text' },
  'public_funds_recipient_number': { field: 'public_funds_recipient_number', label: '公費受給者番号', type: 'text' },
  'locality_code': { field: 'locality_code', label: '区市町村番号', type: 'text' },
  'recipient_number': { field: 'recipient_number', label: '受給者番号', type: 'text' },

  // === 3. 保険種別 ===
  'insurance_type_1_shakoku': { field: 'insurance_type_1', label: '保険種別１', type: 'select', masterKey: 'insurance_types_1', valueField: 'insurance_type_1', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_1', optionLabel: '社･国･組' },
  'insurance_type_1_kouhi': { field: 'insurance_type_1', label: '保険種別１', type: 'select', masterKey: 'insurance_types_1', valueField: 'insurance_type_1', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_1', optionLabel: '公費' },
  'insurance_type_1_kouki': { field: 'insurance_type_1', label: '保険種別１', type: 'select', masterKey: 'insurance_types_1', valueField: 'insurance_type_1', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_1', optionLabel: '後期' },
  'insurance_type_1_taishoku': { field: 'insurance_type_1', label: '保険種別１', type: 'select', masterKey: 'insurance_types_1', valueField: 'insurance_type_1', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_1', optionLabel: '退職' },
  'insurance_type_3_hongai': { field: 'insurance_type_3', label: '保険種別３', type: 'select', masterKey: 'insurance_types_3', valueField: 'insurance_type_3', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_3', optionLabel: '本外' },
  'insurance_type_3_sangai': { field: 'insurance_type_3', label: '保険種別３', type: 'select', masterKey: 'insurance_types_3', valueField: 'insurance_type_3', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_3', optionLabel: '三外' },
  'insurance_type_3_kagai': { field: 'insurance_type_3', label: '保険種別３', type: 'select', masterKey: 'insurance_types_3', valueField: 'insurance_type_3', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_3', optionLabel: '家外' },
  'insurance_type_3_kougai9': { field: 'insurance_type_3', label: '保険種別３', type: 'select', masterKey: 'insurance_types_3', valueField: 'insurance_type_3', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_3', optionLabel: '高外９' },
  'insurance_type_3_kougai8': { field: 'insurance_type_3', label: '保険種別３', type: 'select', masterKey: 'insurance_types_3', valueField: 'insurance_type_3', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'insurance_type_3', optionLabel: '高外８' },
  'benefit_ratio_80': { field: 'benefit_ratio', label: '給付割合', type: 'select', options: ['8割', '9割', '10割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'benefit_ratio', optionLabel: '8割' },
  'benefit_ratio_90': { field: 'benefit_ratio', label: '給付割合', type: 'select', options: ['8割', '9割', '10割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'benefit_ratio', optionLabel: '9割' },
  'benefit_ratio_100': { field: 'benefit_ratio', label: '給付割合', type: 'select', options: ['8割', '9割', '10割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'benefit_ratio', optionLabel: '10割' },

  // === 4. 保険者番号 ===
  'insurer_number': { field: 'insurer_number', label: '保険者番号', type: 'text' },

  // === 5. 被保険者証記号・番号 ===
  'insurance_symbol_code': { field: 'insurance_symbol_code', label: '被保険者記号', type: 'text' },
  'insurance_symbol_number': { field: 'insurance_symbol_number', label: '被保険者番号', type: 'text' },

  // === 6. 患者氏名カナ・続柄・氏名 ===
  'patient_last_kana': { field: 'last_kana', label: '氏名カナ（姓）', type: 'text' },
  'patient_first_kana': { field: 'first_kana', label: '氏名カナ（名）', type: 'text' },
  'patient_name_kana': { field: 'last_kana', label: '氏名カナ（姓名）', type: 'text', combine: ['last_kana', 'first_kana'] },
  'patient_relationship': { field: 'relationship', label: '続柄', type: 'select', masterKey: 'relationships', valueField: 'relationship' },
  'patient_last_name': { field: 'last_name', label: '氏名（姓）', type: 'text' },
  'patient_first_name': { field: 'first_name', label: '氏名（名）', type: 'text' },
  'patient_name': { field: 'last_name', label: '氏名（姓名）', type: 'text', combine: ['last_name', 'first_name'] },

  // === 7. 性別・生年月日 ===
  'patient_gender_male': { field: 'gender', label: '性別', type: 'select', masterKey: 'genders', valueField: 'gender', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'gender', optionLabel: '男' },
  'patient_gender_female': { field: 'gender', label: '性別', type: 'select', masterKey: 'genders', valueField: 'gender', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'gender', optionLabel: '女' },
  'birthday_era_meiji': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '明治', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_taisho': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '大正', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_showa': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '昭和', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_era_heisei': { field: 'birthday_era', label: '元号', type: 'select', options: ['明治', '大正', '昭和', '平成'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'birthday_era', optionLabel: '平成', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_year': { field: 'birthday_year', label: '年', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日', optionLabel: '年月日' },
  'birthday_month': { field: 'birthday_month', label: '月', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'birthday_day': { field: 'birthday_day', label: '日', type: 'text', compositeGroup: 'birthday_full_date', compositeLabel: '生年月日' },
  'insured_person_name': { field: 'insured_person_name', label: '被保険者氏名', type: 'text' },

  // === 8. 初療年月日・施術期間 ===
  'first_treatment_date': { field: 'first_treatment_date', label: '初療年月日', type: 'text', compositeGroup: 'first_treatment_date_composite', compositeLabel: '初療年月日', hidden: true },
  'first_treatment_era': { field: 'first_treatment_era', label: '元号', type: 'text', compositeGroup: 'first_treatment_date_composite', compositeLabel: '初療年月日', hidden: true },
  'first_treatment_year': { field: 'first_treatment_year', label: '年', type: 'number', compositeGroup: 'first_treatment_date_composite', compositeLabel: '初療年月日', hidden: true },
  'first_treatment_month': { field: 'first_treatment_month', label: '月', type: 'number', compositeGroup: 'first_treatment_date_composite', compositeLabel: '初療年月日', hidden: true },
  'first_treatment_day': { field: 'first_treatment_day', label: '日', type: 'number', compositeGroup: 'first_treatment_date_composite', compositeLabel: '初療年月日', hidden: true },
  'treatment_period': { field: 'treatment_period', label: '施術期間', type: 'text' },
  'treatment_start_date': { field: 'treatment_start_date', label: '施術期間（開始）', type: 'text', compositeGroup: 'treatment_start_date_composite', compositeLabel: '施術期間（開始）', hidden: true },
  'treatment_start_year': { field: 'treatment_start_year', label: '年', type: 'number', compositeGroup: 'treatment_start_date_composite', compositeLabel: '施術期間（開始）', hidden: true },
  'treatment_start_month': { field: 'treatment_start_month', label: '月', type: 'number', compositeGroup: 'treatment_start_date_composite', compositeLabel: '施術期間（開始）', hidden: true },
  'treatment_start_day': { field: 'treatment_start_day', label: '日', type: 'number', compositeGroup: 'treatment_start_date_composite', compositeLabel: '施術期間（開始）', hidden: true },
  'treatment_end_date': { field: 'treatment_end_date', label: '施術期間（終了）', type: 'text', compositeGroup: 'treatment_end_date_composite', compositeLabel: '施術期間（終了）', hidden: true },
  'treatment_end_year': { field: 'treatment_end_year', label: '年', type: 'number', compositeGroup: 'treatment_end_date_composite', compositeLabel: '施術期間（終了）', hidden: true },
  'treatment_end_month': { field: 'treatment_end_month', label: '月', type: 'number', compositeGroup: 'treatment_end_date_composite', compositeLabel: '施術期間（終了）', hidden: true },
  'treatment_end_day': { field: 'treatment_end_day', label: '日', type: 'number', compositeGroup: 'treatment_end_date_composite', compositeLabel: '施術期間（終了）', hidden: true },

  // === 9. 実日数 ===
  'treatment_day_count': { field: 'treatment_days', label: '実日数', type: 'number' },

  // === 9-1. 業務上･外･第三者行為の有無 ===
  'work_scope_type_1': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: '業務上' },
  'work_scope_type_2': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: '第三者行為' },
  'work_scope_type_3': { field: 'work_scope_type', label: '業務上･外･第三者行為の有無', type: 'select', options: ['業務上', '第三者行為', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'work_scope_type', optionLabel: 'その他' },

  // === 9-2. 傷病名 ===
  'treatment_content_illness_name': { field: 'consent_record_illness_name', label: '傷病名', type: 'text' },
  'illness_name_symptom': { field: 'illness_name_symptom', label: '傷病名・症状', type: 'text' },

  'illness_name_1': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '神経痛' },
  'illness_name_2': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'リウマチ' },
  'illness_name_3': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸腕症候群' },
  'illness_name_4': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '五十肩' },
  'illness_name_5': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '腰痛症' },
  'illness_name_6': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸椎捻挫後遺症' },
  'illness_name_7': { field: 'illness_name', label: '傷病名', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'その他' },
  'illness_name_other_text': { field: 'illness_name_other_text', label: '傷病名（その他の内容）', type: 'text' },

  // === 9-3. 発病負傷年月日 ===
  'onset_date': { field: 'onset_date', label: '発病負傷年月日', type: 'text' },
  'onset_date_year': { field: 'onset_date_year', label: '年', type: 'number', compositeGroup: 'onset_date_composite', compositeLabel: '発病負傷年月日', hidden: true },
  'onset_date_month': { field: 'onset_date_month', label: '月', type: 'number', compositeGroup: 'onset_date_composite', compositeLabel: '発病負傷年月日', hidden: true },
  'onset_date_day': { field: 'onset_date_day', label: '日', type: 'number', compositeGroup: 'onset_date_composite', compositeLabel: '発病負傷年月日', hidden: true },

  // === 9-4. 請求区分・転帰 ===
  'bill_category': { field: 'bill_category', label: '請求区分', type: 'text' },
  'outcome': { field: 'outcome', label: '転帰', type: 'text' },

  // === 9-5. 摘要 ===
  'condition': { field: 'condition', label: '発病負傷の原因･経過', type: 'text' },
  'abstract': { field: 'abstract', label: '摘要', type: 'text', width: 180, lineHeight: 5 },

  // === 10. 施術月・施術日 ===
  'treatment_month': { field: 'treatment_month', label: '施術月', type: 'number' },
  'treatment_days': { field: 'treatment_days', label: '施術日', type: 'calendar', circleRadius: 1.8, doubleCircleInnerRadius: 2.5, circleSpacing: 6.45 },

  // === 11. 施術料金（鍼灸） ===
  'fee_hari_unit': { field: 'fee_hari_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり', optionLabel: '単価' },
  'fee_hari_count': { field: 'fee_hari_count', label: '回数', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり', optionLabel: '回数' },
  'fee_hari_total': { field: 'fee_hari_total', label: '合計', type: 'number', compositeGroup: 'fee_hari', compositeLabel: 'はり', optionLabel: '合計' },
  'fee_hari_electric_unit': { field: 'fee_hari_electric_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari_electric', compositeLabel: 'はり（電気鍼併用）', optionLabel: '単価' },
  'fee_hari_electric_count': { field: 'fee_hari_electric_count', label: '回数', type: 'number', compositeGroup: 'fee_hari_electric', compositeLabel: 'はり（電気鍼併用）', optionLabel: '回数' },
  'fee_hari_electric_total': { field: 'fee_hari_electric_total', label: '合計', type: 'number', compositeGroup: 'fee_hari_electric', compositeLabel: 'はり（電気鍼併用）', optionLabel: '合計' },
  'fee_kyu_unit': { field: 'fee_kyu_unit', label: '単価', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう', optionLabel: '単価' },
  'fee_kyu_count': { field: 'fee_kyu_count', label: '回数', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう', optionLabel: '回数' },
  'fee_kyu_total': { field: 'fee_kyu_total', label: '合計', type: 'number', compositeGroup: 'fee_kyu', compositeLabel: 'きゅう', optionLabel: '合計' },
  'fee_kyu_electric_unit': { field: 'fee_kyu_electric_unit', label: '単価', type: 'number', compositeGroup: 'fee_kyu_electric', compositeLabel: 'きゅう（電気温灸器併用）', optionLabel: '単価' },
  'fee_kyu_electric_count': { field: 'fee_kyu_electric_count', label: '回数', type: 'number', compositeGroup: 'fee_kyu_electric', compositeLabel: 'きゅう（電気温灸器併用）', optionLabel: '回数' },
  'fee_kyu_electric_total': { field: 'fee_kyu_electric_total', label: '合計', type: 'number', compositeGroup: 'fee_kyu_electric', compositeLabel: 'きゅう（電気温灸器併用）', optionLabel: '合計' },
  'fee_hari_kyu_unit': { field: 'fee_hari_kyu_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり･きゅう併用', optionLabel: '単価' },
  'fee_hari_kyu_count': { field: 'fee_hari_kyu_count', label: '回数', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり･きゅう併用', optionLabel: '回数' },
  'fee_hari_kyu_total': { field: 'fee_hari_kyu_total', label: '合計', type: 'number', compositeGroup: 'fee_hari_kyu', compositeLabel: 'はり･きゅう併用', optionLabel: '合計' },
  'fee_hari_kyu_electric_unit': { field: 'fee_hari_kyu_electric_unit', label: '単価', type: 'number', compositeGroup: 'fee_hari_kyu_electric', compositeLabel: 'はり･きゅう併用（電気鍼･電気温灸器併用）', optionLabel: '単価' },
  'fee_hari_kyu_electric_count': { field: 'fee_hari_kyu_electric_count', label: '回数', type: 'number', compositeGroup: 'fee_hari_kyu_electric', compositeLabel: 'はり･きゅう併用（電気鍼･電気温灸器併用）', optionLabel: '回数' },
  'fee_hari_kyu_electric_total': { field: 'fee_hari_kyu_electric_total', label: '合計', type: 'number', compositeGroup: 'fee_hari_kyu_electric', compositeLabel: 'はり･きゅう併用（電気鍼･電気温灸器併用）', optionLabel: '合計' },

  // === 11-2. マッサージ関連（マッサージ用PDF） ===
  'fee_massage_unit': { field: 'fee_massage_unit', label: '単価', type: 'number', compositeGroup: 'fee_massage', compositeLabel: 'マッサージ', optionLabel: '単価' },
  'fee_massage_bodypart_count': { field: 'fee_massage_bodypart_count', label: '部位数', type: 'number', compositeGroup: 'fee_massage', compositeLabel: 'マッサージ', optionLabel: '部位数' },
  'fee_massage_count': { field: 'fee_massage_count', label: '回数', type: 'number', compositeGroup: 'fee_massage', compositeLabel: 'マッサージ', optionLabel: '回数' },
  'fee_massage_total': { field: 'fee_massage_total', label: '合計', type: 'number', compositeGroup: 'fee_massage', compositeLabel: 'マッサージ', optionLabel: '合計' },
  'fee_manual_correction_unit': { field: 'fee_manual_correction_unit', label: '単価', type: 'number', compositeGroup: 'fee_manual_correction', compositeLabel: '変形徒手矯正術', optionLabel: '単価' },
  'fee_manual_correction_bodypart_count': { field: 'fee_manual_correction_bodypart_count', label: '部位数', type: 'number', compositeGroup: 'fee_manual_correction', compositeLabel: '変形徒手矯正術', optionLabel: '部位数' },
  'fee_manual_correction_count': { field: 'fee_manual_correction_count', label: '回数', type: 'number', compositeGroup: 'fee_manual_correction', compositeLabel: '変形徒手矯正術', optionLabel: '回数' },
  'fee_manual_correction_total': { field: 'fee_manual_correction_total', label: '合計', type: 'number', compositeGroup: 'fee_manual_correction', compositeLabel: '変形徒手矯正術', optionLabel: '合計' },
  'fee_fomentation_unit': { field: 'fee_fomentation_unit', label: '単価', type: 'number', compositeGroup: 'fee_fomentation', compositeLabel: '温罨法', optionLabel: '単価' },
  'fee_fomentation_count': { field: 'fee_fomentation_count', label: '回数', type: 'number', compositeGroup: 'fee_fomentation', compositeLabel: '温罨法', optionLabel: '回数' },
  'fee_fomentation_total': { field: 'fee_fomentation_total', label: '合計', type: 'number', compositeGroup: 'fee_fomentation', compositeLabel: '温罨法', optionLabel: '合計' },
  'fee_fomentation_electric_light_unit': { field: 'fee_fomentation_electric_light_unit', label: '単価', type: 'number', compositeGroup: 'fee_fomentation_electric_light', compositeLabel: '温罨法・電光線器具', optionLabel: '単価' },
  'fee_fomentation_electric_light_count': { field: 'fee_fomentation_electric_light_count', label: '回数', type: 'number', compositeGroup: 'fee_fomentation_electric_light', compositeLabel: '温罨法・電光線器具', optionLabel: '回数' },
  'fee_fomentation_electric_light_total': { field: 'fee_fomentation_electric_light_total', label: '合計', type: 'number', compositeGroup: 'fee_fomentation_electric_light', compositeLabel: '温罨法・電光線器具', optionLabel: '合計' },

  'fee_housecall_unit': { field: 'fee_housecall_unit', label: '単価', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料', optionLabel: '単価' },
  'fee_housecall_count': { field: 'fee_housecall_count', label: '回数', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料', optionLabel: '回数' },
  'fee_housecall_total': { field: 'fee_housecall_total', label: '合計', type: 'number', compositeGroup: 'fee_housecall', compositeLabel: '往療料', optionLabel: '合計' },
  'fee_housecall_additional_unit': { field: 'fee_housecall_additional_unit', label: '単価', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金', optionLabel: '単価' },
  'fee_housecall_additional_count': { field: 'fee_housecall_additional_count', label: '回数', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金', optionLabel: '回数' },
  'fee_housecall_additional_total': { field: 'fee_housecall_additional_total', label: '合計', type: 'number', compositeGroup: 'fee_housecall_additional', compositeLabel: '往療4km超料金', optionLabel: '合計' },
  'fee_initial_examination_hari': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり' },
  'fee_initial_examination_hari_electric': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり（電気鍼併用）' },
  'fee_initial_examination_kyu': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'きゅう' },
  'fee_initial_examination_kyu_electric': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'きゅう（電気温灸器併用）' },
  'fee_initial_examination_combined': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり･きゅう併用' },
  'fee_initial_examination_hari_kyu_electric': { field: 'fee_initial_examination', label: '初検料（サークル）', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用', 'はり（電気鍼併用）', 'きゅう（電気温灸器併用）', 'はり･きゅう併用（電気鍼･電気温灸器併用）'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり･きゅう併用（電気鍼･電気温灸器併用）' },
  'fee_initial_examination_amount': { field: 'fee_initial_examination_amount', label: '初検料', type: 'number' },

  'fee_subtotal': { field: 'fee_subtotal', label: '合計', type: 'number' },

  // === 11-2. 公費負担額（割合） ===
  'public_burden_ratio': { field: 'public_burden_ratio', label: '公費負担額（割合）', type: 'text' },

  'fee_public_burden_amount': { field: 'fee_public_burden_amount', label: '公費負担額', type: 'number' },

  // === 11-3. 一部負担金（サークル） ===
  'expenses_borne_ratio_10': { field: 'expenses_borne_ratio', label: '一部負担金（サークル）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '１割' },
  'expenses_borne_ratio_20': { field: 'expenses_borne_ratio', label: '一部負担金（サークル）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '２割' },
  'expenses_borne_ratio_30': { field: 'expenses_borne_ratio', label: '一部負担金（サークル）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '３割' },

  'fee_partial_payment': { field: 'fee_partial_payment', label: '一部負担金', type: 'number' },
  'fee_total_claim': { field: 'fee_total_claim', label: '請求額', type: 'number' },

  // === 13. 施術所情報 ===
  'clinic_postal_code': { field: 'clinic_postal_code', label: '施術所郵便番号', type: 'text' },
  'clinic_address': { field: 'clinic_address', label: '施術所住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_name': { field: 'clinic_name', label: '施術所名称', type: 'text' },
  'clinic_manager': { field: 'clinic_manager', label: '施術管理者氏名', type: 'text' },
  'clinic_phone': { field: 'clinic_phone', label: '電話番号', type: 'text' },
  'institution_code': { field: 'institution_code', label: '機関コード', type: 'text' },
  'therapist_registration_number': { field: 'therapist_registration_number', label: '施術者登録番号', type: 'text' },
  'health_center_registration_1': { field: 'health_center_registration', label: '保健所登録区分', type: 'select', options: ['施術所所在地', '出張専門施術者住所地'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'health_center_registration', optionLabel: '施術所所在地' },
  'health_center_registration_2': { field: 'health_center_registration', label: '保健所登録区分', type: 'select', options: ['施術所所在地', '出張専門施術者住所地'], ellipseWidth: 8, ellipseHeight: 5, lineWidth: 0.5, radioGroup: 'health_center_registration', optionLabel: '出張専門施術者住所地' },
  'license_massage_number': { field: 'license_massage_number', label: '免許番号（あん摩マッサージ指圧師）', type: 'text' },
  'license_hari_number': { field: 'license_hari_number', label: '免許番号（はり師）', type: 'text' },
  'license_kyu_number': { field: 'license_kyu_number', label: '免許番号（きゅう師）', type: 'text' },
  'therapist_postal_code': { field: 'therapist_postal_code', label: '施術者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'therapist_address': { field: 'therapist_address', label: '施術者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'therapist_name': { field: 'therapist_name', label: '施術者氏名', type: 'text' },
  'therapist_phone': { field: 'therapist_phone', label: '施術者電話番号', type: 'text' },
  'clinic_date_year': { field: 'clinic_date_year', label: '年', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },
  'clinic_date_month': { field: 'clinic_date_month', label: '月', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },
  'clinic_date_day': { field: 'clinic_date_day', label: '日', type: 'number', compositeGroup: 'clinic_date', compositeLabel: '施術証明年月日' },

  // === 14. 同意記録 ===
  'consent_record_doctor_name': { field: 'consent_record_doctor_name', label: '同意医師氏名', type: 'text' },
  'consent_record_doctor_postal_code': { field: 'consent_record_doctor_postal_code', label: '同意医師郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'consent_record_doctor_address': { field: 'consent_record_doctor_address', label: '同意医師住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'consent_record_date_year': { field: 'consent_record_date_year', label: '年', type: 'text', compositeGroup: 'consent_record_date', compositeLabel: '同意年月日' },
  'consent_record_illness_name': { field: 'consent_record_illness_name', label: '傷病名', type: 'text' },
  'required_treatment_period': { field: 'required_treatment_period', label: '要加療期間', type: 'text' },

  // === 15. 申請欄 ===
  'applicant_postal_code': { field: 'applicant_postal_code', label: '申請者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'applicant_address': { field: 'applicant_address', label: '申請者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'applicant_name': { field: 'applicant_name', label: '申請者氏名', type: 'text' },
  'patient_address': { field: 'address', label: '住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'patient_phone': { field: 'patient_phone', label: '電話番号', type: 'text' },

  // === 16. 医師情報（旧：医師情報・同意書） ===
  'consent_date': { field: 'consent_date', label: '同意年月日', type: 'date' },
  'consent_year': { field: 'consent_year', label: '同意年', type: 'number' },
  'consent_month': { field: 'consent_month', label: '同意月', type: 'number' },
  'consent_day': { field: 'consent_day', label: '同意日', type: 'number' },
  'consent_doctor_name': { field: 'consent_doctor_name', label: '同意書医師氏名', type: 'text' },
  'consent_illness_name': { field: 'consent_illness_name', label: '同意書傷病名', type: 'text' },
  'therapy_period': { field: 'therapy_period', label: '要加療期間', type: 'text' },
  'doctor_address': { field: 'doctor_address', label: '医師所在地', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'medical_institution': { field: 'medical_institution', label: '医療機関名', type: 'text' },
  'doctor_name': { field: 'doctor_name', label: '医師氏名', type: 'text' },
  'medical_institution_location_type_1': { field: 'medical_institution_location_type', label: '医療機関所在地区分', type: 'select', options: ['1', '2'], optionLabels: ['区郡市府県庁所在地', '出張所等指定都市所在地域'] },
  'medical_institution_location_type_2': { field: 'medical_institution_location_type', label: '医療機関所在地区分', type: 'select', options: ['1', '2'], optionLabels: ['区郡市府県庁所在地', '出張所等指定都市所在地域'] },

  // === 18. 支払機関欄 ===
  // 支払区分（ラジオグループ）- 療養費支給申請書用
  'payment_category_furikomi': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 6, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '振込' },
  'payment_category_bank_transfer': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 8, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '銀行送金' },
  'payment_category_post_transfer': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 10, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '郵便局送金' },
  'payment_category_local_payment': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 8, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '当地払' },
  // 支払区分（ラジオグループ）- 医療助成申請書用
  'payment_category_account_transfer': { field: 'payment_category', label: '支払区分', type: 'select', options: ['口座振替', '窓口払'], ellipseWidth: 10, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '口座振替' },
  'payment_category_counter_payment': { field: 'payment_category', label: '支払区分', type: 'select', options: ['口座振替', '窓口払'], ellipseWidth: 10, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '窓口払' },

  // 預金種別（ラジオグループ）- 共通（座標JSONのoptionsを使用）
  'deposit_type_ordinary': { field: 'deposit_type', label: '預金種類', type: 'select', ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '普通' },
  'deposit_type_current': { field: 'deposit_type', label: '預金種類', type: 'select', ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '当座' },
  // 預金種別（ラジオグループ）- 療養費支給申請書専用
  'deposit_type_notice': { field: 'deposit_type', label: '預金種類', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '通知' },
  'deposit_type_betsudan': { field: 'deposit_type', label: '預金種類', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '別段' },
  // 預金種別（ラジオグループ）- 医療助成申請書用
  'deposit_type_savings': { field: 'deposit_type', label: '預金種類', type: 'select', options: ['普通', '当座', '貯蓄', 'その他'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '貯蓄' },
  'deposit_type_other': { field: 'deposit_type', label: '預金種類', type: 'select', options: ['普通', '当座', '貯蓄', 'その他'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: 'その他' },

  // 金融機関情報
  'financial_institution_name': { field: 'financial_institution_name', label: '金融機関名', type: 'text' },
  'financial_institution_name_1': { field: 'financial_institution_name_1', label: '金融機関名1', type: 'text' },
  'financial_institution_type_bank': { field: 'financial_institution_type', label: '金融機関名１（サークル）', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '銀行' },
  'financial_institution_type_kinko': { field: 'financial_institution_type', label: '金融機関名１（サークル）', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '金庫' },
  'financial_institution_type_nokyo': { field: 'financial_institution_type', label: '金融機関名１（サークル）', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '農協' },
  'financial_institution_name_2': { field: 'financial_institution_name_2', label: '金融機関名2', type: 'text' },
  'branch_type_honten': { field: 'branch_type', label: '金融機関名２（サークル）', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '本店' },
  'branch_type_shiten': { field: 'branch_type', label: '金融機関名２（サークル）', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '支店' },
  'branch_type_shucchoujo': { field: 'branch_type', label: '金融機関名２（サークル）', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 7, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '出張所' },
  'bank_account_holder_kana': { field: 'bank_account_holder_kana', label: '口座名義', type: 'text' },
  'bank_account_number': { field: 'bank_account_number', label: '口座番号', type: 'text' },
  'insurer_name': { field: 'insurer_name', label: '申請先名称', type: 'text' },
  'submission_date_year': { field: 'submission_date_year', label: '年', type: 'number', compositeGroup: 'submission_date', compositeLabel: '申請年月日' },
  'submission_date_month': { field: 'submission_date_month', label: '月', type: 'number', compositeGroup: 'submission_date', compositeLabel: '申請年月日' },
  'submission_date_day': { field: 'submission_date_day', label: '日', type: 'number', compositeGroup: 'submission_date', compositeLabel: '申請年月日' },

  // === 19. 委任欄 ===
  'signature_applicant_postal_code': { field: 'signature_applicant_postal_code', label: '委任者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'signature_applicant_address': { field: 'signature_applicant_address', label: '委任者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'temporary_insurer_name': { field: 'temporary_insurer_name', label: '委任者氏名', type: 'text' },
  'agent_postal_code': { field: 'agent_postal_code', label: '代理人郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'agent_address': { field: 'agent_address', label: '代理人住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'agent_name': { field: 'agent_name', label: '代理人氏名', type: 'text' },
  'signature_date_year': { field: 'signature_date_year', label: '年', type: 'number', compositeGroup: 'signature_date', compositeLabel: '委任年月日' },
  'signature_date_month': { field: 'signature_date_month', label: '月', type: 'number', compositeGroup: 'signature_date', compositeLabel: '委任年月日' },
  'signature_date_day': { field: 'signature_date_day', label: '日', type: 'number', compositeGroup: 'signature_date', compositeLabel: '委任年月日' },

  // ========== 施術料金領収書専用フィールド（指示順） ==========
  // 1. 元号年月
  'title_year_month': { field: 'title_year_month', label: '元号年月', type: 'text' },
  // 2. 書類区分
  'document_type': { field: 'document_type', label: '書類区分', type: 'text' },
  // 3. 利用者氏名 → patient_name（既存）
  // 4. 性別 → patient_gender_male/female（既存）
  // 5. 年齢
  'patient_age': { field: 'patient_age', label: '年齢', type: 'number' },
  // 6. 病名 → illness_name（既存）
  // 7. 発病・負傷年月日
  'onset_date': { field: 'onset_date', label: '発病・負傷年月日', type: 'text' },
  // 8. 保険医同意年月日
  'consent_date': { field: 'consent_date', label: '保険医同意年月日', type: 'text' },
  // 9. 施術開始年月日
  'treatment_start_date': { field: 'treatment_start_date', label: '施術開始年月日', type: 'text' },
  // 10. 施術終了年月日
  'treatment_end_date': { field: 'treatment_end_date', label: '施術終了年月日', type: 'text' },
  // 11. 請求区分 → bill_category_new/continued（既存）
  // 12. 転帰 → outcome_*（既存）
  // 13. 施術の種類
  'therapy_types': { field: 'therapy_types', label: '施術の種類', type: 'text' },
  // 14. 回数
  'therapy_counts': { field: 'therapy_counts', label: '回数', type: 'number' },
  // 15. １回の料金
  'therapy_unit_prices': { field: 'therapy_unit_prices', label: '１回の料金', type: 'number' },
  // 16. 計
  'therapy_totals': { field: 'therapy_totals', label: '計', type: 'number' },
  // 17. 施術期間（開始）
  'therapy_period_start': { field: 'therapy_period_start', label: '施術期間（開始）', type: 'text' },
  // 18. 施術期間（終了）
  'therapy_period_end': { field: 'therapy_period_end', label: '施術期間（終了）', type: 'text' },
  // 19. 保険対象合計金額
  'insurance_total': { field: 'insurance_total', label: '保険対象合計金額', type: 'number' },
  // 20. 自費対象合計金額
  'self_pay_total': { field: 'self_pay_total', label: '自費対象合計金額', type: 'number' },
  // 21. 一部負担金額
  'copayment_amount': { field: 'copayment_amount', label: '一部負担金額', type: 'number' },
  // 22. 施術月 → treatment_month（既存）
  // 23. 施術日 → treatment_days（既存）
  // 24. 負担割合
  'copayment_ratio': { field: 'copayment_ratio', label: '負担割合', type: 'number' },
  // 25. 領収金額
  'receipt_amount': { field: 'receipt_amount', label: '領収金額', type: 'number' },
  // 26. 提出年月日
  'creation_date': { field: 'creation_date', label: '提出年月日', type: 'text' },
  // 27-31. 作成者情報 → clinic_*（既存）

  // === 鍼灸療養費支給申請書専用フィールド ===
  'insurance_symbol': { field: 'insurance_symbol', label: '被保険者証記号', type: 'text' },
  'calendar_start': { field: 'calendar_start', label: 'カレンダー開始位置', type: 'number' },
  'payment_institution_postal_code': { field: 'payment_institution_postal_code', label: '支払機関郵便番号', type: 'text' },
  'payment_institution_address': { field: 'payment_institution_address', label: '支払機関住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'bill_category_new': { field: 'bill_category', label: '請求区分（新規）', type: 'select', ellipseWidth: 6.5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'bill_category', optionLabel: '新規' },
  'bill_category_continued': { field: 'bill_category', label: '請求区分（継続）', type: 'select', ellipseWidth: 6.5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'bill_category', optionLabel: '継続' },
  'outcome_continued': { field: 'outcome', label: '転帰（継続）', type: 'select', ellipseWidth: 5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'outcome', optionLabel: '継続' },
  'outcome_cured': { field: 'outcome', label: '転帰（治癒）', type: 'select', ellipseWidth: 5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'outcome', optionLabel: '治癒' },
  'outcome_discontinued': { field: 'outcome', label: '転帰（中止）', type: 'select', ellipseWidth: 5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'outcome', optionLabel: '中止' },
  'outcome_transferred': { field: 'outcome', label: '転帰（転医）', type: 'select', ellipseWidth: 5, ellipseHeight: 3, lineWidth: 0.4, radioGroup: 'outcome', optionLabel: '転医' },
  'consent_date_full': { field: 'consent_date_full', label: '同意年月日', type: 'text' },

  // === 医療助成費支給申請書・同意書共通フィールド ===
  'birthday_full_date': { field: 'birthday_full_date', label: '生年月日', type: 'text' },
  'illness_name': { field: 'illness_name', label: '傷病名', type: 'text' },
  'first_treatment_date': { field: 'first_treatment_date', label: '初療年月日', type: 'text' },
  'remarks': { field: 'remarks', label: '備考', type: 'text' },
  'treatment_days_1': { field: 'treatment_days_1', label: '施術日1', type: 'number' },
  'treatment_days_2': { field: 'treatment_days_2', label: '施術日2', type: 'number' },
  'treatment_days_3': { field: 'treatment_days_3', label: '施術日3', type: 'number' },
  'treatment_days_4': { field: 'treatment_days_4', label: '施術日4', type: 'number' },
  'treatment_days_5': { field: 'treatment_days_5', label: '施術日5', type: 'number' },
  'treatment_days_6': { field: 'treatment_days_6', label: '施術日6', type: 'number' },
  'treatment_days_7': { field: 'treatment_days_7', label: '施術日7', type: 'number' },
  'treatment_days_8': { field: 'treatment_days_8', label: '施術日8', type: 'number' },
  'treatment_days_9': { field: 'treatment_days_9', label: '施術日9', type: 'number' },
  'treatment_days_10': { field: 'treatment_days_10', label: '施術日10', type: 'number' },
  'treatment_days_11': { field: 'treatment_days_11', label: '施術日11', type: 'number' },
  'treatment_days_12': { field: 'treatment_days_12', label: '施術日12', type: 'number' },
  'treatment_days_13': { field: 'treatment_days_13', label: '施術日13', type: 'number' },
  'treatment_days_14': { field: 'treatment_days_14', label: '施術日14', type: 'number' },
  'treatment_days_15': { field: 'treatment_days_15', label: '施術日15', type: 'number' },
  'treatment_days_16': { field: 'treatment_days_16', label: '施術日16', type: 'number' },
  'treatment_days_17': { field: 'treatment_days_17', label: '施術日17', type: 'number' },
  'treatment_days_18': { field: 'treatment_days_18', label: '施術日18', type: 'number' },
  'treatment_days_19': { field: 'treatment_days_19', label: '施術日19', type: 'number' },
  'treatment_days_20': { field: 'treatment_days_20', label: '施術日20', type: 'number' },
  'treatment_days_21': { field: 'treatment_days_21', label: '施術日21', type: 'number' },
  'treatment_days_22': { field: 'treatment_days_22', label: '施術日22', type: 'number' },
  'treatment_days_23': { field: 'treatment_days_23', label: '施術日23', type: 'number' },
  'treatment_days_24': { field: 'treatment_days_24', label: '施術日24', type: 'number' },
  'treatment_days_25': { field: 'treatment_days_25', label: '施術日25', type: 'number' },
  'treatment_days_26': { field: 'treatment_days_26', label: '施術日26', type: 'number' },
  'treatment_days_27': { field: 'treatment_days_27', label: '施術日27', type: 'number' },
  'treatment_days_28': { field: 'treatment_days_28', label: '施術日28', type: 'number' },
  'treatment_days_29': { field: 'treatment_days_29', label: '施術日29', type: 'number' },
  'treatment_days_30': { field: 'treatment_days_30', label: '施術日30', type: 'number' },
  'treatment_days_31': { field: 'treatment_days_31', label: '施術日31', type: 'number' },

  // === 同意書（あんま・マッサージ）専用フィールド ===
  'user_name': { field: 'user_name', label: '利用者氏名', type: 'text' },
  'user_address': { field: 'user_address', label: '利用者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'user_birthday': { field: 'user_birthday', label: '利用者生年月日', type: 'text' },
  'consent_massage_illness_name': { field: 'consent_massage_illness_name', label: '傷病名', type: 'text' },
  'consent_category': { field: 'consent_category', label: '同意区分', type: 'text' },
  'consenting_date': { field: 'consenting_date', label: '診察日', type: 'text' },
  'consenting_doctor_medical_institution_name': { field: 'consenting_doctor_medical_institution_name', label: '同意医師医療機関名', type: 'text' },
  'consenting_doctor_address': { field: 'consenting_doctor_address', label: '同意医師医療機関住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'consenting_doctor_name': { field: 'consenting_doctor_name', label: '同意医師氏名', type: 'text' },
  'submission_date': { field: 'submission_date', label: '提出年月日', type: 'text' }
};

// 施術料金領収書用のフィールド定義
const fieldDefinitionsTreatmentReceipt = {
  'title_year_month': { field: 'title_year_month', label: '元号年月', type: 'text' },
  'document_type': { field: 'document_type', label: '書類区分', type: 'text' },
  'patient_name': { field: 'last_name', label: '氏名（姓名）', type: 'text', combine: ['last_name', 'first_name'] },
  'patient_gender_male': { field: 'gender', label: '性別', type: 'select', radioGroup: 'gender', optionLabel: '男', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5 },
  'patient_gender_female': { field: 'gender', label: '性別', type: 'select', radioGroup: 'gender', optionLabel: '女', ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5 },
  'patient_age': { field: 'patient_age', label: '年齢', type: 'number' },
  'illness_name': { field: 'consent_illness_name', label: '傷病名', type: 'text' },
  'onset_date': { field: 'onset_date', label: '発病年月日', type: 'text' },
  'consent_date': { field: 'consent_date', label: '同意年月日', type: 'text' },
  'treatment_start_date': { field: 'treatment_start_date', label: '施術開始日', type: 'text' },
  'treatment_end_date': { field: 'treatment_end_date', label: '施術終了日', type: 'text' },
  'bill_category': { field: 'bill_category', label: '請求区分', type: 'text' },
  'outcome': { field: 'outcome', label: '転帰', type: 'text' },
  'therapy_types': { field: 'therapy_types', label: '施術の種類', type: 'text' },
  'therapy_counts': { field: 'therapy_counts', label: '施術回数', type: 'number' },
  'therapy_unit_prices': { field: 'therapy_unit_prices', label: '単価', type: 'number' },
  'therapy_totals': { field: 'therapy_totals', label: '計', type: 'number' },
  'therapy_period_start': { field: 'therapy_period_start', label: '施術期間（開始）', type: 'text' },
  'therapy_period_end': { field: 'therapy_period_end', label: '施術期間（終了）', type: 'text' },
  'treatment_days': { field: 'treatment_days', label: '施術日', type: 'calendar' },
  'insurance_total': { field: 'insurance_total', label: '保険請求額合計', type: 'number' },
  'copayment_amount': { field: 'copayment_amount', label: '一部負担金', type: 'number' },
  'treatment_month': { field: 'treatment_month', label: '施術月', type: 'number' },
  'copayment_ratio': { field: 'copayment_ratio', label: '負担割合', type: 'number' },
  'receipt_amount': { field: 'receipt_amount', label: '領収金額', type: 'number' },
  'creation_date': { field: 'creation_date', label: '提出年月日', type: 'text' },
  'clinic_postal_code': { field: 'clinic_postal_code', label: '郵便番号', type: 'text' },
  'clinic_address': { field: 'clinic_address', label: '住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_name': { field: 'clinic_name', label: '事業所名称', type: 'text' },
  'clinic_manager': { field: 'clinic_manager', label: '代表者氏名', type: 'text' },
  'clinic_phone': { field: 'clinic_phone', label: '電話番号', type: 'text' }
};

// ============================================================
// 同意書（はり・きゅう）用のフィールド定義
// ============================================================
// PDFタイプ: consent_acupuncture
// ============================================================
const fieldDefinitionsConsentAcupuncture = {
  'user_address': { field: 'user_address', label: '利用者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'user_name': { field: 'user_name', label: '利用者氏名', type: 'text' },
  'user_birthday': { field: 'user_birthday', label: '利用者生年月日', type: 'text' },
  'illness_name_1': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '神経痛' },
  'illness_name_2': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'リウマチ' },
  'illness_name_3': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸腕症候群' },
  'illness_name_4': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '五十肩' },
  'illness_name_5': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '腰痛症' },
  'illness_name_6': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: '頸椎捻挫後遺症' },
  'illness_name_7': { field: 'illness_name', label: '傷病名（サークル）', type: 'select', options: ['1', '2', '3', '4', '5', '6', '7'], optionLabels: ['神経痛', 'リウマチ', '頸腕症候群', '五十肩', '腰痛症', '頸椎捻挫後遺症', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'illness_name', optionLabel: 'その他' },
  'illness_name_other_text': { field: 'illness_name_other_text', label: '傷病名（その他の内容）', type: 'text' },
  'onset_date': { field: 'onset_date', label: '発病負傷年月日', type: 'text' },
  'consent_category': { field: 'consent_category', label: '同意区分', type: 'text' },
  'consenting_date': { field: 'consenting_date', label: '診察日', type: 'text' },
  'submission_date': { field: 'submission_date', label: '提出年月日', type: 'text' },
  'consenting_doctor_medical_institution_name': { field: 'consenting_doctor_medical_institution_name', label: '同意医師医療機関名', type: 'text' },
  'consenting_doctor_address': { field: 'consenting_doctor_address', label: '同意医師医療機関住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'consenting_doctor_name': { field: 'consenting_doctor_name', label: '同意医師氏名', type: 'text' }
};

// ============================================================
// 同意書（あんま・マッサージ）用のフィールド定義
// ============================================================
// PDFタイプ: consent_massage
// ============================================================
const fieldDefinitionsConsentMassage = {
  'user_address': { field: 'user_address', label: '利用者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'user_name': { field: 'user_name', label: '利用者氏名', type: 'text' },
  'user_birthday': { field: 'user_birthday', label: '利用者生年月日', type: 'text' },
  'consent_massage_illness_name': { field: 'consent_massage_illness_name', label: '傷病名', type: 'text' },
  'onset_date': { field: 'onset_date', label: '発病負傷年月日', type: 'text' },
  'consent_category': { field: 'consent_category', label: '同意区分', type: 'text' },
  'consenting_date': { field: 'consenting_date', label: '診察日', type: 'text' },

  // === 症状カテゴリ（マッサージ用） ===
  'muscle_paralysis_trunk': { field: 'muscle_paralysis', label: '筋麻痺･委縮', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'muscle_paralysis', optionLabel: '躯幹' },
  'muscle_paralysis_upper_limb_r': { field: 'muscle_paralysis', label: '筋麻痺･委縮', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'muscle_paralysis', optionLabel: '右上肢' },
  'muscle_paralysis_upper_limb_l': { field: 'muscle_paralysis', label: '筋麻痺･委縮', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'muscle_paralysis', optionLabel: '左上肢' },
  'muscle_paralysis_lower_limb_r': { field: 'muscle_paralysis', label: '筋麻痺･委縮', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'muscle_paralysis', optionLabel: '右下肢' },
  'muscle_paralysis_lower_limb_l': { field: 'muscle_paralysis', label: '筋麻痺･委縮', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'muscle_paralysis', optionLabel: '左下肢' },
  'joint_contracture_right_shoulder': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右肩' },
  'joint_contracture_right_elbow': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右肘' },
  'joint_contracture_right_wrist': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右手首' },
  'joint_contracture_right_hip': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右股関節' },
  'joint_contracture_right_knee': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右膝' },
  'joint_contracture_right_ankle': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '右足首' },
  'joint_contracture_left_shoulder': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左肩' },
  'joint_contracture_left_elbow': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左肘' },
  'joint_contracture_left_wrist': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左手首' },
  'joint_contracture_left_hip': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左股関節' },
  'joint_contracture_left_knee': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左膝' },
  'joint_contracture_left_ankle': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: '左足首' },
  'joint_contracture_other': { field: 'joint_contracture', label: '関節拘縮（サークル）', type: 'select', options: ['右肩', '右肘', '右手首', '右股関節', '右膝', '右足首', '左肩', '左肘', '左手首', '左股関節', '左膝', '左足首', 'その他'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'joint_contracture', optionLabel: 'その他' },
  'joint_contracture_other_text': { field: 'joint_contracture_other_text', label: '関節拘縮（その他の内容）', type: 'text' },
  'symptom_other': { field: 'symptom_other', label: 'その他', type: 'text' },

  // === 施術種類･部位カテゴリ（マッサージ用） ===
  'massage_trunk': { field: 'massage_bodypart', label: 'マッサージ（楕円描画）', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'massage_bodypart', optionLabel: '躯幹' },
  'massage_upper_limb_r': { field: 'massage_bodypart', label: 'マッサージ（楕円描画）', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'massage_bodypart', optionLabel: '右上肢' },
  'massage_upper_limb_l': { field: 'massage_bodypart', label: 'マッサージ（楕円描画）', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'massage_bodypart', optionLabel: '左上肢' },
  'massage_lower_limb_r': { field: 'massage_bodypart', label: 'マッサージ（楕円描画）', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'massage_bodypart', optionLabel: '右下肢' },
  'massage_lower_limb_l': { field: 'massage_bodypart', label: 'マッサージ（楕円描画）', type: 'select', options: ['躯幹', '右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'massage_bodypart', optionLabel: '左下肢' },
  'manual_correction_upper_limb_r': { field: 'manual_correction_bodypart', label: '変形徒手矯正術（楕円描画）', type: 'select', options: ['右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'manual_correction_bodypart', optionLabel: '右上肢' },
  'manual_correction_upper_limb_l': { field: 'manual_correction_bodypart', label: '変形徒手矯正術（楕円描画）', type: 'select', options: ['右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'manual_correction_bodypart', optionLabel: '左上肢' },
  'manual_correction_lower_limb_r': { field: 'manual_correction_bodypart', label: '変形徒手矯正術（楕円描画）', type: 'select', options: ['右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'manual_correction_bodypart', optionLabel: '右下肢' },
  'manual_correction_lower_limb_l': { field: 'manual_correction_bodypart', label: '変形徒手矯正術（楕円描画）', type: 'select', options: ['右上肢', '左上肢', '右下肢', '左下肢'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'manual_correction_bodypart', optionLabel: '左下肢' },

  // === 往療カテゴリ（マッサージ用） ===
  'housecall_required': { field: 'housecall_required', label: '必要有無（楕円描画）', type: 'select', options: ['必要', '不要'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'housecall_required', optionLabel: '必要' },
  'housecall_not_required': { field: 'housecall_required', label: '必要有無（楕円描画）', type: 'select', options: ['必要', '不要'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'housecall_required', optionLabel: '不要' },
  'housecall_reason_1': { field: 'housecall_reason', label: '往療必要の理由（サークル）', type: 'select', options: ['１', '２', '３'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'housecall_reason', optionLabel: '１' },
  'housecall_reason_2': { field: 'housecall_reason', label: '往療必要の理由（サークル）', type: 'select', options: ['１', '２', '３'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'housecall_reason', optionLabel: '２' },
  'housecall_reason_3': { field: 'housecall_reason', label: '往療必要の理由（サークル）', type: 'select', options: ['１', '２', '３'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'housecall_reason', optionLabel: '３' },
  'housecall_reason_other_text': { field: 'housecall_reason_other_text', label: '往療必要の理由（その他の内容）', type: 'text' },

  'submission_date': { field: 'submission_date', label: '提出年月日', type: 'text' },
  'consenting_doctor_medical_institution_name': { field: 'consenting_doctor_medical_institution_name', label: '同意医師医療機関名', type: 'text' },
  'consenting_doctor_address': { field: 'consenting_doctor_address', label: '同意医師医療機関住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'consenting_doctor_name': { field: 'consenting_doctor_name', label: '同意医師氏名', type: 'text' }
};

// ============================================================
// 同意書依頼状（サンプル版・医師指定版）はり･きゅう・マッサージ用のフィールド定義
// ============================================================
// 【重要】このフィールド定義は以下の複数のPDFタイプで共有される
// ============================================================
// 使用PDFタイプ：
// - consent_request_letter_sample_acupuncture（サンプル版はり・きゅう）
// - consent_request_letter_designated_acupuncture（医師指定版はり・きゅう）
// - consent_request_letter_sample_massage（サンプル版マッサージ）
// - consent_request_letter_designated_massage（医師指定版マッサージ）
//
// 共有理由：
// - 座標とフィールド構成が完全に同じため
// - 違いは参照する文面（document_content）と傷病名テーブルのみ
//   → サンプル版はり・きゅう：document_id_1=1, illnesses_acupuncture
//   → 医師指定版はり・きゅう：document_id_1=3, illnesses_acupuncture
//   → サンプル版マッサージ：document_id_1=2, illnesses_massage
//   → 医師指定版マッサージ：document_id_1=4, illnesses_massage
//
// 【注意】新しい同意書依頼状PDFタイプを追加する場合：
// 1. このフィールド定義を使い回す（座標が同じ場合）
// 2. coordinate-adjuster_core.jsのgetFieldDefinitions()に追加
// 3. PDFサービスクラスで適切なdocument_id_1を参照
// ============================================================
const fieldDefinitionsConsentRequestLetterSampleAcupuncture = {
  'custom_title_text': { field: 'custom_title_text', label: 'タイトル', type: 'text' },
  'submission_date': { field: 'submission_date', label: '提出年月日', type: 'text' },
  'medical_institution_name': { field: 'medical_institution_name', label: '医療機関名', type: 'text' },
  'doctor_name': { field: 'doctor_name', label: '医師氏名', type: 'text' },
  'document_content': { field: 'document_content', label: '本文', type: 'text', lineHeight: 5, maxCharsPerLine: 40 },
  'user_name': { field: 'user_name', label: '利用者氏名', type: 'text' },
  'illness_name': { field: 'illness_name', label: '傷病名', type: 'text' },
  'clinic_postal_code': { field: 'clinic_postal_code', label: '施設郵便番号', type: 'text' },
  'clinic_address': { field: 'clinic_address', label: '施設住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_phone': { field: 'clinic_phone', label: '施設電話番号', type: 'text' },
  'clinic_name': { field: 'clinic_name', label: '施設名', type: 'text' },
  'clinic_owner_name': { field: 'clinic_owner_name', label: '施設代表者氏名', type: 'text' }
};

// ============================================================
// 施術録（はり・きゅう）用のフィールド定義
// ============================================================
// PDFタイプ: treatment_record_acupuncture
// ============================================================
const fieldDefinitionsTreatmentRecordAcupuncture = {
  // === 各種番号 ===
  'locality_code': { field: 'locality_code', label: '市町村番号', type: 'text' },
  'recipient_number': { field: 'recipient_number', label: '受給者番号', type: 'text' },
  'public_funds_payer_number': { field: 'public_funds_payer_number', label: '公費負担者番号', type: 'text' },
  'public_funds_recipient_number': { field: 'public_funds_recipient_number', label: '公費受給者番号', type: 'text' },

  // === 被保険者情報 ===
  'insurance_symbol_code': { field: 'insurance_symbol_code', label: '被保険者証記号', type: 'text' },
  'insurance_symbol_number': { field: 'insurance_symbol_number', label: '被保険者証番号', type: 'text' },
  'insured_person_name': { field: 'insured_person_name', label: '被保険者氏名', type: 'text' },
  'insured_person_gender': { field: 'insured_person_gender', label: '被保険者性別', type: 'text' },
  'insured_person_birthday': { field: 'insured_person_birthday', label: '被保険者生年月日', type: 'text' },
  'insurance_valid_until': { field: 'insurance_valid_until', label: '保険有効期限', type: 'text' },
  'insured_person_postal_code': { field: 'insured_person_postal_code', label: '被保険者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'insured_person_address': { field: 'insured_person_address', label: '被保険者住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'insurance_qualification_date': { field: 'insurance_qualification_date', label: '保険資格取得年月日', type: 'text' },

  // === 利用者情報 ===
  'user_name': { field: 'user_name', label: '利用者氏名', type: 'text' },
  'user_gender': { field: 'user_gender', label: '利用者性別', type: 'text' },
  'user_birthday': { field: 'user_birthday', label: '利用者生年月日', type: 'text' },
  'user_relationship': { field: 'user_relationship', label: '利用者続柄', type: 'select', masterKey: 'relationships', valueField: 'relationship' },

  // === 事業所情報 ===
  'clinic_address': { field: 'clinic_address', label: '事業所所在地', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_name': { field: 'clinic_name', label: '事業所名称', type: 'text' },

  // === 保険者情報 ===
  'insurer_address': { field: 'insurer_address', label: '保険者所在地', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'insurer_name': { field: 'insurer_name', label: '保険者名称', type: 'text' },
  'insurer_number': { field: 'insurer_number', label: '番号', type: 'text' },

  // === 傷病･施術情報 ===
  'illness_name': { field: 'illness_name', label: '傷病名', type: 'text' },
  'onset_date': { field: 'onset_date', label: '発病年月日', type: 'text' },
  'first_treatment_date': { field: 'first_treatment_date', label: '初療年月日', type: 'text' },
  'treatment_end_date': { field: 'treatment_end_date', label: '施術終了年月日', type: 'text' },
  'treatment_days_count': { field: 'treatment_days_count', label: '日数', type: 'number' },
  'treatment_count': { field: 'treatment_count', label: '施術回数', type: 'number' },
  'outcome': { field: 'outcome', label: '転帰', type: 'text' },

  // === 同意記録 ===
  'medical_institution_name': { field: 'medical_institution_name', label: '医療機関名', type: 'text' },
  'medical_institution_address': { field: 'medical_institution_address', label: '医療機関住所', type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'medical_institution_phone': { field: 'medical_institution_phone', label: '医療機関電話番号', type: 'text' },
  'doctor_name_kana': { field: 'doctor_name_kana', label: '医師氏名カナ', type: 'text' },
  'doctor_name': { field: 'doctor_name', label: '医師氏名', type: 'text' },
  'consent_category': { field: 'consent_category', label: '同意区分', type: 'text' },
  'treatment_period': { field: 'treatment_period', label: '施術期間', type: 'text' },
  'onset_cause': { field: 'onset_cause', label: '発病原因', type: 'text' },

};

// ============================================================
// 総括表専用フィールド定義
// ============================================================
const fieldDefinitionsSummaryTable = {
  // === ヘッダー情報 ===
  'submission_date':    { field: 'submission_date',    label: '提出年月日',         type: 'text', category: 'header_info' },
  'service_year_month': { field: 'service_year_month', label: 'サービス提供年月',   type: 'text', category: 'header_info' },
  'therapy_category':   { field: 'therapy_category',   label: '施術カテゴリ',       type: 'text', category: 'header_info' },
  'insurer_name':       { field: 'insurer_name',       label: '保険機関名',         type: 'text', category: 'header_info' },

  // === 事業所情報 ===
  'clinic_postal_code': { field: 'clinic_postal_code', label: '事業所郵便番号',     type: 'text', category: 'clinic_info' },
  'clinic_address':     { field: 'clinic_address',     label: '事業所住所',         type: 'text', category: 'clinic_info', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_name':        { field: 'clinic_name',        label: '事業所名',           type: 'text', category: 'clinic_info' },
  'clinic_owner_name':  { field: 'clinic_owner_name',  label: '事業所代表者氏名',   type: 'text', category: 'clinic_info' },
  'clinic_phone':       { field: 'clinic_phone',       label: '事業所電話番号',     type: 'text', category: 'clinic_info' },

  // === 集計情報 ===
  'benefit_ratio':        { field: 'benefit_ratio',        label: '支給割合区分（1行目）', type: 'text', category: 'cost_summary', rowLineHeight: 7 },
  'treatment_count':      { field: 'treatment_count',      label: '施術件数（1行目）',     type: 'text', category: 'cost_summary' },
  'cost_amount':          { field: 'cost_amount',          label: '費用額（1行目）',       type: 'text', category: 'cost_summary' },
  'claim_amount':         { field: 'claim_amount',         label: '申請額（1行目）',       type: 'text', category: 'cost_summary' },
  'total_treatment_count': { field: 'total_treatment_count', label: '合計（件数）',        type: 'text', category: 'cost_summary' },
  'total_cost_amount':     { field: 'total_cost_amount',     label: '合計（費用額）',      type: 'text', category: 'cost_summary' },
  'total_claim_amount':    { field: 'total_claim_amount',    label: '合計（申請額）',      type: 'text', category: 'cost_summary' },

  // === 金融機関情報 ===
  'bank_name':           { field: 'bank_name',           label: '金融機関名（コード）', type: 'text', category: 'bank_info' },
  'bank_branch_name':    { field: 'bank_branch_name',    label: '支店名（コード）',    type: 'text', category: 'bank_info' },
  'bank_account_type':   { field: 'bank_account_type',   label: '預金種別',           type: 'text', category: 'bank_info' },
  'bank_account_number': { field: 'bank_account_number', label: '口座番号',           type: 'text', category: 'bank_info' },
  'bank_account_name':   { field: 'bank_account_name',   label: '口座名義',           type: 'text', category: 'bank_info' },
};

// ============================================================
// 委任状（申請･受領）専用フィールド定義
// ============================================================
const fieldDefinitionsPowerOfAttorneyApplication = {
  'clinic_address':       { field: 'clinic_address',       label: '事業所住所',       type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_owner_name':    { field: 'clinic_owner_name',    label: '代表者氏名',       type: 'text' },
  'clinic_owner_birthday': { field: 'clinic_owner_birthday', label: '代表者生年月日', type: 'text' },
};

// ============================================================
// 委任状（同意書取得）専用フィールド定義
// ============================================================
const fieldDefinitionsPowerOfAttorneyConsent = {
  'clinic_address':       { field: 'clinic_address',       label: '事業所住所',       type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_owner_name':    { field: 'clinic_owner_name',    label: '代表者氏名',       type: 'text' },
  'clinic_owner_birthday': { field: 'clinic_owner_birthday', label: '代表者生年月日', type: 'text' },
};

// ============================================================
// 実施計画書用のフィールド定義
// ============================================================
// PDFタイプ: implementation_plan
// ============================================================
const fieldDefinitionsImplementationPlan = {
  // === グループ１: 基本情報 ===
  'ip_assessment_date':                { field: 'ip_assessment_date',                label: '評価日',              type: 'text' },
  'ip_patient_name':                   { field: 'ip_patient_name',                   label: '利用者氏名',          type: 'text' },
  'ip_gender':                         { field: 'ip_gender',                         label: '性別',                type: 'text' },
  'ip_birthdate':                      { field: 'ip_birthdate',                      label: '生年月日',            type: 'text' },
  'ip_illness_name':                   { field: 'ip_illness_name',                   label: '傷病名',              type: 'text' },

  // === グループ２: ADL評価値（行間調整あり・全10項目を縦列挙） ===
  'ip_adl_level':                      { field: 'ip_adl_level',                      label: 'ADL評価値',               type: 'text', rowLineHeight: 7 },

  // === グループ２: ADL備考（行間はADL評価値と自動同期） ===
  'ip_adl_note':                       { field: 'ip_adl_note',                       label: 'ADL備考',               type: 'text', rowLineHeight: 7 },

  // === グループ２: ADL合計値 ===
  'ip_adl_total':                      { field: 'ip_adl_total',                      label: 'ADL合計値',                type: 'text' },

  // === グループ２: コミュニケーション ===
  'ip_communication_note':             { field: 'ip_communication_note',             label: 'コミュニケーション',       type: 'text', maxCharsPerLine: 30, lineHeight: 5 },

  // === グループ３: 計画情報（maxCharsPerLine調整あり） ===
  'ip_wish_of_user_and_family':        { field: 'ip_wish_of_user_and_family',        label: '本人・家族の希望',         type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_care_purpose':                   { field: 'ip_care_purpose',                   label: '治療目的',                 type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_rehabilitation_program':         { field: 'ip_rehabilitation_program',         label: 'リハビリプログラム',       type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_home_rehabilitation':            { field: 'ip_home_rehabilitation',            label: '自主訓練',                 type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_change_since_previous_planning': { field: 'ip_change_since_previous_planning', label: '前回計画からの変化',       type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_note':                           { field: 'ip_note',                           label: '注意事項',                 type: 'text', maxCharsPerLine: 30, lineHeight: 5 },
  'ip_assessor':                       { field: 'ip_assessor',                       label: '評価者',                   type: 'text' },

  // === グループ４: 事業所情報 ===
  'ip_clinic_postal_code':             { field: 'ip_clinic_postal_code',             label: '事業所郵便番号',           type: 'text' },
  'ip_clinic_address':                 { field: 'ip_clinic_address',                 label: '事業所住所',               type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'ip_clinic_phone':                   { field: 'ip_clinic_phone',                   label: '事業所電話番号',           type: 'text' },
  'ip_clinic_name':                    { field: 'ip_clinic_name',                    label: '事業所名',                 type: 'text' },
  'ip_clinic_owner_name':              { field: 'ip_clinic_owner_name',              label: '事業所代表者氏名',         type: 'text' },
};

// ============================================================
// 医師への御礼状用のフィールド定義
// ============================================================
// PDFタイプ: thank_you_letter_doctor
// ============================================================
const fieldDefinitionsThankYouLetterDoctor = {
  'custom_title_text':        { field: 'custom_title_text',        label: 'タイトル',         type: 'text' },
  'submission_date':          { field: 'submission_date',          label: '提出年月日',       type: 'text' },
  'medical_institution_name': { field: 'medical_institution_name', label: '医療機関名',       type: 'text' },
  'doctor_name':              { field: 'doctor_name',              label: '医師氏名',         type: 'text' },
  'document_content':         { field: 'document_content',         label: '本文',             type: 'text', lineHeight: 5, maxCharsPerLine: 40 },
  'user_name':                { field: 'user_name',                label: '利用者氏名',       type: 'text' },
  'illness_name':             { field: 'illness_name',             label: '傷病名',           type: 'text' },
  'clinic_postal_code':       { field: 'clinic_postal_code',       label: '施設郵便番号',     type: 'text' },
  'clinic_address':           { field: 'clinic_address',           label: '施設住所',         type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_phone':             { field: 'clinic_phone',             label: '施設電話番号',     type: 'text' },
  'clinic_name':              { field: 'clinic_name',              label: '施設名',           type: 'text' },
  'clinic_owner_name':        { field: 'clinic_owner_name',        label: '施設代表者氏名',   type: 'text' },
};

// ============================================================
// 紹介者への御礼状用のフィールド定義
// ============================================================
// PDFタイプ: thank_you_letter_referrer
// ============================================================
const fieldDefinitionsThankYouLetterReferrer = {
  'custom_title_text':      { field: 'custom_title_text',      label: 'タイトル',         type: 'text' },
  'submission_date':        { field: 'submission_date',        label: '提出年月日',       type: 'text' },
  'service_provider_name':  { field: 'service_provider_name',  label: '事業所名',         type: 'text' },
  'caremanager_name':       { field: 'caremanager_name',       label: 'ケアマネ氏名',     type: 'text' },
  'document_content':       { field: 'document_content',       label: '本文',             type: 'text', lineHeight: 5, maxCharsPerLine: 40 },
  'user_name':              { field: 'user_name',              label: '利用者氏名',       type: 'text' },
  'illness_name':           { field: 'illness_name',           label: '傷病名',           type: 'text' },
  'clinic_postal_code':     { field: 'clinic_postal_code',     label: '施設郵便番号',     type: 'text' },
  'clinic_address':         { field: 'clinic_address',         label: '施設住所',         type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_phone':           { field: 'clinic_phone',           label: '施設電話番号',     type: 'text' },
  'clinic_name':            { field: 'clinic_name',            label: '施設名',           type: 'text' },
  'clinic_owner_name':      { field: 'clinic_owner_name',      label: '施設代表者氏名',   type: 'text' },
};

// ============================================================
// FAX送信票用のフィールド定義
// ============================================================
// PDFタイプ: fax_cover_sheet
// ============================================================
const fieldDefinitionsFaxCoverSheet = {
  'clinic_postal_code':{ field: 'clinic_postal_code', label: '事業所郵便番号',   type: 'text' },
  'clinic_address':    { field: 'clinic_address',     label: '事業所住所',       type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_phone':      { field: 'clinic_phone',       label: '事業所電話番号',   type: 'text' },
  'clinic_name':       { field: 'clinic_name',        label: '事業所名',         type: 'text' },
  'clinic_owner_name': { field: 'clinic_owner_name',  label: '事業所代表者氏名', type: 'text' },
};

// ============================================================
// 報告書用のフィールド定義
// ============================================================
// PDFタイプ: report
// ============================================================
const fieldDefinitionsReport = {
  'submission_date':             { field: 'report_submission_date',      label: '提出年月日',       type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
  'greeting_text':               { field: 'greeting_text',               label: '利用者氏名挨拶文', type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
  'subjective_symptom_and_wish': { field: 'subjective_symptom_and_wish', label: '自覚症状・希望',   type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
  'objective_symptom':           { field: 'objective_symptom',           label: '客観症状',       type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
  'therapy_content':             { field: 'therapy_content',             label: '施術内容',       type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
  'therapy_plan':                { field: 'therapy_plan',                label: '治療計画',       type: 'text', maxCharsPerLine: 38, lineHeight: 6 },
};

// 報告書挨拶状用のフィールド定義
// ============================================================
// PDFタイプ: report_greeting
// ============================================================
const fieldDefinitionsReportGreeting = {
  'custom_title_text':        { field: 'custom_title_text',        label: 'タイトル',         type: 'text' },
  'submission_date':          { field: 'submission_date',          label: '提出年月日',       type: 'text' },
  'medical_institution_name': { field: 'medical_institution_name', label: '医療機関名（医師向け）',       type: 'text' },
  'doctor_name':              { field: 'doctor_name',              label: '医師氏名（医師向け）',         type: 'text' },
  'service_provider_name':    { field: 'service_provider_name',    label: '事業所名（ケアマネ向け）',       type: 'text' },
  'caremanager_name':         { field: 'caremanager_name',         label: 'ケアマネ氏名（ケアマネ向け）',     type: 'text' },
  'document_content':         { field: 'document_content',         label: '本文',             type: 'text', lineHeight: 5, maxCharsPerLine: 40 },
  'user_name':                { field: 'user_name',                label: '利用者氏名',       type: 'text' },
  'illness_name':             { field: 'illness_name',             label: '傷病名',           type: 'text' },
  'clinic_postal_code':       { field: 'clinic_postal_code',       label: '施設郵便番号',     type: 'text' },
  'clinic_address':           { field: 'clinic_address',           label: '施設住所',         type: 'text', lineHeight: 5, maxCharsPerLine: 20, verticalAlign: 'middle' },
  'clinic_phone':             { field: 'clinic_phone',             label: '施設電話番号',     type: 'text' },
  'clinic_name':              { field: 'clinic_name',             label: '施設名',           type: 'text' },
  'clinic_owner_name':        { field: 'clinic_owner_name',        label: '施設代表者氏名',   type: 'text' },
};
