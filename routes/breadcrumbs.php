<?php
// routes/breadcrumbs.php //


use App\Support\Breadcrumbs;


//━━━┫〈 ヘルパー関数 〉┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━//
// ホーム
function getIndexBreadcrumbs() {
  return [
    ['url' => route('index'), 'label' => 'ホーム'],
  ];
}

// マスター登録
function getMasterBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('master.index'), 'label' => 'マスター登録'],
  ];
}

function getDoctorsBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('doctors.index'), 'label' => '医師情報'],
  ];
}

// 利用者情報
function getClinicUsersBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('clinic-users.index'), 'label' => '利用者情報'],
  ];
}

// 施術者情報
function getTherapistsBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('therapists.index'), 'label' => '施術者情報'],
  ];
}

// ケアマネ情報
function getCareManagersBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('caremanagers.index'), 'label' => 'ケアマネ情報'],
  ];
}

// 自社情報
function getClinicInfoBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('clinic-info.index'), 'label' => '自社情報'],
  ];
}

// サブマスター登録
function getSubMasterBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('submaster.index'), 'label' => 'サブマスター登録'],
  ];
}

// 文面編集
function getDocumentsBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('master.documents.index'), 'label' => '文面編集'],
  ];
}

// 施術料金編集
function getTreatmentFeesBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('master.treatment-fees.index'), 'label' => '施術料金編集'],
  ];
}

// 自費施術料金編集
function getSelfFeesBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('master.self-fees.index'), 'label' => '自費施術料金編集'],
  ];
}

// 標準文書の確認および関連付け
function getDocumentAssociationBreadcrumbs() {
  return [
    ...getMasterBreadcrumbs(),
    ['url' => route('master.document-association.index'), 'label' => '標準文書の確認および関連付け'],
  ];
}

// 実績データ
function getRecordsBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('records.index'), 'label' => '実績データ'],
  ];
}

// 報告書データ
function getReportsBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('reports.index'), 'label' => '報告書データ'],
  ];
}

// スケジュール
function getSchedulesBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('schedules.index'), 'label' => 'スケジュール'],
  ];
}

// 入金管理
function getDepositsBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('deposits.index'), 'label' => '入金管理'],
  ];
}
//---------------------------------------------------------------//






//━━━┫〈 パンくずリスト定義 〉┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━//
// ホーム
Breadcrumbs::define('index', function() {
  return getIndexBreadcrumbs();
});

// 管理画面
function getAdminPanelBreadcrumbs() {
  return [
    ...getIndexBreadcrumbs(),
    ['url' => route('admin-panel.index'), 'label' => '管理画面'],
  ];
}

Breadcrumbs::define('admin-panel.index', function() {
  return getAdminPanelBreadcrumbs();
});

// お知らせ ｰ トップ
function getNoticesBreadcrumbs() {
  return [
    ...getAdminPanelBreadcrumbs(),
    ['url' => route('notices.index'), 'label' => 'お知らせ'],
  ];
}

Breadcrumbs::define('notices.index', function() {
  return getNoticesBreadcrumbs();
});

// お知らせ ｰ 新規登録
Breadcrumbs::define('notices.create', function() {
  return [
    ...getNoticesBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// お知らせ ｰ 編集
Breadcrumbs::define('notices.edit', function() {
  return [
    ...getNoticesBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// お知らせ ｰ 複製
Breadcrumbs::define('notices.duplicate', function() {
  return [
    ...getNoticesBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// マスター登録
Breadcrumbs::define('master.index', function() {
  return getMasterBreadcrumbs();
});

// 医師情報 ｰ トップ
Breadcrumbs::define('doctors.index', function() {
  return getDoctorsBreadcrumbs();
});

// 医師情報 ｰ 新規登録
Breadcrumbs::define('doctors.create', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 医師情報 ｰ 新規登録確認
Breadcrumbs::define('doctors.confirm', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 医師情報 ｰ 編集
Breadcrumbs::define('doctors.edit', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 医師情報 ｰ 編集確認
Breadcrumbs::define('doctors.edit.confirm', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 医師情報 ｰ 複製
Breadcrumbs::define('doctors.duplicate', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 医師情報 ｰ 複製確認
Breadcrumbs::define('doctors.duplicate.confirm', function() {
  return [
    ...getDoctorsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 実績データ
Breadcrumbs::define('records.index', function() {
  return getRecordsBreadcrumbs();
});

// 実績データ ｰ 編集
Breadcrumbs::define('records.edit', function() {
  return [
    ...getRecordsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 実績データ ｰ 複製
Breadcrumbs::define('records.duplicate', function() {
  return [
    ...getRecordsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 報告書データ
Breadcrumbs::define('reports.index', function() {
  return getReportsBreadcrumbs();
});

// 報告書データ ｰ 新規登録
Breadcrumbs::define('reports.create', function() {
  return [
    ...getReportsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 報告書データ ｰ 編集
Breadcrumbs::define('reports.edit', function() {
  return [
    ...getReportsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 報告書データ ｰ 複製
Breadcrumbs::define('reports.duplicate', function() {
  return [
    ...getReportsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// スケジュール
Breadcrumbs::define('schedules.index', function() {
  return getSchedulesBreadcrumbs();
});

// 入金管理
Breadcrumbs::define('deposits.index', function() {
  return getDepositsBreadcrumbs();
});

// 利用者情報 ｰ トップ
Breadcrumbs::define('clinic-users.index', function() {
  return getClinicUsersBreadcrumbs();
});

// 利用者情報 ｰ 新規登録
Breadcrumbs::define('clinic-users.create', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 利用者情報 ｰ 編集
Breadcrumbs::define('clinic-users.edit', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 利用者情報 ｰ 保険情報
Breadcrumbs::define('clinic-users.insurances.index', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '保険情報'],
  ];
});

// 利用者情報 ｰ 保険情報 ｰ 新規登録
Breadcrumbs::define('clinic-users.insurances.create', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '保険情報'],
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 利用者情報 ｰ 保険情報 ｰ 編集
Breadcrumbs::define('clinic-users.insurances.edit', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '保険情報'],
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 利用者情報 ｰ 保険情報 ｰ 複製
Breadcrumbs::define('clinic-users.insurances.duplicate', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '保険情報'],
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（あんま・マッサージ）
Breadcrumbs::define('clinic-users.consents-massage.index', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（あんま・マッサージ）'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（あんま・マッサージ） ｰ 新規登録
Breadcrumbs::define('clinic-users.consents-massage.create', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（あんま・マッサージ）'],
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（あんま・マッサージ） ｰ 編集
Breadcrumbs::define('clinic-users.consents-massage.edit', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（あんま・マッサージ）'],
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（あんま・マッサージ） ｰ 複製
Breadcrumbs::define('clinic-users.consents-massage.duplicate', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（あんま・マッサージ）'],
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（鍼灸）
Breadcrumbs::define('clinic-users.consents-acupuncture.index', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（鍼灸）'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（鍼灸） ｰ 新規登録
Breadcrumbs::define('clinic-users.consents-acupuncture.registration', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（鍼灸）'],
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（鍼灸） ｰ 編集
Breadcrumbs::define('clinic-users.consents-acupuncture.edit', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（鍼灸）'],
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 利用者情報 ｰ 同意医師履歴（鍼灸） ｰ 複製
Breadcrumbs::define('clinic-users.consents-acupuncture.duplicate', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '同意医師履歴（鍼灸）'],
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 利用者情報 ｰ 計画情報
Breadcrumbs::define('clinic-users.plans.index', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '計画情報'],
  ];
});

// 利用者情報 ｰ 計画情報 ｰ 新規登録
Breadcrumbs::define('clinic-users.plans.create', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '計画情報'],
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 利用者情報 ｰ 計画情報 ｰ 編集
Breadcrumbs::define('clinic-users.plans.edit', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '計画情報'],
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 利用者情報 ｰ 計画情報 ｰ 複製
Breadcrumbs::define('clinic-users.plans.duplicate', function() {
  return [
    ...getClinicUsersBreadcrumbs(),
    ['url' => null, 'label' => '計画情報'],
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 施術者情報 ｰ トップ
Breadcrumbs::define('therapists.index', function() {
  return getTherapistsBreadcrumbs();
});

// 施術者情報 ｰ 新規登録
Breadcrumbs::define('therapists.create', function() {
  return [
    ...getTherapistsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 施術者情報 ｰ 編集
Breadcrumbs::define('therapists.edit', function() {
  return [
    ...getTherapistsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// ケアマネ情報 ｰ トップ
Breadcrumbs::define('caremanagers.index', function() {
  return getCareManagersBreadcrumbs();
});

// ケアマネ情報 ｰ 新規登録
Breadcrumbs::define('caremanagers.create', function() {
  return [
    ...getCareManagersBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// ケアマネ情報 ｰ 編集
Breadcrumbs::define('caremanagers.edit', function() {
  return [
    ...getCareManagersBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 自社情報
Breadcrumbs::define('clinic-info.index', function() {
  return getClinicInfoBreadcrumbs();
});

// サブマスター登録 ｰ トップ
Breadcrumbs::define('submaster.index', function() {
  return getSubMasterBreadcrumbs();
});

// サブマスター登録 ｰ 医療機関
Breadcrumbs::define('submaster.medical-institutions', function() {
  return [
    ...getSubMasterBreadcrumbs(),
    ['url' => null, 'label' => '医療機関'],
  ];
});

// サブマスター登録 ｰ サービス提供者
Breadcrumbs::define('submaster.service-providers', function() {
  return [
    ...getSubMasterBreadcrumbs(),
    ['url' => null, 'label' => 'サービス提供者'],
  ];
});

// サブマスター登録 ｰ 状態
Breadcrumbs::define('submaster.conditions', function() {
  return [
    ...getSubMasterBreadcrumbs(),
    ['url' => null, 'label' => '状態'],
  ];
});

// サブマスター登録 ｰ 疾病（あんま・マッサージ）
Breadcrumbs::define('submaster.illnesses-massage', function() {
  return [
    ...getSubMasterBreadcrumbs(),
    ['url' => null, 'label' => '疾病（あんま・マッサージ）'],
  ];
});

// 文面編集 ｰ トップ
Breadcrumbs::define('master.documents.index', function() {
  return getDocumentsBreadcrumbs();
});

// 文面編集 ｰ 新規登録
Breadcrumbs::define('master.documents.create', function() {
  return [
    ...getDocumentsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 文面編集 ｰ 編集
Breadcrumbs::define('master.documents.edit', function() {
  return [
    ...getDocumentsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 文面編集 ｰ 複製
Breadcrumbs::define('master.documents.duplicate', function() {
  return [
    ...getDocumentsBreadcrumbs(),
    ['url' => null, 'label' => '登録 (複製)'],
  ];
});

// 施術料金編集 ｰ トップ
Breadcrumbs::define('master.treatment-fees.index', function() {
  return getTreatmentFeesBreadcrumbs();
});

// 施術料金編集 ｰ 新規登録
Breadcrumbs::define('master.treatment-fees.create', function() {
  return [
    ...getTreatmentFeesBreadcrumbs(),
    ['url' => null, 'label' => '登録 (新規)'],
  ];
});

// 施術料金編集 ｰ 編集
Breadcrumbs::define('master.treatment-fees.edit', function() {
  return [
    ...getTreatmentFeesBreadcrumbs(),
    ['url' => null, 'label' => '登録 (編集)'],
  ];
});

// 自費施術料金編集
Breadcrumbs::define('master.self-fees.index', function() {
  return getSelfFeesBreadcrumbs();
});

// 標準文書の確認および関連付け
Breadcrumbs::define('master.document-association.index', function() {
  return getDocumentAssociationBreadcrumbs();
});
//---------------------------------------------------------------//