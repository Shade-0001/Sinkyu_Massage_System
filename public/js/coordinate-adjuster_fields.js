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

  // === 4. 保険者番号 ===
  'insurer_number': { field: 'insurer_number', label: '保険者番号', type: 'text' },

  // === 5. 被保険者証記号・番号、発病年月日、傷病名 ===
  'insurance_symbol': { field: 'insurance_symbol_kigou', label: '被保険者証等記号番号', type: 'text', combine: ['insurance_symbol_kigou', 'insurance_symbol_bangou'] },
  'insurance_number': { field: 'insurance_number', label: '被保険者番号', type: 'text' },
  'onset_date_year': { field: 'onset_date_year', label: '発病または負傷年月日（年）', type: 'number' },
  'onset_date_month': { field: 'onset_date_month', label: '発病または負傷年月日（月）', type: 'number' },
  'onset_date_day': { field: 'onset_date_day', label: '発病または負傷年月日（日）', type: 'number' },
  'onset_illness_name': { field: 'onset_illness_name', label: '傷病名（発病または負傷年月日の隣）', type: 'text' },

  // === 6. 患者氏名カナ・続柄・氏名 ===
  'patient_last_kana': { field: 'last_kana', label: '氏名カナ（姓）', type: 'text' },
  'patient_first_kana': { field: 'first_kana', label: '氏名カナ（名）', type: 'text' },
  'patient_name_kana': { field: 'last_kana', label: '氏名カナ（姓名）', type: 'text', combine: ['last_kana', 'first_kana'] },
  'patient_relationship': { field: 'relationship', label: '続柄', type: 'select', masterKey: 'relationships', valueField: 'relationship' },
  'patient_last_name': { field: 'last_name', label: '氏名（姓）', type: 'text' },
  'patient_first_name': { field: 'first_name', label: '氏名（名）', type: 'text' },
  'patient_name': { field: 'last_name', label: '氏名（姓名）', type: 'text', combine: ['last_name', 'first_name'] },

  // === 7. 性別・業務上第三者行為・生年月日 ===
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

  // === 8. 初療年月日・施術期間 ===
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

  // === 9. 実日数・請求区分・傷病名・転帰 ===
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

  // 施術月
  'treatment_month': { field: 'treatment_month', label: '施術月', type: 'number' },

  // === 10. 施術日カレンダー（1-31日） ===
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

  // === 11. 施術料金（鍼灸） ===
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
  'fee_initial_examination_hari': { field: 'fee_initial_examination', label: '初検料', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり' },
  'fee_initial_examination_kyu': { field: 'fee_initial_examination', label: '初検料', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'きゅう' },
  'fee_initial_examination_combined': { field: 'fee_initial_examination', label: '初検料', type: 'select', options: ['はり', 'きゅう', 'はり･きゅう併用'], ellipseWidth: 6, ellipseHeight: 3, lineWidth: 0.5, radioGroup: 'fee_initial_examination', optionLabel: 'はり･きゅう併用' },
  'fee_subtotal': { field: 'fee_subtotal', label: '合計', type: 'number' },

  // === 11-2. 一部負担金（楕円） ===
  'expenses_borne_ratio_10': { field: 'expenses_borne_ratio', label: '一部負担金（楕円）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '１割' },
  'expenses_borne_ratio_20': { field: 'expenses_borne_ratio', label: '一部負担金（楕円）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '２割' },
  'expenses_borne_ratio_30': { field: 'expenses_borne_ratio', label: '一部負担金（楕円）', type: 'select', options: ['１割', '２割', '３割'], ellipseWidth: 2.5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'expenses_borne_ratio', optionLabel: '３割' },

  'fee_partial_payment': { field: 'fee_partial_payment', label: '一部負担金', type: 'number' },
  'fee_total_claim': { field: 'fee_total_claim', label: '請求額', type: 'number' },

  // === 12. 施術料金（マッサージ） ===
  'fee_massage_unit': { field: 'fee_massage_unit', label: 'マッサージ料金（単価）', type: 'number' },
  'fee_massage_count': { field: 'fee_massage_count', label: 'マッサージ料金（回数）', type: 'number' },
  'fee_massage_total': { field: 'fee_massage_total', label: 'マッサージ料金（合計）', type: 'number' },

  // === 13. 施術所情報 ===
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

  // === 14. 申請者情報 ===
  'applicant_postal_code': { field: 'applicant_postal_code', label: '申請者郵便番号', type: 'postal_code', postalCodeGap: 2 },
  'applicant_address': { field: 'address', label: '申請者住所', type: 'text' },
  'applicant_name': { field: 'last_name', label: '申請者氏名', type: 'text', combine: ['last_name', 'first_name'] },
  'patient_address': { field: 'address', label: '住所', type: 'text' },
  'patient_phone': { field: 'phone', label: '電話番号', type: 'text' },
  'office_name': { field: 'office_name', label: '事業所名称', type: 'text' },


  // 同意記録
  'consent_record_doctor_name': { field: 'consent_record_doctor_name', label: '同意医師氏名（同意記録）', type: 'text' },
  'consent_record_doctor_address': { field: 'consent_record_doctor_address', label: '同意医師住所（同意記録）', type: 'text' },
  'consent_record_date_year': { field: 'consent_record_date_year', label: '同意年月日（年）', type: 'number' },
  'consent_record_date_month': { field: 'consent_record_date_month', label: '同意年月日（月）', type: 'number' },
  'consent_record_date_day': { field: 'consent_record_date_day', label: '同意年月日（日）', type: 'number' },
  'consent_record_illness_name': { field: 'consent_record_illness_name', label: '傷病名（同意記録）', type: 'text' },
  'required_treatment_period': { field: 'required_treatment_period', label: '要加療期間', type: 'text' },

  // === 15. 医師情報・同意書 ===
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

  // === 16. 振込口座情報 ===
  'bank_name': { field: 'bank_name', label: '銀行名', type: 'text' },
  'branch_name': { field: 'branch_name', label: '支店名', type: 'text' },
  'account_type': { field: 'account_type', label: '口座種別', type: 'select', options: ['普通', '当座'] },
  'account_number': { field: 'account_number', label: '口座番号', type: 'text' },
  'account_holder': { field: 'account_holder', label: '口座名義', type: 'text' },

  // === 17. 代理人情報 ===
  'agent_postal_code': { field: 'agent_postal_code', label: '代理人郵便番号', type: 'text' },
  'agent_address': { field: 'agent_address', label: '代理人住所', type: 'text' },
  'agent_name': { field: 'agent_name', label: '代理人氏名', type: 'text' },

  // === 18. 支払機関欄 ===
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


  // 支払区分（ラジオグループ）
  'payment_category_furikomi': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 6, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '振込' },
  'payment_category_bank_transfer': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 8, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '銀行送金' },
  'payment_category_post_transfer': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 10, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '郵便局送金' },
  'payment_category_local_payment': { field: 'payment_category', label: '支払区分', type: 'select', options: ['振込', '銀行送金', '郵便局送金', '当地払'], ellipseWidth: 8, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'payment_category', optionLabel: '当地払' },

  // 預金種別（ラジオグループ）
  'deposit_type_ordinary': { field: 'deposit_type', label: '預金種別', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '普通' },
  'deposit_type_current': { field: 'deposit_type', label: '預金種別', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '当座' },
  'deposit_type_notice': { field: 'deposit_type', label: '預金種別', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '通知' },
  'deposit_type_betsudan': { field: 'deposit_type', label: '預金種別', type: 'select', options: ['普通', '当座', '通知', '別段'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'deposit_type', optionLabel: '別段' },

  // 金融機関情報
  'financial_institution_name_1': { field: 'financial_institution_name_1', label: '金融機関名1', type: 'text' },
  'financial_institution_type_bank': { field: 'financial_institution_type', label: '金融機関種別', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '銀行' },
  'financial_institution_type_kinko': { field: 'financial_institution_type', label: '金融機関種別', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '金庫' },
  'financial_institution_type_nokyo': { field: 'financial_institution_type', label: '金融機関種別', type: 'select', options: ['銀行', '金庫', '農協'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'financial_institution_type', optionLabel: '農協' },
  'financial_institution_name_2': { field: 'financial_institution_name_2', label: '金融機関名2', type: 'text' },
  'branch_type_honten': { field: 'branch_type', label: '支店種別', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '本店' },
  'branch_type_shiten': { field: 'branch_type', label: '支店種別', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 5, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '支店' },
  'branch_type_shucchoujo': { field: 'branch_type', label: '支店種別', type: 'select', options: ['本店', '支店', '出張所'], ellipseWidth: 7, ellipseHeight: 2.5, lineWidth: 0.5, radioGroup: 'branch_type', optionLabel: '出張所' },
  'bank_account_holder_kana': { field: 'bank_account_holder_kana', label: '口座名義', type: 'text' },
  'bank_account_number': { field: 'bank_account_number', label: '口座番号', type: 'text' },
  'insurer_name': { field: 'insurer_name', label: '申請先名称', type: 'text' },

  // === 19. 被保険者情報 ===
  'temporary_insurer_name': { field: 'temporary_insurer_name', label: '被保険者氏名', type: 'text' },

  // === 20. その他 ===
  'claim_number': { field: 'claim_number', label: '請求書番号', type: 'text' },
  'treatment_year_month': { field: 'treatment_year_month', label: '施術年月', type: 'text' },

  // === 21. 署名・申請情報 ===
  // 年月日（署名）
  'signature_date_year': { field: 'signature_date_year', label: '年月日（署名・年）', type: 'number' },
  'signature_date_month': { field: 'signature_date_month', label: '年月日（署名・月）', type: 'number' },
  'signature_date_day': { field: 'signature_date_day', label: '年月日（署名・日）', type: 'number' },
  'signature_applicant_address': { field: 'signature_applicant_address', label: '申請者住所（署名）', type: 'text' },

  // 申請年月日
  'submission_date_year': { field: 'submission_date_year', label: '申請年月日（年）', type: 'number' },
  'submission_date_month': { field: 'submission_date_month', label: '申請年月日（月）', type: 'number' },
  'submission_date_day': { field: 'submission_date_day', label: '申請年月日（日）', type: 'number' }
};
