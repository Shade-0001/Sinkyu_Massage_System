<x-app-layout>
  @php
    $page_header_title = '印刷メニュー';
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('prints.index')"
  />

  <br>

  <h3>はり・きゅう関連</h3>
  <button type="button" class="btn btn-primary" onclick="openAcupunctureBenefitModal()">療養費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentReceiptModal('acupuncture')">施術料金領収書</button>
  <button type="button" class="btn btn-primary" onclick="openMedicalAssistanceModal('acupuncture')">医療助成費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openLateElderlyMedicalModal('acupuncture')">後期高齢者医療療養費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openConsentRequestSampleModal('acupuncture')">同意書依頼状 (サンプル版)</button>
  <button type="button" class="btn btn-primary" onclick="openConsentRequestDesignatedModal('acupuncture')">同意書依頼状 (医師指定)</button>
  <button type="button" class="btn btn-primary" onclick="openConsentFormModal('acupuncture')">同意書</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentFeeListModal('acupuncture')">施術料金一覧表(保険)</button>
  <button type="button" class="btn btn-primary" onclick="openSelfFeeListModal()">施術料金一覧表(自費)</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentRecordModal('acupuncture')">施術録</button>
  <button type="button" class="btn btn-primary" onclick="openSummaryTableModal('acupuncture')">総括表</button>
  <br><br>

  <h3>あんま・マッサージ関連</h3>
  <button type="button" class="btn btn-primary" onclick="openMassageBenefitModal()">療養費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentReceiptModal('massage')">施術料金領収書</button>
  <button type="button" class="btn btn-primary" onclick="openMedicalAssistanceModal('massage')">医療助成費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openLateElderlyMedicalModal('massage')">後期高齢者医療療養費支給申請書</button>
  <button type="button" class="btn btn-primary" onclick="openConsentRequestSampleModal('massage')">同意書依頼状 (サンプル版)</button>
  <button type="button" class="btn btn-primary" onclick="openConsentRequestDesignatedModal('massage')">同意書依頼状 (医師指定)</button>
  <button type="button" class="btn btn-primary" onclick="openConsentFormModal('massage')">同意書</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentFeeListModal('massage')">施術料金一覧表(保険)</button>
  <button type="button" class="btn btn-primary" onclick="openSelfFeeListModal()">施術料金一覧表(自費)</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentRecordModal('massage')">施術録</button>
  <button type="button" class="btn btn-primary" onclick="openSummaryTableModal('massage')">総括表</button>
  <br><br>

  <h3>その他１</h3>
  <button type="button" class="btn btn-primary" onclick="submitFirstExperienceMaterial()">初回体験用資料</button>
  <button type="button" class="btn btn-primary" onclick="submitPowerOfAttorneyApplication()">委任状（申請・受領）</button>
  <button type="button" class="btn btn-primary" onclick="submitPowerOfAttorneyConsent()">委任状（同意書取得）</button>
  <button type="button" class="btn btn-primary" onclick="openPaymentListModal()">入金管理表（保険）</button>
  <button type="button" class="btn btn-primary" onclick="openDoctorThankYouModal()">医師への御礼状</button>
  <button type="button" class="btn btn-primary" onclick="openReferrerThankYouModal()">紹介者への御礼状</button>
  <button type="button" class="btn btn-primary" onclick="openUserCountSummaryModal()">利用者数集計表</button>
  <button type="button" class="btn btn-primary" onclick="openImplementationPlanModal()">実施計画書</button>
  <button type="button" class="btn btn-primary" onclick="openReportGreetingModal()">報告書挨拶文</button>
  <button type="button" class="btn btn-primary" onclick="openReportModal()">報告書</button>
  <button type="button" class="btn btn-primary" onclick="openScheduleListModal()">予定表</button>
  <button type="button" class="btn btn-primary" onclick="openTreatmentExpiryListModal()">要加療期限切れリスト</button>
  <br><br>

  <h3>その他２</h3>
  <button type="button" class="btn btn-primary" onclick="submitUserInfoBasicList()">利用者情報一覧（基本情報）</button>
  <button type="button" class="btn btn-primary" onclick="submitUserInfoInsuranceList()">利用者情報一覧（医療保険情報）</button>
  <button type="button" class="btn btn-primary" onclick="submitClinicUserConsentInfoList()">利用者情報一覧（同意医師情報）</button>
  <button type="button" class="btn btn-primary" onclick="submitDoctorInfoList()">医師情報一覧</button>
  <button type="button" class="btn btn-primary" onclick="submitCareManagerInfoList()">ケアマネ情報一覧</button>
  <button type="button" class="btn btn-primary" onclick="submitTherapistInfoList()">施術者情報一覧</button>
  <button type="button" class="btn btn-primary" onclick="openAddressLabelModal()">宛名シール・住所データCSV出力</button>
  <button type="button" class="btn btn-primary" onclick="submitFaxCoverSheet()">FAX送信票表示</button>

  <!-- はり・きゅう療養費支給申請書モーダル -->
  <div class="modal fade" id="acupunctureBenefitModal" tabindex="-1" aria-labelledby="acupunctureBenefitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="acupunctureBenefitModalLabel">はり・きゅう療養費支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="acupunctureBenefitForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitAcupunctureBenefit()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 施術料金領収書モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="treatmentReceiptModal" tabindex="-1" aria-labelledby="treatmentReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="treatmentReceiptModalLabel">施術料金領収書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="treatmentReceiptForm" method="POST">
            @csrf
            <input type="hidden" id="receipt_type" name="receipt_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="receipt_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="receipt_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="receipt_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('receipt_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="receipt_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 施術報告書交付料 -->
            <div class="mb-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="report_fee" name="include_report_fee" value="1">
                <label class="form-check-label" for="report_fee">施術報告書交付料あり</label>
              </div>
            </div>

            <!-- 備考 -->
            <div class="mb-3">
              <label for="receipt_remarks" class="form-label">備考</label>
              <textarea class="form-control" id="receipt_remarks" name="remarks" rows="3"></textarea>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="receipt_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="receipt_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitTreatmentReceipt()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- あんま・マッサージ療養費支給申請書モーダル -->
  <div class="modal fade" id="massageBenefitModal" tabindex="-1" aria-labelledby="massageBenefitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="massageBenefitModalLabel">あんま・マッサージ療養費支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="massageBenefitForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="massage_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="massage_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="massage_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('massage_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="massage_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="massage_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="massage_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitMassageBenefit()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 医療助成費支給申請書モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="medicalAssistanceModal" tabindex="-1" aria-labelledby="medicalAssistanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="medicalAssistanceModalLabel">医療助成費支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="medicalAssistanceForm" method="POST">
            @csrf
            <input type="hidden" id="medical_assistance_type" name="assistance_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="medical_assistance_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="medical_assistance_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="medical_assistance_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('medical_assistance_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="medical_assistance_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- オプション -->
            <div class="mb-3">
              <label class="form-label">オプション</label>
              <div class="form-check">
                <input class="form-check-input signature-option-checkbox" type="checkbox" name="signature_option" id="signature_option_1" value="user_signature_blank">
                <label class="form-check-label" for="signature_option_1">
                  利用者署名欄空白
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input signature-option-checkbox" type="checkbox" name="signature_option" id="signature_option_2" value="user_address_signature_blank">
                <label class="form-check-label" for="signature_option_2">
                  利用者住所・署名欄空白
                </label>
              </div>
            </div>

            <!-- 提出年月 -->
            <div class="mb-3">
              <label for="medical_assistance_submission_month" class="form-label">提出年月 <span class="text-danger">*</span></label>
              <input type="month" class="form-control" id="medical_assistance_submission_month" name="submission_month" value="{{ now()->format('Y-m') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitMedicalAssistance()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 後期高齢者医療療養費支給申請書モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="lateElderlyMedicalModal" tabindex="-1" aria-labelledby="lateElderlyMedicalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lateElderlyMedicalModalLabel">後期高齢者医療療養費支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="lateElderlyMedicalForm" method="POST">
            @csrf
            <input type="hidden" id="late_elderly_medical_type" name="assistance_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="late_elderly_medical_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="late_elderly_medical_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="late_elderly_medical_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('late_elderly_medical_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="late_elderly_medical_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- オプション -->
            <div class="mb-3">
              <label class="form-label">オプション</label>
              <div class="form-check">
                <input class="form-check-input late-elderly-signature-option-checkbox" type="checkbox" name="signature_option" id="late_elderly_signature_option_1" value="user_signature_blank">
                <label class="form-check-label" for="late_elderly_signature_option_1">
                  利用者署名欄空白
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input late-elderly-signature-option-checkbox" type="checkbox" name="signature_option" id="late_elderly_signature_option_2" value="user_address_signature_blank">
                <label class="form-check-label" for="late_elderly_signature_option_2">
                  利用者住所・署名欄空白
                </label>
              </div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="late_elderly_medical_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="late_elderly_medical_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitLateElderlyMedical()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 施術料金一覧表（保険扱い）モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="treatmentFeeListModal" tabindex="-1" aria-labelledby="treatmentFeeListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="treatmentFeeListModalLabel">施術料金一覧表（保険扱い） 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="treatmentFeeListForm" method="POST">
            @csrf
            <input type="hidden" id="fee_list_type" name="receipt_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="fee_list_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="fee_list_service_year_month" name="service_year_month" required>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitTreatmentFeeList()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 施術料金一覧表（自費）モーダル -->
  <div class="modal fade" id="selfFeeListModal" tabindex="-1" aria-labelledby="selfFeeListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="selfFeeListModalLabel">施術料金一覧表（自費） 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="selfFeeListForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="self_fee_list_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="self_fee_list_service_year_month" name="service_year_month" required>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitSelfFeeList()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 同意書依頼状（サンプル版）モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="consentRequestSampleModal" tabindex="-1" aria-labelledby="consentRequestSampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="consentRequestSampleModalLabel">同意書依頼状（サンプル版） 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="consentRequestSampleForm" method="POST">
            @csrf
            <input type="hidden" id="consent_request_sample_type" name="consent_request_type" value="">

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="consent_request_sample_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('consent_request_sample_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="consent_request_sample_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="consent_request_sample_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="consent_request_sample_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitConsentRequestSample()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 同意書依頼状（医師指定）モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="consentRequestDesignatedModal" tabindex="-1" aria-labelledby="consentRequestDesignatedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="consentRequestDesignatedModalLabel">同意書依頼状（医師指定） 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="consentRequestDesignatedForm" method="POST">
            @csrf
            <input type="hidden" id="consent_request_designated_type" name="consent_request_type" value="">

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="consent_request_designated_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('consent_request_designated_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="consent_request_designated_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 医師選択 -->
            <div class="mb-3">
              <label for="consent_request_designated_doctor_ids" class="form-label">医師 <span class="text-danger">*</span></label>
              <select class="form-select" id="consent_request_designated_doctor_ids" name="doctor_ids[]" size="10" required>
                @foreach($doctors as $doctor)
                  <option value="{{ $doctor->id }}">
                    {{ $doctor->last_name }}{{ "\u{2000}" }}{{ $doctor->first_name }}（{{ $doctor->last_name_kana }}{{ "\u{2000}" }}{{ $doctor->first_name_kana }}）
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="consent_request_designated_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="consent_request_designated_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitConsentRequestDesignated()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 施術録モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="treatmentRecordModal" tabindex="-1" aria-labelledby="treatmentRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="treatmentRecordModalLabel">施術録 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="treatmentRecordForm" method="POST">
            @csrf
            <input type="hidden" id="treatment_record_type" name="record_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="treatment_record_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="treatment_record_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="treatment_record_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('treatment_record_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="treatment_record_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="treatment_record_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="treatment_record_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitTreatmentRecord()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 総括表モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="summaryTableModal" tabindex="-1" aria-labelledby="summaryTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="summaryTableModalLabel">総括表 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="summaryTableForm" method="POST">
            @csrf
            <input type="hidden" id="summary_table_type" name="summary_type" value="">

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="summary_table_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="summary_table_service_year_month" name="service_year_month" required
                data-acupuncture-months="{{ json_encode($summaryTableDataMonths['acupuncture']) }}"
                data-massage-months="{{ json_encode($summaryTableDataMonths['massage']) }}">
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="summary_table_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="summary_table_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitSummaryTable()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 入金管理表（保険）モーダル -->
  <div class="modal fade" id="paymentListModal" tabindex="-1" aria-labelledby="paymentListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="paymentListModalLabel">入金管理表（保険） 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="paymentListForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="payment_list_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="payment_list_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitPaymentList()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 同意書モーダル（はり・きゅう / あんま・マッサージ共通） -->
  <div class="modal fade" id="consentFormModal" tabindex="-1" aria-labelledby="consentFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="consentFormModalLabel">同意書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="consentFormForm" method="POST">
            @csrf
            <input type="hidden" id="consent_form_type" name="consent_form_type" value="">

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="consent_form_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('consent_form_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="consent_form_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 同意区分 -->
            <div class="mb-3">
              <label class="form-label">同意区分 <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="consent_category" id="consent_category_new" value="new" checked>
                  <label class="form-check-label" for="consent_category_new">
                    新規同意
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="consent_category" id="consent_category_renewal" value="renewal">
                  <label class="form-check-label" for="consent_category_renewal">
                    再同意
                  </label>
                </div>
              </div>
            </div>

            <!-- オプション -->
            <div class="mb-3">
              <label class="form-label">オプション</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="consent_form_option" id="consent_form_option_doctor_blank" value="doctor_info_blank">
                <label class="form-check-label" for="consent_form_option_doctor_blank">
                  医師情報空白
                </label>
              </div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="consent_form_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="consent_form_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitConsentForm()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 医師への御礼状モーダル -->
  <div class="modal fade" id="doctorThankYouModal" tabindex="-1" aria-labelledby="doctorThankYouModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="doctorThankYouModalLabel">医師への御礼状 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="doctorThankYouForm" method="POST">
            @csrf

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="doctor_thank_you_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('doctor_thank_you_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="doctor_thank_you_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- 医師選択 -->
            <div class="mb-3">
              <label for="doctor_thank_you_doctor_id" class="form-label">医師 <span class="text-danger">*</span></label>
              <select class="form-select" id="doctor_thank_you_doctor_id" name="doctor_id" size="10" required>
                @foreach($doctors as $doctor)
                  <option value="{{ $doctor->id }}">
                    {{ $doctor->last_name }}{{ "\u{2000}" }}{{ $doctor->first_name }}（{{ $doctor->last_name_kana }}{{ "\u{2000}" }}{{ $doctor->first_name_kana }}）
                  </option>
                @endforeach
              </select>
            </div>

            <!-- オプション -->
            <div class="mb-3">
              <label class="form-label">オプション <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="thank_you_option" id="thank_you_option_consent" value="consent" checked>
                  <label class="form-check-label" for="thank_you_option_consent">同意書発行</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="thank_you_option" id="thank_you_option_general" value="general">
                  <label class="form-check-label" for="thank_you_option_general">一般</label>
                </div>
              </div>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="doctor_thank_you_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="doctor_thank_you_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitDoctorThankYou()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 紹介者への御礼状モーダル -->
  <div class="modal fade" id="referrerThankYouModal" tabindex="-1" aria-labelledby="referrerThankYouModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="referrerThankYouModalLabel">紹介者への御礼状 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="referrerThankYouForm" method="POST">
            @csrf

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="referrer_thank_you_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('referrer_thank_you_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="referrer_thank_you_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>

            <!-- ケアマネ選択 -->
            <div class="mb-3">
              <label for="referrer_thank_you_caremanager_id" class="form-label">ケアマネ <span class="text-danger">*</span></label>
              <select class="form-select" id="referrer_thank_you_caremanager_id" name="caremanager_id" size="10" required>
                @foreach($caremanagers as $cm)
                  <option value="{{ $cm->id }}">
                    {{ $cm->last_name }}{{ "\u{2000}" }}{{ $cm->first_name }}（{{ $cm->last_name_kana }}{{ "\u{2000}" }}{{ $cm->first_name_kana }}）
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="referrer_thank_you_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="referrer_thank_you_submission_date" name="submission_date" value="{{ now()->format('Y-m-d') }}" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitReferrerThankYou()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 実施計画書モーダル -->
  <div class="modal fade" id="implementationPlanModal" tabindex="-1" aria-labelledby="implementationPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="implementationPlanModalLabel">実施計画書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="implementationPlanForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="implementation_plan_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="implementation_plan_service_year_month" name="service_year_month" required onchange="updateImplementationPlanUserLabels()">
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="implementation_plan_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('implementation_plan_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="implementation_plan_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}" data-plan-months="{{ json_encode($implementationPlanUserMonths[$user->id] ?? []) }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitImplementationPlan()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 報告書モーダル -->
  <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reportModalLabel">報告書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="reportForm" method="POST">
            @csrf

            <!-- 利用者 -->
            <div class="mb-3">
              <label for="report_clinic_user_id" class="form-label">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
              <select class="form-select" id="report_clinic_user_id" name="clinic_user_id" required>
                <option value="">選択してください</option>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
            </div>

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="report_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="report_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $reportCurrentDate = now();
                  for ($i = -1; $i < 23; $i++) {
                    $reportDate = $reportCurrentDate->copy()->subMonths($i);
                    $reportValue = $reportDate->format('Y-m');
                    $reportM = (int)$reportDate->format('n');
                    $reportDisplay = $reportDate->format('Y年') . ($reportM < 10 ? "\u{00A0}\u{00A0}" : '') . $reportM . '月';
                    $reportSelected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$reportValue}\" {$reportSelected}>{$reportDisplay}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="report_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="report_submission_date" name="submission_date" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitReport()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 報告書挨拶文モーダル -->
  <div class="modal fade" id="reportGreetingModal" tabindex="-1" aria-labelledby="reportGreetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reportGreetingModalLabel">報告書挨拶文 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="reportGreetingForm" method="POST">
            @csrf

            <!-- オプション -->
            <div class="mb-3">
              <label class="form-label">オプション <span class="text-danger">*</span></label>
              <div class="d-flex gap-4">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="greeting_type" id="greeting_type_doctor" value="doctor" required checked onchange="updateReportGreetingFields()">
                  <label class="form-check-label" for="greeting_type_doctor">医師向け</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="greeting_type" id="greeting_type_caremanager" value="caremanager" onchange="updateReportGreetingFields()">
                  <label class="form-check-label" for="greeting_type_caremanager">ケアマネ向け</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="greeting_type" id="greeting_type_user" value="user" onchange="updateReportGreetingFields()">
                  <label class="form-check-label" for="greeting_type_user">利用者向け</label>
                </div>
              </div>
            </div>

            <!-- 利用者 -->
            <div class="mb-3">
              <label for="report_greeting_clinic_user_id" class="form-label">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
              <select class="form-select" id="report_greeting_clinic_user_id" name="clinic_user_id" required>
                <option value="">選択してください</option>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
            </div>

            <!-- 医師 -->
            <div class="mb-3" id="report_greeting_doctor_section">
              <label for="report_greeting_doctor_id" class="form-label">医師 <span class="text-danger">*</span></label>
              <select class="form-select" id="report_greeting_doctor_id" name="doctor_id" required>
                <option value="">選択してください</option>
                @foreach($doctors as $doctor)
                  <option value="{{ $doctor->id }}">
                    {{ $doctor->last_name }}{{ "\u{2000}" }}{{ $doctor->first_name }}（{{ $doctor->last_name_kana }}{{ "\u{2000}" }}{{ $doctor->first_name_kana }}）
                  </option>
                @endforeach
              </select>
            </div>

            <!-- ケアマネ -->
            <div class="mb-3 d-none" id="report_greeting_caremanager_section">
              <label for="report_greeting_caremanager_id" class="form-label">ケアマネ <span class="text-danger">*</span></label>
              <select class="form-select" id="report_greeting_caremanager_id" name="caremanager_id">
                <option value="">選択してください</option>
                @foreach($caremanagers as $cm)
                  <option value="{{ $cm->id }}">
                    {{ $cm->last_name }}{{ "\u{2000}" }}{{ $cm->first_name }}（{{ $cm->last_name_kana }}{{ "\u{2000}" }}{{ $cm->first_name_kana }}）
                  </option>
                @endforeach
              </select>
            </div>

            <!-- 提出年月日 -->
            <div class="mb-3">
              <label for="report_greeting_submission_date" class="form-label">提出年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="report_greeting_submission_date" name="submission_date" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitReportGreeting()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 利用者数集計表モーダル -->
  <div class="modal fade" id="userCountSummaryModal" tabindex="-1" aria-labelledby="userCountSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="userCountSummaryModalLabel">利用者数集計表 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="userCountSummaryForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="user_count_summary_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="user_count_summary_service_year_month" name="service_year_month" required
                data-months="{{ json_encode($userCountDataMonths) }}">
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = 0; $i < 24; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitUserCountSummary()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 予定表モーダル -->
  <div class="modal fade" id="scheduleListModal" tabindex="-1" aria-labelledby="scheduleListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="scheduleListModalLabel">予定表 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="scheduleListForm" method="POST">
            @csrf

            <!-- サービス提供年月 -->
            <div class="mb-3">
              <label for="schedule_list_service_year_month" class="form-label">サービス提供年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="schedule_list_service_year_month" name="service_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = -1; $i < 23; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === -1) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 利用者選択 -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="schedule_list_clinic_user_ids" class="form-label mb-0">利用者｜［ID］氏名（カナ） <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleSelectAll('schedule_list_clinic_user_ids')">全て選択 / 解除</button>
              </div>
              <select class="form-select" id="schedule_list_clinic_user_ids" name="clinic_user_ids[]" multiple size="10" required>
                @foreach($clinicUsers as $user)
                  <option value="{{ $user->id }}">{{ str_repeat("\u{00A0}", max(0, (3 - strlen((string)$user->id)) * 2)) . '［' . $user->id . '］' . $user->last_name . "\u{2000}" . $user->first_name . '（' . $user->last_kana . "\u{2000}" . $user->first_kana . '）' }}</option>
                @endforeach
              </select>
              <div class="form-text">複数選択可（クリックで選択/解除、長押し+ドラッグで連続選択）</div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitScheduleList()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 要加療期限切れリストモーダル -->
  <div class="modal fade" id="treatmentExpiryListModal" tabindex="-1" aria-labelledby="treatmentExpiryListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="treatmentExpiryListModalLabel">要加療期限切れリスト 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="treatmentExpiryListForm" method="POST">
            @csrf

            <!-- 対象年月 -->
            <div class="mb-3">
              <label for="treatment_expiry_list_target_year_month" class="form-label">対象年月 <span class="text-danger">*</span></label>
              <select class="form-select" id="treatment_expiry_list_target_year_month" name="target_year_month" required>
                <option value="">選択してください</option>
                @php
                  $currentDate = now();
                  for ($i = -1; $i < 23; $i++) {
                    $date = $currentDate->copy()->subMonths($i);
                    $value = $date->format('Y-m');
                    $m = (int)$date->format('n');
                    $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                    $selected = ($i === 0) ? 'selected' : '';
                    echo "<option value=\"{$value}\" {$selected}>{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 出力年月日 -->
            <div class="mb-3">
              <label for="treatment_expiry_list_output_date" class="form-label">出力年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="treatment_expiry_list_output_date" name="output_date" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitTreatmentExpiryList()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 宛名シール・住所データCSV出力モーダル -->
  <div class="modal fade" id="addressLabelModal" tabindex="-1" aria-labelledby="addressLabelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addressLabelModalLabel">宛名シール・住所データCSV出力 設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addressLabelForm">
            <!-- 出力データ -->
            <div class="mb-3">
              <label for="address_label_data_type" class="form-label">出力データ <span class="text-danger">*</span></label>
              <select class="form-select" id="address_label_data_type" name="data_type" required>
                <option value="clinic_user">利用者関連</option>
                <option value="doctor">医師関連</option>
                <option value="insurer">保険者関連</option>
                <option value="caremanager">ケアマネ関連</option>
              </select>
            </div>

            <!-- 出力方式 -->
            <div class="mb-3">
              <label for="address_label_output_type" class="form-label">出力方式 <span class="text-danger">*</span></label>
              <select class="form-select" id="address_label_output_type" name="output_type" required>
                <option value="csv">CSV</option>
                <option value="label_12">宛名シール（12面）</option>
                <option value="label_10">宛名シール（10面）</option>
              </select>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitAddressLabel()">出力</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    @push('styles')
      <style>
        /* ensure modals sit above page content */
        .modal { z-index: 2000; }
        .modal-backdrop { z-index: 1990; }
        .modal .modal-content { background-color: #fff; }
        /* 利用者選択リストのスクロールアニメーションを無効化 */
        #clinic_user_ids,
        #massage_clinic_user_ids,
        #receipt_clinic_user_ids,
        #medical_assistance_clinic_user_ids,
        #late_elderly_medical_clinic_user_ids,
        #consent_request_sample_clinic_user_ids,
        #consent_request_designated_clinic_user_ids,
        #consent_request_designated_doctor_ids,
        #consent_form_clinic_user_ids,
        #treatment_record_clinic_user_ids,
        #doctor_thank_you_clinic_user_ids,
        #doctor_thank_you_doctor_id,
        #referrer_thank_you_clinic_user_ids,
        #implementation_plan_clinic_user_ids,
        #report_greeting_clinic_user_id,
        #report_greeting_doctor_id,
        #report_greeting_caremanager_id,
        #referrer_thank_you_caremanager_id,
        #schedule_list_clinic_user_ids {
          scroll-behavior: auto;
        }
      </style>
    @endpush
    <script src="{{ asset('js/prints.js') }}"></script>
  @endpush
</x-app-layout>
