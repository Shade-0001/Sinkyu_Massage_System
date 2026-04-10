<?php
// routes/web.php


use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClinicUserController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\ConsentMassageController;
use App\Http\Controllers\ConsentAcupunctureController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\DoctorsController;
use App\Http\Controllers\TherapistsController;
use App\Http\Controllers\CareManagersController;
use App\Http\Controllers\CompanyInfoController;
use App\Http\Controllers\SubMasterController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TreatmentFeeController;
use App\Http\Controllers\SelfFeeController;
use App\Http\Controllers\DocumentAssociationController;
use App\Http\Controllers\RecordsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\TherapyPeriodController;
use App\Http\Controllers\DepositsController;
use App\Http\Controllers\PrintsController;
use App\Http\Controllers\NoticesController;
use App\Http\Controllers\SystemUsersController;

Route::get('/', fn() => redirect()->route('login'));

Route::middleware('auth')->group(function () {
  // お知らせAPI（ヘッダー通知ウィジェット用）
  Route::get('/notices/api/list', [NoticesController::class, 'apiList'])->name('notices.api.list');
  Route::post('/notices/api/{id}/toggle-read', [NoticesController::class, 'apiToggleRead'])->name('notices.api.toggle-read');

	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
	Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

	Route::view('/index', 'index')->name('index');

	Route::view('/master/index', 'master.master_index')->name('master.index');

  // システム管理（is_admin=1のみアクセス可）
  Route::middleware('admin')->group(function () {
    Route::view('/admin-panel/index', 'admin-panel.admin-panel_index')->name('admin-panel.index');

    // システムユーザー
    Route::get('/admin-panel/system-users/index', [SystemUsersController::class, 'index'])->name('system-users.index');
    Route::get('/admin-panel/system-users/create', [SystemUsersController::class, 'create'])->name('system-users.create');
    Route::post('/admin-panel/system-users/store', [SystemUsersController::class, 'store'])->name('system-users.store');
    Route::get('/admin-panel/system-users/{id}/edit', [SystemUsersController::class, 'edit'])->name('system-users.edit');
    Route::post('/admin-panel/system-users/{id}/update', [SystemUsersController::class, 'update'])->name('system-users.update');
    Route::post('/admin-panel/system-users/verify-password', [SystemUsersController::class, 'verifyPassword'])->name('system-users.verify-password');

    // お知らせ
    Route::get('/admin-panel/notices/index', [NoticesController::class, 'index'])->name('notices.index');
    Route::get('/admin-panel/notices/create', [NoticesController::class, 'create'])->name('notices.create');
    Route::post('/admin-panel/notices/store', [NoticesController::class, 'store'])->name('notices.store');
    Route::get('/admin-panel/notices/{id}/edit', [NoticesController::class, 'edit'])->name('notices.edit');
    Route::post('/admin-panel/notices/{id}/update', [NoticesController::class, 'update'])->name('notices.update');
    Route::get('/admin-panel/notices/{id}/duplicate', [NoticesController::class, 'duplicate'])->name('notices.duplicate');
    Route::post('/admin-panel/notices/duplicate/store', [NoticesController::class, 'duplicateStore'])->name('notices.duplicate.store');
    Route::delete('/admin-panel/notices/{id}', [NoticesController::class, 'destroy'])->name('notices.delete');
  });

  // 医師情報
  Route::get('/master/doctors/index', [DoctorsController::class, 'index'])->name('doctors.index');
  Route::get('/master/doctors/create', [DoctorsController::class, 'create'])->name('doctors.create');
  Route::post('/master/doctors/create/confirm', [DoctorsController::class, 'confirm'])->name('doctors.confirm');
  Route::post('/master/doctors/store', [DoctorsController::class, 'store'])->name('doctors.store');

  Route::get('/master/doctors/{id}/edit', [DoctorsController::class, 'edit'])->name('doctors.edit');
  Route::post('/master/doctors/{id}/edit/confirm', [DoctorsController::class, 'editConfirm'])->name('doctors.edit.confirm');
  Route::post('/master/doctors/{id}/update', [DoctorsController::class, 'update'])->name('doctors.update');

  Route::get('/master/doctors/{id}/duplicate', [DoctorsController::class, 'duplicate'])->name('doctors.duplicate');
  Route::post('/master/doctors/duplicate/confirm', [DoctorsController::class, 'duplicateConfirm'])->name('doctors.duplicate.confirm');
  Route::post('/master/doctors/duplicate/store', [DoctorsController::class, 'duplicateStore'])->name('doctors.duplicate.store');

  Route::delete('/master/doctors/{id}', [DoctorsController::class, 'destroy'])->name('doctors.delete');

  // 施術者情報
  Route::get('/master/therapists/index', [TherapistsController::class, 'index'])->name('therapists.index');
  Route::get('/master/therapists/create', [TherapistsController::class, 'create'])->name('therapists.create');
  Route::post('/master/therapists/confirm', [TherapistsController::class, 'confirm'])->name('therapists.confirm');
  Route::post('/master/therapists/store', [TherapistsController::class, 'store'])->name('therapists.store');

  Route::get('/master/therapists/{id}/edit', [TherapistsController::class, 'edit'])->name('therapists.edit');
  Route::post('/master/therapists/{id}/edit/confirm', [TherapistsController::class, 'editConfirm'])->name('therapists.edit.confirm');
  Route::post('/master/therapists/{id}/update', [TherapistsController::class, 'update'])->name('therapists.update');

  Route::delete('/master/therapists/{id}', [TherapistsController::class, 'destroy'])->name('therapists.delete');

  // ケアマネ情報
  Route::get('/master/caremanagers/index', [CareManagersController::class, 'index'])->name('caremanagers.index');
  Route::get('/master/caremanagers/create', [CareManagersController::class, 'create'])->name('caremanagers.create');
  Route::post('/master/caremanagers/confirm', [CareManagersController::class, 'confirm'])->name('caremanagers.confirm');
  Route::post('/master/caremanagers/store', [CareManagersController::class, 'store'])->name('caremanagers.store');

  Route::get('/master/caremanagers/{id}/edit', [CareManagersController::class, 'edit'])->name('caremanagers.edit');
  Route::post('/master/caremanagers/{id}/edit/confirm', [CareManagersController::class, 'editConfirm'])->name('caremanagers.edit.confirm');
  Route::post('/master/caremanagers/{id}/update', [CareManagersController::class, 'update'])->name('caremanagers.update');

  Route::delete('/master/caremanagers/{id}', [CareManagersController::class, 'destroy'])->name('caremanagers.delete');

  // 自社情報
  Route::get('/master/clinic-info/index', [CompanyInfoController::class, 'index'])->name('clinic-info.index');
  Route::post('/master/clinic-info/confirm', [CompanyInfoController::class, 'confirm'])->name('clinic-info.confirm');
  Route::post('/master/clinic-info/store', [CompanyInfoController::class, 'store'])->name('clinic-info.store');

  // サブマスター登録
  Route::get('/submaster/index', [SubMasterController::class, 'index'])->name('submaster.index');

  // 医療機関
  Route::get('/submaster/medical-institutions', [SubMasterController::class, 'medicalInstitutions'])->name('submaster.medical-institutions');
  Route::post('/submaster/medical-institutions', [SubMasterController::class, 'storeMedicalInstitution'])->name('submaster.medical-institutions.store');
  Route::post('/submaster/medical-institutions/{id}', [SubMasterController::class, 'updateMedicalInstitution'])->name('submaster.medical-institutions.update');
  Route::delete('/submaster/medical-institutions/{id}', [SubMasterController::class, 'destroyMedicalInstitution'])->name('submaster.medical-institutions.destroy');

  // サービス提供者
  Route::get('/submaster/service-providers', [SubMasterController::class, 'serviceProviders'])->name('submaster.service-providers');
  Route::post('/submaster/service-providers', [SubMasterController::class, 'storeServiceProvider'])->name('submaster.service-providers.store');
  Route::post('/submaster/service-providers/{id}', [SubMasterController::class, 'updateServiceProvider'])->name('submaster.service-providers.update');
  Route::delete('/submaster/service-providers/{id}', [SubMasterController::class, 'destroyServiceProvider'])->name('submaster.service-providers.destroy');

  // 状態
  Route::get('/submaster/conditions', [SubMasterController::class, 'conditions'])->name('submaster.conditions');
  Route::post('/submaster/conditions', [SubMasterController::class, 'storeCondition'])->name('submaster.conditions.store');
  Route::post('/submaster/conditions/{id}', [SubMasterController::class, 'updateCondition'])->name('submaster.conditions.update');
  Route::delete('/submaster/conditions/{id}', [SubMasterController::class, 'destroyCondition'])->name('submaster.conditions.destroy');

  // 疾病（あんま・マッサージ）
  Route::get('/submaster/illnesses-massage', [SubMasterController::class, 'illnessesMassage'])->name('submaster.illnesses-massage');
  Route::post('/submaster/illnesses-massage', [SubMasterController::class, 'storeIllnessMassage'])->name('submaster.illnesses-massage.store');
  Route::post('/submaster/illnesses-massage/{id}', [SubMasterController::class, 'updateIllnessMassage'])->name('submaster.illnesses-massage.update');
  Route::delete('/submaster/illnesses-massage/{id}', [SubMasterController::class, 'destroyIllnessMassage'])->name('submaster.illnesses-massage.destroy');

  // 文面編集
  Route::get('/master/documents/index', [DocumentController::class, 'index'])->name('master.documents.index');
  Route::get('/master/documents/create', [DocumentController::class, 'create'])->name('master.documents.create');
  Route::post('/master/documents/check-duplicate-name', [DocumentController::class, 'checkDuplicateName'])->name('master.documents.check-duplicate-name');
  Route::post('/master/documents', [DocumentController::class, 'store'])->name('master.documents.store');
  Route::get('/master/documents/{id}/edit', [DocumentController::class, 'edit'])->name('master.documents.edit');
  Route::post('/master/documents/{id}', [DocumentController::class, 'update'])->name('master.documents.update');
  Route::get('/master/documents/{id}/duplicate', [DocumentController::class, 'duplicate'])->name('master.documents.duplicate');
  Route::post('/master/documents/duplicate/store', [DocumentController::class, 'duplicateStore'])->name('master.documents.duplicate.store');
  Route::delete('/master/documents/{id}', [DocumentController::class, 'destroy'])->name('master.documents.destroy');
  Route::get('/master/documents/{id}/preview', [DocumentController::class, 'preview'])->name('master.documents.preview');

  // 施術料金編集
  Route::get('/master/treatment-fees/index', [TreatmentFeeController::class, 'index'])->name('master.treatment-fees.index');
  Route::get('/master/treatment-fees/create', [TreatmentFeeController::class, 'create'])->name('master.treatment-fees.create');
  Route::post('/master/treatment-fees', [TreatmentFeeController::class, 'store'])->name('master.treatment-fees.store');
  Route::get('/master/treatment-fees/{id}/edit', [TreatmentFeeController::class, 'edit'])->name('master.treatment-fees.edit');
  Route::put('/master/treatment-fees/{id}', [TreatmentFeeController::class, 'update'])->name('master.treatment-fees.update');
  Route::delete('/master/treatment-fees/{id}', [TreatmentFeeController::class, 'destroy'])->name('master.treatment-fees.destroy');

  // 自費施術料金編集
  Route::get('/master/self-fees/index', [SelfFeeController::class, 'index'])->name('master.self-fees.index');
  Route::post('/master/self-fees', [SelfFeeController::class, 'store'])->name('master.self-fees.store');
  Route::post('/master/self-fees/{id}', [SelfFeeController::class, 'update'])->name('master.self-fees.update');
  Route::delete('/master/self-fees/{id}', [SelfFeeController::class, 'destroy'])->name('master.self-fees.destroy');

  // 標準文書の確認および関連付け
  Route::get('/master/document-association/index', [DocumentAssociationController::class, 'index'])->name('master.document-association.index');
  Route::post('/master/document-association/{id}/associate', [DocumentAssociationController::class, 'associate'])->name('master.document-association.associate');

  // 利用者情報
  Route::get('/master/clinic-users/index', [ClinicUserController::class, 'index'])->name('clinic-users.index');
  Route::get('/master/clinic-users/create', [ClinicUserController::class, 'create'])->name('clinic-users.create');
  Route::post('/master/clinic-users/confirm', [ClinicUserController::class, 'confirm'])->name('clinic-users.confirm');
  Route::post('/master/clinic-users/store', [ClinicUserController::class, 'store'])->name('clinic-users.store');

  Route::get('/master/clinic-users/{id}/edit', [ClinicUserController::class, 'edit'])->name('clinic-users.edit');
  Route::post('/master/clinic-users/{id}/edit/confirm', [ClinicUserController::class, 'editConfirm'])->name('clinic-users.edit.confirm');
  Route::post('/master/clinic-users/{id}/edit/update', [ClinicUserController::class, 'update'])->name('clinic-users.edit.update');

  Route::delete('/master/clinic-users/{id}', [ClinicUserController::class, 'destroy'])->name('clinic-users.delete');

  // 保険情報
  Route::get('/master/clinic-users/{id}/insurances', [InsuranceController::class, 'index'])->name('clinic-users.insurances.index');
  Route::get('/master/clinic-users/{id}/insurances/create', [InsuranceController::class, 'create'])->name('clinic-users.insurances.create');
  Route::post('/master/clinic-users/{id}/insurances/confirm', [InsuranceController::class, 'confirm'])->name('clinic-users.insurances.confirm');
  Route::post('/master/clinic-users/{id}/insurances/store', [InsuranceController::class, 'store'])->name('clinic-users.insurances.store');

  Route::get('/master/clinic-users/{id}/insurances/{insurance_id}/edit', [InsuranceController::class, 'edit'])->name('clinic-users.insurances.edit');
  Route::post('/master/clinic-users/{id}/insurances/{insurance_id}/edit/confirm', [InsuranceController::class, 'editConfirm'])->name('clinic-users.insurances.edit.confirm');
  Route::post('/master/clinic-users/{id}/insurances/{insurance_id}/edit/update', [InsuranceController::class, 'update'])->name('clinic-users.insurances.edit.update');

  Route::get('/master/clinic-users/{id}/insurances/{insurance_id}/duplicate', [InsuranceController::class, 'duplicateForm'])->name('clinic-users.insurances.duplicate');
  Route::post('/master/clinic-users/{id}/insurances/{insurance_id}/duplicate/confirm', [InsuranceController::class, 'duplicateConfirm'])->name('clinic-users.insurances.duplicate.confirm');
  Route::post('/master/clinic-users/{id}/insurances/{insurance_id}/duplicate/store', [InsuranceController::class, 'duplicateStore'])->name('clinic-users.insurances.duplicate.store');

  Route::delete('/master/clinic-users/{id}/insurances/{insurance_id}', [InsuranceController::class, 'destroy'])->name('clinic-users.insurances.delete');
  Route::get('/master/clinic-users/{id}/insurances/print-history', [InsuranceController::class, 'print'])->name('clinic-users.insurances.print-history');

  // 同意医師履歴（あんま・マッサージ）
  Route::get('/master/clinic-users/{id}/consents-massage', [ConsentMassageController::class, 'index'])->name('clinic-users.consents-massage.index');
  Route::get('/master/clinic-users/{id}/consents-massage/create', [ConsentMassageController::class, 'create'])->name('clinic-users.consents-massage.create');
  Route::post('/master/clinic-users/{id}/consents-massage/confirm', [ConsentMassageController::class, 'confirm'])->name('clinic-users.consents-massage.confirm');
  Route::post('/master/clinic-users/{id}/consents-massage/store', [ConsentMassageController::class, 'store'])->name('clinic-users.consents-massage.store');

  Route::get('/master/clinic-users/{id}/consents-massage/{history_id}/edit', [ConsentMassageController::class, 'edit'])->name('clinic-users.consents-massage.edit');
  Route::post('/master/clinic-users/{id}/consents-massage/{history_id}/edit/confirm', [ConsentMassageController::class, 'editConfirm'])->name('clinic-users.consents-massage.edit.confirm');
  Route::post('/master/clinic-users/{id}/consents-massage/{history_id}/edit/update', [ConsentMassageController::class, 'update'])->name('clinic-users.consents-massage.edit.update');

  Route::get('/master/clinic-users/{id}/consents-massage/{history_id}/duplicate', [ConsentMassageController::class, 'duplicateForm'])->name('clinic-users.consents-massage.duplicate');
  Route::post('/master/clinic-users/{id}/consents-massage/{history_id}/duplicate/confirm', [ConsentMassageController::class, 'duplicateConfirm'])->name('clinic-users.consents-massage.duplicate.confirm');
  Route::post('/master/clinic-users/{id}/consents-massage/{history_id}/duplicate/store', [ConsentMassageController::class, 'duplicateStore'])->name('clinic-users.consents-massage.duplicate.store');

  Route::delete('/master/clinic-users/{id}/consents-massage/{history_id}', [ConsentMassageController::class, 'destroy'])->name('clinic-users.consents-massage.delete');
  Route::get('/master/clinic-users/{id}/consents-massage/print-history', [ConsentMassageController::class, 'print'])->name('clinic-users.consents-massage.print-history');

  // 同意医師履歴（鍼灸）
  Route::get('/master/clinic-users/{id}/consents-acupuncture', [ConsentAcupunctureController::class, 'index'])->name('clinic-users.consents-acupuncture.index');
  Route::get('/master/clinic-users/{id}/consents-acupuncture/create', [ConsentAcupunctureController::class, 'create'])->name('clinic-users.consents-acupuncture.registration');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/confirm', [ConsentAcupunctureController::class, 'confirm'])->name('clinic-users.consents-acupuncture.confirm');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/store', [ConsentAcupunctureController::class, 'store'])->name('clinic-users.consents-acupuncture.store');

  Route::get('/master/clinic-users/{id}/consents-acupuncture/{history_id}/edit', [ConsentAcupunctureController::class, 'edit'])->name('clinic-users.consents-acupuncture.edit');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/{history_id}/edit/confirm', [ConsentAcupunctureController::class, 'editConfirm'])->name('clinic-users.consents-acupuncture.edit.confirm');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/{history_id}/edit/update', [ConsentAcupunctureController::class, 'update'])->name('clinic-users.consents-acupuncture.edit.update');

  Route::get('/master/clinic-users/{id}/consents-acupuncture/{history_id}/duplicate', [ConsentAcupunctureController::class, 'duplicateForm'])->name('clinic-users.consents-acupuncture.duplicate');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/{history_id}/duplicate/confirm', [ConsentAcupunctureController::class, 'duplicateConfirm'])->name('clinic-users.consents-acupuncture.duplicate.confirm');
  Route::post('/master/clinic-users/{id}/consents-acupuncture/{history_id}/duplicate/store', [ConsentAcupunctureController::class, 'duplicateStore'])->name('clinic-users.consents-acupuncture.duplicate.store');

  Route::delete('/master/clinic-users/{id}/consents-acupuncture/{history_id}', [ConsentAcupunctureController::class, 'destroy'])->name('clinic-users.consents-acupuncture.delete');
  Route::get('/master/clinic-users/{id}/consents-acupuncture/print-history', [ConsentAcupunctureController::class, 'print'])->name('clinic-users.consents-acupuncture.print-history');

  // 計画情報
  Route::get('/master/clinic-users/{id}/plans', [PlanController::class, 'index'])->name('clinic-users.plans.index');
  Route::get('/master/clinic-users/{id}/plans/create', [PlanController::class, 'create'])->name('clinic-users.plans.create');
  Route::post('/master/clinic-users/{id}/plans/confirm', [PlanController::class, 'confirm'])->name('clinic-users.plans.confirm');
  Route::post('/master/clinic-users/{id}/plans/store', [PlanController::class, 'store'])->name('clinic-users.plans.store');

  Route::get('/master/clinic-users/{id}/plans/{plan_id}/edit', [PlanController::class, 'edit'])->name('clinic-users.plans.edit');
  Route::post('/master/clinic-users/{id}/plans/{plan_id}/edit/confirm', [PlanController::class, 'editConfirm'])->name('clinic-users.plans.edit.confirm');
  Route::post('/master/clinic-users/{id}/plans/{plan_id}/edit/update', [PlanController::class, 'update'])->name('clinic-users.plans.edit.update');

  Route::get('/master/clinic-users/{id}/plans/{plan_id}/duplicate', [PlanController::class, 'duplicateForm'])->name('clinic-users.plans.duplicate');
  Route::post('/master/clinic-users/{id}/plans/{plan_id}/duplicate/confirm', [PlanController::class, 'duplicateConfirm'])->name('clinic-users.plans.duplicate.confirm');
  Route::post('/master/clinic-users/{id}/plans/{plan_id}/duplicate/store', [PlanController::class, 'duplicateStore'])->name('clinic-users.plans.duplicate.store');

  Route::delete('/master/clinic-users/{id}/plans/{plan_id}', [PlanController::class, 'destroy'])->name('clinic-users.plans.delete');

  Route::get('/master/clinic-users/{id}/plans/print/history', [PlanController::class, 'printHistory'])->name('clinic-users.plans.print-history');

  // 実績データ
  Route::get('/records/index', [RecordsController::class, 'index'])->name('records.index');
  Route::post('/records/store', [RecordsController::class, 'store'])->name('records.store');
  Route::get('/records/{id}/edit', [RecordsController::class, 'edit'])->name('records.edit');
  Route::put('/records/{id}', [RecordsController::class, 'update'])->name('records.update');
  Route::get('/records/{id}/duplicate-current', [RecordsController::class, 'duplicateCurrentMonth'])->name('records.duplicate.current');
  Route::get('/records/{id}/duplicate-next', [RecordsController::class, 'duplicateNextMonth'])->name('records.duplicate.next');
  Route::post('/records/duplicate/store', [RecordsController::class, 'duplicateStore'])->name('records.duplicate.store');
  Route::post('/records/bulk-duplicate-next', [RecordsController::class, 'bulkDuplicateToNextMonth'])->name('records.bulk.duplicate.next');
  Route::delete('/records/{id}', [RecordsController::class, 'destroy'])->name('records.destroy');

  // 報告書データ
  Route::get('/reports/index', [ReportsController::class, 'index'])->name('reports.index');
  Route::get('/reports/create', [ReportsController::class, 'create'])->name('reports.create');
  Route::post('/reports/store', [ReportsController::class, 'store'])->name('reports.store');
  Route::get('/reports/{id}/edit', [ReportsController::class, 'edit'])->name('reports.edit');
  Route::put('/reports/{id}', [ReportsController::class, 'update'])->name('reports.update');
  Route::get('/reports/{id}/duplicate', [ReportsController::class, 'duplicate'])->name('reports.duplicate');
  Route::post('/reports/duplicate/store', [ReportsController::class, 'duplicateStore'])->name('reports.duplicate.store');
  Route::delete('/reports/{id}', [ReportsController::class, 'destroy'])->name('reports.destroy');

  // 入金管理
  Route::get('/deposits/index', [DepositsController::class, 'index'])->name('deposits.index');
  Route::get('/deposits/month/{yearMonth}', [DepositsController::class, 'getMonthData'])->name('deposits.getMonthData');
  Route::put('/deposits/{id}', [DepositsController::class, 'update'])->name('deposits.update');

  // スケジュール
  Route::get('/schedules/index', [ScheduleController::class, 'index'])->name('schedules.index');
  Route::get('/schedules/data', [ScheduleController::class, 'getData'])->name('schedules.data');

  // 利用者検索（共通）
  Route::get('/user-search', [UserSearchController::class, 'index'])->name('user.search');

  // 要加療期間リスト
  Route::get('/therapy-periods/index', [TherapyPeriodController::class, 'index'])->name('therapy-periods.index');

  // 印刷メニュー
  Route::get('/prints/index', [PrintsController::class, 'index'])->name('prints.index');
  Route::post('/prints/acupuncture-benefit/{filename}', [PrintsController::class, 'acupunctureBenefit'])->name('prints.acupuncture-benefit');
  Route::post('/prints/massage-benefit/{filename}', [PrintsController::class, 'massageBenefit'])->name('prints.massage-benefit');
  Route::post('/prints/treatment-receipt/{filename}', [PrintsController::class, 'treatmentReceipt'])->name('prints.treatment-receipt');
  Route::post('/prints/treatment-fee-list/{filename}', [PrintsController::class, 'treatmentFeeList'])->name('prints.treatment-fee-list');
  Route::post('/prints/self-fee-list/{filename}', [PrintsController::class, 'selfFeeList'])->name('prints.self-fee-list');
  Route::post('/prints/medical-assistance/{filename}', [PrintsController::class, 'medicalAssistance'])->name('prints.medical-assistance');
  Route::post('/prints/late-elderly-medical/{filename}', [PrintsController::class, 'lateElderlyMedical'])->name('prints.late-elderly-medical');
  Route::post('/prints/consent-request-sample/{filename}', [PrintsController::class, 'consentRequestSample'])->name('prints.consent-request-sample');
  Route::post('/prints/consent-request-designated/{filename}', [PrintsController::class, 'consentRequestDesignated'])->name('prints.consent-request-designated');
  Route::post('/prints/consent-form/{filename}', [PrintsController::class, 'consentForm'])->name('prints.consent-form');
  Route::post('/prints/treatment-record/{filename}', [PrintsController::class, 'treatmentRecord'])->name('prints.treatment-record');
  Route::post('/prints/summary-table/{filename}', [PrintsController::class, 'summaryTable'])->name('prints.summary-table');
  Route::post('/prints/payment-list/{filename}', [PrintsController::class, 'paymentList'])->name('prints.payment-list');
  Route::post('/prints/doctor-thank-you/{filename}', [PrintsController::class, 'doctorThankYou'])->name('prints.doctor-thank-you');
  Route::post('/prints/referrer-thank-you/{filename}', [PrintsController::class, 'referrerThankYou'])->name('prints.referrer-thank-you');
  Route::post('/prints/user-count-summary/{filename}', [PrintsController::class, 'userCountSummary'])->name('prints.user-count-summary');
  Route::post('/prints/implementation-plan/{filename}', [PrintsController::class, 'implementationPlan'])->name('prints.implementation-plan');
  Route::post('/prints/report-greeting/{filename}', [PrintsController::class, 'reportGreeting'])->name('prints.report-greeting');
  Route::post('/prints/report/{filename}', [PrintsController::class, 'report'])->name('prints.report');
  Route::post('/prints/schedule-list/{filename}', [PrintsController::class, 'scheduleList'])->name('prints.schedule-list');
  Route::get('/prints/weekly-schedule/{filename}', [PrintsController::class, 'weeklySchedule'])->name('prints.weekly-schedule');
  Route::get('/prints/monthly-schedule/{filename}', [PrintsController::class, 'monthlySchedule'])->name('prints.monthly-schedule');
  Route::post('/prints/treatment-expiry-list/{filename}', [PrintsController::class, 'treatmentExpiryList'])->name('prints.treatment-expiry-list');
  Route::get('/prints/clinic-user-consent-info-list/{filename}', [PrintsController::class, 'clinicUserConsentInfoList'])->name('prints.clinic-user-consent-info-list');
  Route::get('/prints/doctor-info-list/{filename}', [PrintsController::class, 'doctorInfoList'])->name('prints.doctor-info-list');
  Route::get('/prints/care-manager-info-list/{filename}', [PrintsController::class, 'careManagerInfoList'])->name('prints.care-manager-info-list');
  Route::get('/prints/therapist-info-list/{filename}', [PrintsController::class, 'therapistInfoList'])->name('prints.therapist-info-list');
  Route::get('/prints/user-info-basic-list/{filename}', [PrintsController::class, 'userInfoBasicList'])->name('prints.user-info-basic-list');
  Route::get('/prints/user-info-insurance-list/{filename}', [PrintsController::class, 'userInfoInsuranceList'])->name('prints.user-info-insurance-list');
  Route::get('/prints/fax-cover-sheet/{filename}', [PrintsController::class, 'faxCoverSheet'])->name('prints.fax-cover-sheet');
  Route::get('/prints/address-label-csv', [PrintsController::class, 'addressLabelCsv'])->name('prints.address-label-csv');
  Route::get('/prints/address-label-pdf/{filename}', [PrintsController::class, 'addressLabelPdf'])->name('prints.address-label-pdf');
  Route::get('/prints/first-experience-material', [PrintsController::class, 'firstExperienceMaterial'])->name('prints.first-experience-material');
  Route::get('/prints/power-of-attorney-application', [PrintsController::class, 'powerOfAttorneyApplication'])->name('prints.power-of-attorney-application');
  Route::get('/prints/power-of-attorney-consent', [PrintsController::class, 'powerOfAttorneyConsent'])->name('prints.power-of-attorney-consent');

  // PDFレイアウト調整ツール
  Route::get('/prints/coordinate-adjuster', [PrintsController::class, 'coordinateAdjuster'])->name('prints.coordinate-adjuster');
  Route::get('/prints/get-coordinates', [PrintsController::class, 'getCoordinates'])->name('prints.get-coordinates');
  Route::post('/prints/save-coordinates', [PrintsController::class, 'saveCoordinates'])->name('prints.save-coordinates');
  Route::post('/prints/preview-pdf', [PrintsController::class, 'previewPdf'])->name('prints.preview-pdf');
  Route::get('/prints/get-treatment-days', [PrintsController::class, 'getTreatmentDays'])->name('prints.get-treatment-days');
});

require __DIR__.'/auth.php';
