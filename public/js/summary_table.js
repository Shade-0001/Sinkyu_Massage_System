// public/js/summary_table.js
// 総括表（summary_table）フィールド定義
// coordinate-adjuster_fields.js の fieldDefinitions に追記される形で使用

// ============================================================
// 総括表フィールド定義
// ============================================================
// フィールドキー → サンプルデータ値のマッピング
// coordinate-adjuster_data.js から参照される

const summaryTableFieldDefinitions = {

  // === 提出年月日 ===
  'submission_date': {
    field: 'submission_date',
    label: '提出年月日',
    type: 'text',
    category: 'header_info'
  },

  // === サービス提供年月 ===
  'service_year_month': {
    field: 'service_year_month',
    label: 'サービス提供年月',
    type: 'text',
    category: 'header_info'
  },

  // === 施術カテゴリ ===
  'therapy_category': {
    field: 'therapy_category',
    label: '施術カテゴリ',
    type: 'text',
    category: 'header_info'
  },

  // === 保険機関名 ===
  'insurer_name': {
    field: 'insurer_name',
    label: '保険機関名',
    type: 'text',
    category: 'header_info'
  },

  // === 事業所情報 ===
  'clinic_postal_code': {
    field: 'clinic_postal_code',
    label: '事業所郵便番号',
    type: 'text',
    category: 'clinic_info'
  },

  'clinic_address': {
    field: 'clinic_address',
    label: '事業所住所',
    type: 'text',
    category: 'clinic_info'
  },

  'clinic_name': {
    field: 'clinic_name',
    label: '事業所名',
    type: 'text',
    category: 'clinic_info'
  },

  'clinic_owner_name': {
    field: 'clinic_owner_name',
    label: '事業所代表者氏名',
    type: 'text',
    category: 'clinic_info'
  },

  'clinic_phone': {
    field: 'clinic_phone',
    label: '事業所電話番号',
    type: 'text',
    category: 'clinic_info'
  },

  // === 集計情報（複数行） ===
  'benefit_ratio': {
    field: 'benefit_ratio',
    label: '支給割合区分（1行目）',
    type: 'text',
    category: 'cost_summary'
  },

  'treatment_count': {
    field: 'treatment_count',
    label: '施術件数（1行目）',
    type: 'text',
    category: 'cost_summary'
  },

  'cost_amount': {
    field: 'cost_amount',
    label: '費用額（1行目）',
    type: 'text',
    category: 'cost_summary'
  },

  'claim_amount': {
    field: 'claim_amount',
    label: '申請額（1行目）',
    type: 'text',
    category: 'cost_summary'
  },

  // === 行間設定（複数行の行間制御） ===
  'benefit_ratio_row_line_height': {
    field: 'benefit_ratio_row_line_height',
    label: '支給割合区分 行間（mm）',
    type: 'number',
    category: 'cost_summary'
  },

  // === 金融機関情報 ===
  'bank_name': {
    field: 'bank_name',
    label: '金融機関名（コード）',
    type: 'text',
    category: 'bank_info'
  },

  'bank_branch_name': {
    field: 'bank_branch_name',
    label: '支店名（コード）',
    type: 'text',
    category: 'bank_info'
  },

  'bank_account_type': {
    field: 'bank_account_type',
    label: '預金種別',
    type: 'text',
    category: 'bank_info'
  },

  'bank_account_number': {
    field: 'bank_account_number',
    label: '口座番号',
    type: 'text',
    category: 'bank_info'
  },

  'bank_account_name': {
    field: 'bank_account_name',
    label: '口座名義',
    type: 'text',
    category: 'bank_info'
  },
};

// カテゴリ定義
const fieldCategoriesSummaryTable = {
  // ヘッダー情報
  'submission_date':    'header_info',
  'service_year_month': 'header_info',
  'therapy_category':   'header_info',
  'insurer_name':       'header_info',

  // 事業所情報
  'clinic_postal_code': 'clinic_info',
  'clinic_address':     'clinic_info',
  'clinic_name':        'clinic_info',
  'clinic_owner_name':  'clinic_info',
  'clinic_phone':       'clinic_info',

  // 集計情報
  'benefit_ratio':                  'cost_summary',
  'treatment_count':                'cost_summary',
  'cost_amount':                    'cost_summary',
  'claim_amount':                   'cost_summary',
  'benefit_ratio_row_line_height':  'cost_summary',

  // 金融機関情報
  'bank_name':           'bank_info',
  'bank_branch_name':    'bank_info',
  'bank_account_type':   'bank_info',
  'bank_account_number': 'bank_info',
  'bank_account_name':   'bank_info',
};

// カテゴリ表示順
const categoryOrderSummaryTable = [
  'header_info',
  'clinic_info',
  'cost_summary',
  'bank_info',
];

// カテゴリラベル
const categoryLabelsSummaryTable = {
  'header_info':  'ヘッダー情報',
  'clinic_info':  '事業所情報',
  'cost_summary': '集計情報',
  'bank_info':    '金融機関情報',
};
