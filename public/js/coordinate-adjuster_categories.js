// ============================================================
// 【重要】座標調整ツールにフィールドを表示するためのカテゴリ定義
// ============================================================
// このファイルは座標調整ツールのUI表示に必須。
// 新しいフィールドを追加した際は、必ずここにカテゴリを割り当てること。
//
// カテゴリ例：
// - basic_info: 基本情報
// - treatment_content: 施術内容
// - treatment_fees: 料金関連
// - treatment_certification: 施術証明
// - application: 申請欄
// - payment_institution: 支払機関欄
// - signature: 委任欄
// - consent_record: 同意記録
//
// ※ここに登録しないとUIに表示されない
//
// 【開発時の注意】
// 1. フィールドがUIに表示されない → このファイルに登録されているか確認
// 2. PDFにテキストが描画されない → PHP側の実装を確認（UIとPDFは別問題）
// 3. フィールド名はcoordinates.jsonと完全一致させること
// 4. 複数のPDF種類で同じフィールドを使う場合、各マッピングに追加が必要
// ============================================================
//
// ============================================================
// 【重要】フィールド表示順序の決定メカニズム
// ============================================================
// カテゴリ内でのフィールド表示順序は、このファイルではなく
// coordinate-adjuster_fields.js の fieldDefinitions の定義順で決まる。
//
// 【誤解】このファイルの順序を変えれば表示順が変わる
// 【正解】fieldDefinitions の定義順が表示順を決定する
//
// 【このファイルの役割】
// - 各フィールドがどのカテゴリに属するかを決定（分類のみ）
// - カテゴリ内での並び順は制御しない
//
// 【表示順序を変更する手順】
// 1. coordinate-adjuster_fields.js で定義順を変更（必須）
// 2. このファイルでカテゴリマッピングを更新（必須）
//
// ※両方の変更が必要。片方だけでは不十分
// ============================================================

// フィールドカテゴリマッピング（鍼灸療養費支給申請書用）
const fieldCategoriesTherapyBenefitAcupuncture = {
    "title_year_era": "basic_info",
    "title_year_number": "basic_info",
    "title_month": "basic_info",
    "institution_code": "basic_info",
    "public_funds_payer_number": "basic_info",
    "public_funds_recipient_number": "basic_info",
    "locality_code": "basic_info",
    "recipient_number": "basic_info",
    "insurance_type_1_shakoku": "basic_info",
    "insurance_type_1_kouhi": "basic_info",
    "insurance_type_1_kouki": "basic_info",
    "insurance_type_1_taishoku": "basic_info",
    "insurance_type_3_hongai": "basic_info",
    "insurance_type_3_sangai": "basic_info",
    "insurance_type_3_kagai": "basic_info",
    "insurance_type_3_kougai9": "basic_info",
    "insurance_type_3_kougai8": "basic_info",
    "benefit_ratio_80": "basic_info",
    "benefit_ratio_90": "basic_info",
    "benefit_ratio_100": "basic_info",
    "insurer_number": "basic_info",
    "insurance_symbol_code": "insured_person",
    "insurance_symbol_number": "insured_person",
    "onset_date_year": "insured_person",
    "onset_date_month": "insured_person",
    "onset_date_day": "insured_person",
    "patient_last_kana": "insured_person",
    "patient_first_kana": "insured_person",
    "patient_name_kana": "insured_person",
    "patient_relationship": "insured_person",
    "patient_last_name": "insured_person",
    "patient_first_name": "insured_person",
    "patient_name": "insured_person",
    "patient_gender_male": "insured_person",
    "patient_gender_female": "insured_person",
    "work_scope_type_1": "insured_person",
    "work_scope_type_2": "insured_person",
    "work_scope_type_3": "insured_person",
    "birthday_era_meiji": "insured_person",
    "birthday_era_taisho": "insured_person",
    "birthday_era_showa": "insured_person",
    "birthday_era_heisei": "insured_person",
    "birthday_full_date": "insured_person",
    "onset_illness_name": "insured_person",
    "patient_postal_code": "insured_person",
    "patient_address": "insured_person",
    "first_treatment_year": "treatment_content",
    "first_treatment_month": "treatment_content",
    "first_treatment_day": "treatment_content",
    "treatment_start_year": "treatment_content",
    "treatment_start_month": "treatment_content",
    "treatment_start_day": "treatment_content",
    "treatment_end_year": "treatment_content",
    "treatment_end_month": "treatment_content",
    "treatment_end_day": "treatment_content",
    "treatment_days": "treatment_content",
    "treatment_day_count": "treatment_content",
    "bill_category": "treatment_content",
    "illness_name_1": "treatment_content",
    "illness_name_2": "treatment_content",
    "illness_name_3": "treatment_content",
    "illness_name_4": "treatment_content",
    "illness_name_5": "treatment_content",
    "illness_name_6": "treatment_content",
    "illness_name_7": "treatment_content",
    "illness_name_other_text": "treatment_content",
    "outcome": "treatment_content",
    "condition": "insured_person",
    "abstract": "treatment_content",
    "treatment_month": "treatment_content",
    "treatment_day_1": "treatment_calendar",
    "treatment_day_2": "treatment_calendar",
    "treatment_day_3": "treatment_calendar",
    "treatment_day_4": "treatment_calendar",
    "treatment_day_5": "treatment_calendar",
    "treatment_day_6": "treatment_calendar",
    "treatment_day_7": "treatment_calendar",
    "treatment_day_8": "treatment_calendar",
    "treatment_day_9": "treatment_calendar",
    "treatment_day_10": "treatment_calendar",
    "treatment_day_11": "treatment_calendar",
    "treatment_day_12": "treatment_calendar",
    "treatment_day_13": "treatment_calendar",
    "treatment_day_14": "treatment_calendar",
    "treatment_day_15": "treatment_calendar",
    "treatment_day_16": "treatment_calendar",
    "treatment_day_17": "treatment_calendar",
    "treatment_day_18": "treatment_calendar",
    "treatment_day_19": "treatment_calendar",
    "treatment_day_20": "treatment_calendar",
    "treatment_day_21": "treatment_calendar",
    "treatment_day_22": "treatment_calendar",
    "treatment_day_23": "treatment_calendar",
    "treatment_day_24": "treatment_calendar",
    "treatment_day_25": "treatment_calendar",
    "treatment_day_26": "treatment_calendar",
    "treatment_day_27": "treatment_calendar",
    "treatment_day_28": "treatment_calendar",
    "treatment_day_29": "treatment_calendar",
    "treatment_day_30": "treatment_calendar",
    "treatment_day_31": "treatment_calendar",
    "calendar_note": "treatment_calendar",
    "fee_initial_examination_hari": "treatment_fees",
    "fee_initial_examination_kyu": "treatment_fees",
    "fee_initial_examination_combined": "treatment_fees",
    "fee_initial_examination_amount": "treatment_fees",
    "therapy_content_electric_needle": "treatment_fees",
    "therapy_content_electric_moxa": "treatment_fees",
    "therapy_content_electric_light": "treatment_fees",
    "fee_hari_unit": "treatment_fees",
    "fee_hari_count": "treatment_fees",
    "fee_hari_total": "treatment_fees",
    "fee_kyu_unit": "treatment_fees",
    "fee_kyu_count": "treatment_fees",
    "fee_kyu_total": "treatment_fees",
    "fee_hari_kyu_unit": "treatment_fees",
    "fee_hari_kyu_count": "treatment_fees",
    "fee_hari_kyu_total": "treatment_fees",
    "fee_electric_unit": "treatment_fees",
    "fee_electric_count": "treatment_fees",
    "fee_electric_total": "treatment_fees",
    "fee_housecall_unit": "treatment_fees",
    "fee_housecall_count": "treatment_fees",
    "fee_housecall_total": "treatment_fees",
    "fee_housecall_additional_unit": "treatment_fees",
    "fee_housecall_additional_count": "treatment_fees",
    "fee_housecall_additional_total": "treatment_fees",
    "fee_subtotal": "treatment_fees",
    "expenses_borne_ratio_10": "treatment_fees",
    "expenses_borne_ratio_20": "treatment_fees",
    "expenses_borne_ratio_30": "treatment_fees",
    "fee_partial_payment": "treatment_fees",
    "fee_total_claim": "treatment_fees",
    "fee_massage_unit": "treatment_fees",
    "fee_massage_count": "treatment_fees",
    "fee_massage_total": "treatment_fees",
    "clinic_postal_code": "treatment_certification",
    "clinic_address": "treatment_certification",
    "clinic_name": "treatment_certification",
    "clinic_manager": "treatment_certification",
    "clinic_phone": "treatment_certification",
    "therapist_registration_number": "treatment_certification",
    "health_center_registration_1": "treatment_certification",
    "health_center_registration_2": "treatment_certification",
    "clinic_date_year": "treatment_certification",
    "clinic_date_month": "treatment_certification",
    "clinic_date_day": "treatment_certification",
    "consent_doctor_name": "consent_record",
    "consent_illness_name": "consent_record",
    "consent_record_doctor_name": "consent_record",
    "consent_record_doctor_postal_code": "consent_record",
    "consent_record_doctor_address": "consent_record",
    "consent_record_date_year": "consent_record",
    "consent_record_date_month": "consent_record",
    "consent_record_date_day": "consent_record",
    "consent_record_illness_name": "consent_record",
    "required_treatment_period": "consent_record",
    "doctor_postal_code": "doctor_info",
    "doctor_address": "doctor_info",
    "doctor_name": "doctor_info",
    "doctor_phone": "doctor_info",
    "guarantor_relationship": "guarantor_info",
    "guarantor_name": "guarantor_info",
    "guarantor_postal_code": "guarantor_info",
    "guarantor_address": "guarantor_info",
    "agent_postal_code": "signature",
    "agent_address": "signature",
    "agent_name": "signature",
    "payment_category_furikomi": "payment_institution",
    "payment_category_bank_transfer": "payment_institution",
    "payment_category_post_transfer": "payment_institution",
    "payment_category_local_payment": "payment_institution",
    "deposit_type_ordinary": "payment_institution",
    "deposit_type_current": "payment_institution",
    "deposit_type_notice": "payment_institution",
    "deposit_type_betsudan": "payment_institution",
    "financial_institution_name_1": "payment_institution",
    "financial_institution_type_bank": "payment_institution",
    "financial_institution_type_kinko": "payment_institution",
    "financial_institution_type_nokyo": "payment_institution",
    "financial_institution_name_2": "payment_institution",
    "branch_type_honten": "payment_institution",
    "branch_type_shiten": "payment_institution",
    "branch_type_shucchoujo": "payment_institution",
    "bank_account_holder_kana": "payment_institution",
    "bank_account_number": "payment_institution",
    "submission_date_year": "application",
    "submission_date_month": "application",
    "submission_date_day": "application",
    "insurer_name": "application",
    "applicant_postal_code": "application",
    "applicant_address": "application",
    "applicant_name": "application",
    "patient_phone": "application",
    "temporary_insurer_name": "signature",
    "signature_date_year": "signature",
    "signature_date_month": "signature",
    "signature_date_day": "signature",
    "signature_applicant_postal_code": "signature",
    "signature_applicant_address": "signature"
};

// フィールドカテゴリマッピング（マッサージ療養費支給申請書用）
const fieldCategoriesTherapyBenefitMassage = {
    "title_year_era": "basic_info",
    "title_year_number": "basic_info",
    "title_month": "basic_info",
    "institution_code": "basic_info",
    "public_funds_payer_number": "basic_info",
    "public_funds_recipient_number": "basic_info",
    "locality_code": "basic_info",
    "recipient_number": "basic_info",
    "insurance_type_1_shakoku": "basic_info",
    "insurance_type_1_kouhi": "basic_info",
    "insurance_type_1_kouki": "basic_info",
    "insurance_type_1_taishoku": "basic_info",
    "insurance_type_3_hongai": "basic_info",
    "insurance_type_3_sangai": "basic_info",
    "insurance_type_3_kagai": "basic_info",
    "insurance_type_3_kougai9": "basic_info",
    "insurance_type_3_kougai8": "basic_info",
    "benefit_ratio_80": "basic_info",
    "benefit_ratio_90": "basic_info",
    "benefit_ratio_100": "basic_info",
    "insurer_number": "basic_info",
    "insurance_symbol_code": "insured_person",
    "insurance_symbol_number": "insured_person",
    "onset_date_year": "insured_person",
    "onset_date_month": "insured_person",
    "onset_date_day": "insured_person",
    "patient_last_kana": "insured_person",
    "patient_first_kana": "insured_person",
    "patient_name_kana": "insured_person",
    "patient_relationship": "insured_person",
    "patient_last_name": "insured_person",
    "patient_first_name": "insured_person",
    "patient_name": "insured_person",
    "patient_gender_male": "insured_person",
    "patient_gender_female": "insured_person",
    "work_scope_type_1": "insured_person",
    "work_scope_type_2": "insured_person",
    "work_scope_type_3": "insured_person",
    "birthday_era_meiji": "insured_person",
    "birthday_era_taisho": "insured_person",
    "birthday_era_showa": "insured_person",
    "birthday_era_heisei": "insured_person",
    "birthday_full_date": "insured_person",
    "onset_illness_name": "insured_person",
    "patient_postal_code": "insured_person",
    "patient_address": "insured_person",
    "first_treatment_year": "treatment_content",
    "first_treatment_month": "treatment_content",
    "first_treatment_day": "treatment_content",
    "treatment_start_year": "treatment_content",
    "treatment_start_month": "treatment_content",
    "treatment_start_day": "treatment_content",
    "treatment_end_year": "treatment_content",
    "treatment_end_month": "treatment_content",
    "treatment_end_day": "treatment_content",
    "treatment_day_count": "treatment_content",
    "bill_category_new": "treatment_content",
    "bill_category_continued": "treatment_content",
    "outcome_continued": "treatment_content",
    "outcome_cured": "treatment_content",
    "outcome_discontinued": "treatment_content",
    "outcome_transferred": "treatment_content",
    "condition": "insured_person",
    "abstract": "treatment_content",
    "treatment_month": "treatment_content",
    "treatment_days": "treatment_content",
    "illness_name_symptom": "treatment_content",
    "fee_massage_trunk_unit": "treatment_fees",
    "fee_massage_trunk_count": "treatment_fees",
    "fee_massage_trunk_total": "treatment_fees",
    "fee_massage_upper_limb_r_unit": "treatment_fees",
    "fee_massage_upper_limb_r_count": "treatment_fees",
    "fee_massage_upper_limb_r_total": "treatment_fees",
    "fee_massage_upper_limb_l_unit": "treatment_fees",
    "fee_massage_upper_limb_l_count": "treatment_fees",
    "fee_massage_upper_limb_l_total": "treatment_fees",
    "fee_massage_lower_limb_r_unit": "treatment_fees",
    "fee_massage_lower_limb_r_count": "treatment_fees",
    "fee_massage_lower_limb_r_total": "treatment_fees",
    "fee_massage_lower_limb_l_unit": "treatment_fees",
    "fee_massage_lower_limb_l_count": "treatment_fees",
    "fee_massage_lower_limb_l_total": "treatment_fees",
    "fee_manual_correction_unit": "treatment_fees",
    "fee_manual_correction_count": "treatment_fees",
    "fee_manual_correction_total": "treatment_fees",
    "fee_fomentation_unit": "treatment_fees",
    "fee_fomentation_count": "treatment_fees",
    "fee_fomentation_total": "treatment_fees",
    "fee_fomentation_electric_light_unit": "treatment_fees",
    "fee_fomentation_electric_light_count": "treatment_fees",
    "fee_fomentation_electric_light_total": "treatment_fees",
    "fee_housecall_unit": "treatment_fees",
    "fee_housecall_count": "treatment_fees",
    "fee_housecall_total": "treatment_fees",
    "fee_housecall_additional_unit": "treatment_fees",
    "fee_housecall_additional_count": "treatment_fees",
    "fee_housecall_additional_total": "treatment_fees",
    "fee_subtotal": "treatment_fees",
    "expenses_borne_ratio_10": "treatment_fees",
    "expenses_borne_ratio_20": "treatment_fees",
    "expenses_borne_ratio_30": "treatment_fees",
    "fee_partial_payment": "treatment_fees",
    "fee_total_claim": "treatment_fees",
    "clinic_postal_code": "treatment_certification",
    "clinic_address": "treatment_certification",
    "clinic_name": "treatment_certification",
    "clinic_manager": "treatment_certification",
    "clinic_phone": "treatment_certification",
    "therapist_registration_number": "treatment_certification",
    "health_center_registration_1": "treatment_certification",
    "health_center_registration_2": "treatment_certification",
    "clinic_date_year": "treatment_certification",
    "clinic_date_month": "treatment_certification",
    "clinic_date_day": "treatment_certification",
    "consent_doctor_name": "consent_record",
    "consent_illness_name": "consent_record",
    "consent_record_doctor_name": "consent_record",
    "consent_record_doctor_postal_code": "consent_record",
    "consent_record_doctor_address": "consent_record",
    "consent_record_date_year": "consent_record",
    "consent_record_date_month": "consent_record",
    "consent_record_date_day": "consent_record",
    "consent_record_illness_name": "consent_record",
    "required_treatment_period": "consent_record",
    "doctor_postal_code": "doctor_info",
    "doctor_address": "doctor_info",
    "doctor_name": "doctor_info",
    "doctor_phone": "doctor_info",
    "guarantor_relationship": "guarantor_info",
    "guarantor_name": "guarantor_info",
    "guarantor_postal_code": "guarantor_info",
    "guarantor_address": "guarantor_info",
    "agent_postal_code": "signature",
    "agent_address": "signature",
    "agent_name": "signature",
    "payment_category_furikomi": "payment_institution",
    "payment_category_bank_transfer": "payment_institution",
    "payment_category_post_transfer": "payment_institution",
    "payment_category_local_payment": "payment_institution",
    "deposit_type_ordinary": "payment_institution",
    "deposit_type_current": "payment_institution",
    "deposit_type_notice": "payment_institution",
    "deposit_type_betsudan": "payment_institution",
    "financial_institution_name_1": "payment_institution",
    "financial_institution_type_bank": "payment_institution",
    "financial_institution_type_kinko": "payment_institution",
    "financial_institution_type_nokyo": "payment_institution",
    "financial_institution_name_2": "payment_institution",
    "branch_type_honten": "payment_institution",
    "branch_type_shiten": "payment_institution",
    "branch_type_shucchoujo": "payment_institution",
    "bank_account_holder_kana": "payment_institution",
    "bank_account_number": "payment_institution",
    "submission_date_year": "application",
    "submission_date_month": "application",
    "submission_date_day": "application",
    "insurer_name": "application",
    "applicant_postal_code": "application",
    "applicant_address": "application",
    "applicant_name": "application",
    "patient_phone": "application",
    "temporary_insurer_name": "signature",
    "signature_date_year": "signature",
    "signature_date_month": "signature",
    "signature_date_day": "signature",
    "signature_applicant_postal_code": "signature",
    "signature_applicant_address": "signature"
};

const categoryLabels = {
    "basic_info": "基礎情報欄",
    "insured_person": "被保険者欄",
    "treatment_content": "施術内容欄",
    "treatment_calendar": "施術日カレンダー",
    "treatment_fees": "料金関連",
    "treatment_certification": "施術証明欄",
    "application": "申請欄",
    "consent_record": "同意記録欄",
    "doctor_info": "医師情報",
    "guarantor_info": "保証人情報",
    "payment_institution": "支払機関欄",
    "signature": "委任欄",
    "symptom_category": "症状",
    "therapy_bodypart_category": "施術種類･部位",
    "housecall_category": "往療",
    "submission_info": "提出日",
    "basic_fields": "基本情報",
    "submission_fields": "提出情報",
    // 施術録（はり・きゅう）用カテゴリラベル
    "various_numbers": "各種番号",
    "insured_person_info": "被保険者情報",
    "user_info": "利用者情報",
    "clinic_info": "事業所情報",
    "insurer_info": "保険者情報",
    "treatment_injury_info": "傷病･施術情報",
    // 総括表用カテゴリラベル
    "header_info":  "ヘッダー情報",
    "cost_summary": "集計情報",
    "bank_info":    "金融機関情報"
};

const categoryOrderTherapyBenefitAcupuncture = [
    "basic_info",
    "insured_person",
    "treatment_content",
    "treatment_fees",
    "treatment_certification",
    "application",
    "payment_institution",
    "consent_record",
    "signature"
];

const categoryOrderTherapyBenefitMassage = [
    "basic_info",
    "insured_person",
    "treatment_content",
    "treatment_fees",
    "treatment_certification",
    "application",
    "payment_institution",
    "consent_record",
    "signature"
];

// フィールドカテゴリマッピング（委任状（申請･受領）用）- カテゴライズなし
const fieldCategoriesPowerOfAttorneyApplication = {};

// カテゴリ順序（委任状（申請･受領）用）- カテゴライズなし
const categoryOrderPowerOfAttorneyApplication = [];

// フィールドカテゴリマッピング（委任状（同意書取得）用）- カテゴライズなし
const fieldCategoriesPowerOfAttorneyConsent = {};

// カテゴリ順序（委任状（同意書取得）用）- カテゴライズなし
const categoryOrderPowerOfAttorneyConsent = [];

// フィールドカテゴリマッピング（施術料金領収書用）- カテゴライズなし
const fieldCategoriesTreatmentReceipt = {};

// カテゴリ順序（施術料金領収書用）- カテゴライズなし
const categoryOrderTreatmentReceipt = [];

// フィールドカテゴリマッピング（同意書（はり・きゅう）用）- カテゴライズなし
const fieldCategoriesConsentAcupuncture = {};

// カテゴリ順序（同意書（はり・きゅう）用）- カテゴライズなし
const categoryOrderConsentAcupuncture = [];

// フィールドカテゴリマッピング（同意書（あんま・マッサージ）用）
const fieldCategoriesConsentMassage = {
  "muscle_paralysis_trunk": "symptom_category",
  "muscle_paralysis_upper_limb_r": "symptom_category",
  "muscle_paralysis_upper_limb_l": "symptom_category",
  "muscle_paralysis_lower_limb_r": "symptom_category",
  "muscle_paralysis_lower_limb_l": "symptom_category",
  "joint_contracture_right_shoulder": "symptom_category",
  "joint_contracture_right_elbow": "symptom_category",
  "joint_contracture_right_wrist": "symptom_category",
  "joint_contracture_right_hip": "symptom_category",
  "joint_contracture_right_knee": "symptom_category",
  "joint_contracture_right_ankle": "symptom_category",
  "joint_contracture_left_shoulder": "symptom_category",
  "joint_contracture_left_elbow": "symptom_category",
  "joint_contracture_left_wrist": "symptom_category",
  "joint_contracture_left_hip": "symptom_category",
  "joint_contracture_left_knee": "symptom_category",
  "joint_contracture_left_ankle": "symptom_category",
  "joint_contracture_other": "symptom_category",
  "joint_contracture_other_text": "symptom_category",
  "symptom_other": "symptom_category",

  "massage_trunk": "therapy_bodypart_category",
  "massage_upper_limb_r": "therapy_bodypart_category",
  "massage_upper_limb_l": "therapy_bodypart_category",
  "massage_lower_limb_r": "therapy_bodypart_category",
  "massage_lower_limb_l": "therapy_bodypart_category",
  "manual_correction_upper_limb_r": "therapy_bodypart_category",
  "manual_correction_upper_limb_l": "therapy_bodypart_category",
  "manual_correction_lower_limb_r": "therapy_bodypart_category",
  "manual_correction_lower_limb_l": "therapy_bodypart_category",

  "housecall_required": "housecall_category",
  "housecall_not_required": "housecall_category",
  "housecall_reason_1": "housecall_category",
  "housecall_reason_2": "housecall_category",
  "housecall_reason_3": "housecall_category",
  "housecall_reason_other_text": "housecall_category"
};

// カテゴリ順序（同意書（あんま・マッサージ）用）
const categoryOrderConsentMassage = [
  "symptom_category",
  "therapy_bodypart_category",
  "housecall_category"
];

// フィールドカテゴリマッピング（同意書依頼状サンプル版はり･きゅう用）- カテゴライズなし
const fieldCategoriesConsentRequestLetterSampleAcupuncture = {};

// カテゴリ順序（同意書依頼状サンプル版はり･きゅう用）- カテゴライズなし
const categoryOrderConsentRequestLetterSampleAcupuncture = [];

// フィールドカテゴリマッピング（医療助成費支給申請書（はり・きゅう）用）
const fieldCategoriesMedicalAssistanceAcupuncture = {
  "custom_title_text": "basic_info",
  "submission_count": "basic_info",
  "title_year_month": "basic_info",
  "locality_code": "basic_info",
  "recipient_number": "basic_info",
  "insurer_number": "basic_info",
  "insurance_symbol_code": "basic_info",
  "insurance_symbol_number": "basic_info",
  "patient_name_kana": "basic_info",
  "patient_name": "basic_info",
  "patient_gender_male": "basic_info",
  "patient_gender_female": "basic_info",
  "birthday_era_meiji": "basic_info",
  "birthday_era_taisho": "basic_info",
  "birthday_era_showa": "basic_info",
  "birthday_era_heisei": "basic_info",
  "birthday_era_reiwa": "basic_info",
  "birthday_year": "basic_info",
  "birthday_month": "basic_info",
  "birthday_day": "basic_info",
  "patient_relationship": "basic_info",
  "insured_person_name": "basic_info",
  "condition": "basic_info",
  "insurance_type_3_hongai": "basic_info",
  "insurance_type_3_sangai": "basic_info",
  "insurance_type_3_kagai": "basic_info",
  "insurance_type_3_kougai9": "basic_info",
  "insurance_type_3_kougai8": "basic_info",
  "treatment_start_year": "treatment_content",
  "treatment_start_month": "treatment_content",
  "treatment_start_day": "treatment_content",
  "treatment_end_year": "treatment_content",
  "treatment_end_month": "treatment_content",
  "treatment_end_day": "treatment_content",
  "treatment_days": "treatment_content",
  "treatment_day_count": "treatment_content",
  "bill_category": "treatment_content",
  "illness_name_1": "treatment_content",
  "illness_name_2": "treatment_content",
  "illness_name_3": "treatment_content",
  "illness_name_4": "treatment_content",
  "illness_name_5": "treatment_content",
  "illness_name_6": "treatment_content",
  "illness_name_7": "treatment_content",
  "illness_name_other_text": "treatment_content",
  "outcome": "treatment_content",
  "abstract": "treatment_content",
  "treatment_month": "treatment_content",
  "first_treatment_year": "treatment_content",
  "first_treatment_month": "treatment_content",
  "first_treatment_day": "treatment_content",
  "onset_date_year": "treatment_content",
  "onset_date_month": "treatment_content",
  "onset_date_day": "treatment_content",
  "onset_date": "treatment_content",
  "therapy_period_start": "treatment_content",
  "therapy_period_end": "treatment_content",
  "work_scope_type_1": "treatment_content",
  "work_scope_type_2": "treatment_content",
  "work_scope_type_3": "treatment_content",
  "fee_initial_examination_hari": "treatment_fees",
  "fee_initial_examination_kyu": "treatment_fees",
  "fee_initial_examination_combined": "treatment_fees",
  "fee_initial_examination_amount": "treatment_fees",
  "fee_hari_unit": "treatment_fees",
  "fee_hari_count": "treatment_fees",
  "fee_hari_total": "treatment_fees",
  "fee_hari_electric_unit": "treatment_fees",
  "fee_hari_electric_count": "treatment_fees",
  "fee_hari_electric_total": "treatment_fees",
  "fee_kyu_unit": "treatment_fees",
  "fee_kyu_count": "treatment_fees",
  "fee_kyu_total": "treatment_fees",
  "fee_kyu_electric_unit": "treatment_fees",
  "fee_kyu_electric_count": "treatment_fees",
  "fee_kyu_electric_total": "treatment_fees",
  "fee_hari_kyu_unit": "treatment_fees",
  "fee_hari_kyu_count": "treatment_fees",
  "fee_hari_kyu_total": "treatment_fees",
  "fee_hari_kyu_electric_unit": "treatment_fees",
  "fee_hari_kyu_electric_count": "treatment_fees",
  "fee_hari_kyu_electric_total": "treatment_fees",
  "fee_housecall_unit": "treatment_fees",
  "fee_housecall_count": "treatment_fees",
  "fee_housecall_total": "treatment_fees",
  "fee_housecall_additional_unit": "treatment_fees",
  "fee_housecall_additional_count": "treatment_fees",
  "fee_housecall_additional_total": "treatment_fees",
  "fee_subtotal": "treatment_fees",
  "public_burden_ratio": "treatment_fees",
  "fee_public_burden_amount": "treatment_fees",
  "expenses_borne_ratio_10": "treatment_fees",
  "expenses_borne_ratio_20": "treatment_fees",
  "expenses_borne_ratio_30": "treatment_fees",
  "fee_partial_payment": "treatment_fees",
  "fee_total_claim": "treatment_fees",
  "health_center_registration_1": "treatment_certification",
  "health_center_registration_2": "treatment_certification",
  "license_hari_number": "treatment_certification",
  "license_kyu_number": "treatment_certification",
  "therapist_postal_code": "treatment_certification",
  "therapist_address": "treatment_certification",
  "therapist_name": "treatment_certification",
  "therapist_phone": "treatment_certification",
  "applicant_postal_code": "application",
  "applicant_address": "application",
  "applicant_name": "application",
  "patient_phone": "application",
  "payment_category_account_transfer": "payment_institution",
  "payment_category_counter_payment": "payment_institution",
  "deposit_type_ordinary": "payment_institution",
  "deposit_type_current": "payment_institution",
  "deposit_type_savings": "payment_institution",
  "deposit_type_other": "payment_institution",
  "financial_institution_name": "payment_institution",
  "bank_account_holder_kana": "payment_institution",
  "bank_account_number": "payment_institution",
  "signature_applicant_postal_code": "signature",
  "agent_address": "signature",
  "temporary_insurer_name": "signature",
  "agent_postal_code": "signature",
  "signature_applicant_address": "signature",
  "agent_name": "signature",
  "consent_record_doctor_name": "consent_record",
  "consent_record_doctor_postal_code": "consent_record",
  "consent_record_doctor_address": "consent_record",
  "consent_record_date_year": "consent_record",
  "consent_record_illness_name": "consent_record",
  "required_treatment_period": "consent_record"
};

// フィールドカテゴリマッピング（医療助成費支給申請書（あんま・マッサージ）用）
const fieldCategoriesMedicalAssistanceMassage = {
  "custom_title_text": "basic_info",
  "submission_count": "basic_info",
  "title_year_month": "basic_info",
  "locality_code": "basic_info",
  "recipient_number": "basic_info",
  "insurer_number": "basic_info",
  "insurance_symbol_code": "basic_info",
  "insurance_symbol_number": "basic_info",
  "patient_name_kana": "basic_info",
  "patient_name": "basic_info",
  "patient_gender_male": "basic_info",
  "patient_gender_female": "basic_info",
  "birthday_era_meiji": "basic_info",
  "birthday_era_taisho": "basic_info",
  "birthday_era_showa": "basic_info",
  "birthday_era_heisei": "basic_info",
  "birthday_era_reiwa": "basic_info",
  "birthday_year": "basic_info",
  "birthday_month": "basic_info",
  "birthday_day": "basic_info",
  "patient_relationship": "basic_info",
  "insured_person_name": "basic_info",
  "condition": "basic_info",
  "insurance_type_3_hongai": "basic_info",
  "insurance_type_3_sangai": "basic_info",
  "insurance_type_3_kagai": "basic_info",
  "insurance_type_3_kougai9": "basic_info",
  "insurance_type_3_kougai8": "basic_info",
  "first_treatment_year": "treatment_content",
  "first_treatment_month": "treatment_content",
  "first_treatment_day": "treatment_content",
  "treatment_start_year": "treatment_content",
  "treatment_start_month": "treatment_content",
  "treatment_start_day": "treatment_content",
  "treatment_end_year": "treatment_content",
  "treatment_end_month": "treatment_content",
  "treatment_end_day": "treatment_content",
  "treatment_day_count": "treatment_content",
  "work_scope_type_1": "treatment_content",
  "work_scope_type_2": "treatment_content",
  "work_scope_type_3": "treatment_content",
  "treatment_content_illness_name": "treatment_content",
  "onset_date": "treatment_content",
  "onset_date_year": "treatment_content",
  "onset_date_month": "treatment_content",
  "onset_date_day": "treatment_content",
  "onset_illness_name": "treatment_content",
  "illness_name_symptom": "treatment_content",
  "bill_category": "treatment_content",
  "outcome": "treatment_content",
  "abstract": "treatment_content",
  "treatment_month": "treatment_content",
  "treatment_days": "treatment_content",
  "fee_massage_unit": "treatment_fees",
  "fee_massage_bodypart_count": "treatment_fees",
  "fee_massage_count": "treatment_fees",
  "fee_massage_total": "treatment_fees",
  "fee_manual_correction_unit": "treatment_fees",
  "fee_manual_correction_bodypart_count": "treatment_fees",
  "fee_manual_correction_count": "treatment_fees",
  "fee_manual_correction_total": "treatment_fees",
  "fee_fomentation_unit": "treatment_fees",
  "fee_fomentation_count": "treatment_fees",
  "fee_fomentation_total": "treatment_fees",
  "fee_fomentation_electric_light_unit": "treatment_fees",
  "fee_fomentation_electric_light_count": "treatment_fees",
  "fee_fomentation_electric_light_total": "treatment_fees",
  "fee_housecall_unit": "treatment_fees",
  "fee_housecall_count": "treatment_fees",
  "fee_housecall_total": "treatment_fees",
  "fee_housecall_additional_unit": "treatment_fees",
  "fee_housecall_additional_count": "treatment_fees",
  "fee_housecall_additional_total": "treatment_fees",
  "fee_subtotal": "treatment_fees",
  "public_burden_ratio": "treatment_fees",
  "fee_public_burden_amount": "treatment_fees",
  "expenses_borne_ratio_10": "treatment_fees",
  "expenses_borne_ratio_20": "treatment_fees",
  "expenses_borne_ratio_30": "treatment_fees",
  "fee_partial_payment": "treatment_fees",
  "fee_total_claim": "treatment_fees",
  "health_center_registration_1": "treatment_certification",
  "health_center_registration_2": "treatment_certification",
  "license_massage_number": "treatment_certification",
  "therapist_postal_code": "treatment_certification",
  "therapist_address": "treatment_certification",
  "therapist_name": "treatment_certification",
  "therapist_phone": "treatment_certification",
  "applicant_postal_code": "application",
  "applicant_address": "application",
  "applicant_name": "application",
  "patient_phone": "application",
  "payment_category_account_transfer": "payment_institution",
  "payment_category_counter_payment": "payment_institution",
  "deposit_type_ordinary": "payment_institution",
  "deposit_type_current": "payment_institution",
  "deposit_type_savings": "payment_institution",
  "deposit_type_other": "payment_institution",
  "financial_institution_name": "payment_institution",
  "bank_account_holder_kana": "payment_institution",
  "bank_account_number": "payment_institution",
  "signature_applicant_postal_code": "signature",
  "agent_address": "signature",
  "temporary_insurer_name": "signature",
  "agent_postal_code": "signature",
  "signature_applicant_address": "signature",
  "agent_name": "signature",
  "consent_record_doctor_name": "consent_record",
  "consent_record_doctor_postal_code": "consent_record",
  "consent_record_doctor_address": "consent_record",
  "consent_record_date_year": "consent_record",
  "consent_record_illness_name": "consent_record",
  "required_treatment_period": "consent_record"
};

// カテゴリ順序（医療助成費支給申請書用）
const categoryOrderMedicalAssistance = [
  "basic_info",
  "treatment_content",
  "treatment_fees",
  "treatment_certification",
  "application",
  "payment_institution",
  "signature",
  "consent_record"
];

// ============================================================
// 施術録（はり・きゅう）用のフィールドカテゴリマッピング
// ============================================================
const fieldCategoriesTreatmentRecordAcupuncture = {
  // === 各種番号 ===
  "locality_code": "various_numbers",
  "recipient_number": "various_numbers",
  "public_funds_payer_number": "various_numbers",
  "public_funds_recipient_number": "various_numbers",

  // === 被保険者情報 ===
  "insurance_symbol_code": "insured_person_info",
  "insurance_symbol_number": "insured_person_info",
  "insured_person_name": "insured_person_info",
  "insured_person_gender": "insured_person_info",
  "insured_person_birthday": "insured_person_info",
  "insurance_valid_until": "insured_person_info",
  "insured_person_postal_code": "insured_person_info",
  "insured_person_address": "insured_person_info",
  "insurance_qualification_date": "insured_person_info",

  // === 利用者情報 ===
  "user_name": "user_info",
  "user_gender": "user_info",
  "user_birthday": "user_info",
  "user_relationship": "user_info",

  // === 事業所情報 ===
  "clinic_address": "clinic_info",
  "clinic_name": "clinic_info",

  // === 保険者情報 ===
  "insurer_address": "insurer_info",
  "insurer_name": "insurer_info",
  "insurer_number": "insurer_info",

  // === 傷病･施術情報 ===
  "illness_name": "treatment_injury_info",
  "onset_date": "treatment_injury_info",
  "first_treatment_date": "treatment_injury_info",
  "treatment_end_date": "treatment_injury_info",
  "treatment_days_count": "treatment_injury_info",
  "treatment_count": "treatment_injury_info",
  "outcome": "treatment_injury_info",

  // === 同意記録 ===
  "medical_institution_name": "consent_record",
  "medical_institution_address": "consent_record",
  "medical_institution_phone": "consent_record",
  "doctor_name_kana": "consent_record",
  "doctor_name": "consent_record",
  "consent_category": "consent_record",
  "treatment_period": "consent_record",
  "onset_cause": "consent_record"
};

// カテゴリ順序（施術録（はり・きゅう）用）
const categoryOrderTreatmentRecordAcupuncture = [
  "various_numbers",
  "insured_person_info",
  "user_info",
  "clinic_info",
  "insurer_info",
  "treatment_injury_info",
  "consent_record"
];

// フィールドカテゴリマッピング（総括表用）
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
  'benefit_ratio':         'cost_summary',
  'treatment_count':       'cost_summary',
  'cost_amount':           'cost_summary',
  'claim_amount':          'cost_summary',
  'total_treatment_count': 'cost_summary',
  'total_cost_amount':     'cost_summary',
  'total_claim_amount':    'cost_summary',

  // 金融機関情報
  'bank_name':           'bank_info',
  'bank_branch_name':    'bank_info',
  'bank_account_type':   'bank_info',
  'bank_account_number': 'bank_info',
  'bank_account_name':   'bank_info',
};

// カテゴリ順序（総括表用）
const categoryOrderSummaryTable = [
  'header_info',
  'clinic_info',
  'cost_summary',
  'bank_info',
];

// ============================================================
// PDFタイプに応じたfieldCategoriesを取得
// ============================================================
// ⚠️ 必読: storage/app/config/PDF_TYPES_README.md
// 新しいPDFタイプ追加時、この関数にケースを追加すること
// ============================================================
function getFieldCategories(pdfType) {
  // fieldsFileが空の場合は空オブジェクトを返す
  const pdfTypes = window.coordinateAdjusterData?.pdfTypes || {};
  const currentConfig = pdfTypes[pdfType] || {};

  if (!currentConfig.fieldsFile || currentConfig.fieldsFile === '') {
    return {};
  }

  if (pdfType === 'power_of_attorney_application') {
    return fieldCategoriesPowerOfAttorneyApplication;
  } else if (pdfType === 'power_of_attorney_consent') {
    return fieldCategoriesPowerOfAttorneyConsent;
  } else if (pdfType === 'therapy_benefit_massage') {
    return fieldCategoriesTherapyBenefitMassage;
  } else if (pdfType === 'treatment_receipt') {
    return fieldCategoriesTreatmentReceipt;
  } else if (pdfType === 'consent_acupuncture') {
    return fieldCategoriesConsentAcupuncture;
  } else if (pdfType === 'consent_massage') {
    return fieldCategoriesConsentMassage;
  } else if (pdfType === 'consent_request_letter_sample_acupuncture' || pdfType === 'consent_request_letter_designated_acupuncture' || pdfType === 'consent_request_letter_sample_massage' || pdfType === 'consent_request_letter_designated_massage') {
    return fieldCategoriesConsentRequestLetterSampleAcupuncture;
  } else if (pdfType === 'medical_assistance_acupuncture') {
    return fieldCategoriesMedicalAssistanceAcupuncture;
  } else if (pdfType === 'medical_assistance_massage' || pdfType === 'elderly_therapy_benefit_massage') {
    return fieldCategoriesMedicalAssistanceMassage;
  } else if (pdfType === 'elderly_therapy_benefit_acupuncture') {
    return fieldCategoriesMedicalAssistanceAcupuncture;
  } else if (pdfType === 'treatment_record_acupuncture' || pdfType === 'treatment_record_massage') {
    return fieldCategoriesTreatmentRecordAcupuncture;
  } else if (pdfType === 'summary_table') {
    return fieldCategoriesSummaryTable;
  }
  return fieldCategoriesTherapyBenefitAcupuncture;
}

// ============================================================
// PDFタイプに応じたcategoryOrderを取得
// ============================================================
// ⚠️ 必読: storage/app/config/PDF_TYPES_README.md
// 新しいPDFタイプ追加時、この関数にケースを追加すること
// ============================================================
function getCategoryOrder(pdfType) {
  // fieldsFileが空の場合は空配列を返す
  const pdfTypes = window.coordinateAdjusterData?.pdfTypes || {};
  const currentConfig = pdfTypes[pdfType] || {};

  if (!currentConfig.fieldsFile || currentConfig.fieldsFile === '') {
    return [];
  }

  if (pdfType === 'power_of_attorney_application') {
    return categoryOrderPowerOfAttorneyApplication;
  } else if (pdfType === 'power_of_attorney_consent') {
    return categoryOrderPowerOfAttorneyConsent;
  } else if (pdfType === 'therapy_benefit_massage') {
    return categoryOrderTherapyBenefitMassage;
  } else if (pdfType === 'treatment_receipt') {
    return categoryOrderTreatmentReceipt;
  } else if (pdfType === 'consent_acupuncture') {
    return categoryOrderConsentAcupuncture;
  } else if (pdfType === 'consent_massage') {
    return categoryOrderConsentMassage;
  } else if (pdfType === 'consent_request_letter_sample_acupuncture' || pdfType === 'consent_request_letter_designated_acupuncture' || pdfType === 'consent_request_letter_sample_massage' || pdfType === 'consent_request_letter_designated_massage') {
    return categoryOrderConsentRequestLetterSampleAcupuncture;
  } else if (pdfType === 'medical_assistance_acupuncture' || pdfType === 'medical_assistance_massage' || pdfType === 'elderly_therapy_benefit_acupuncture' || pdfType === 'elderly_therapy_benefit_massage') {
    return categoryOrderMedicalAssistance;
  } else if (pdfType === 'treatment_record_acupuncture' || pdfType === 'treatment_record_massage') {
    return categoryOrderTreatmentRecordAcupuncture;
  } else if (pdfType === 'summary_table') {
    return categoryOrderSummaryTable;
  }
  return categoryOrderTherapyBenefitAcupuncture;
}
