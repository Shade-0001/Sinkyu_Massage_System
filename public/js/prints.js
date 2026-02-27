// public/js/prints.js

/**
 * 初回体験用資料PDF出力
 */
function submitFirstExperienceMaterial() {
  window.open('/prints/first-experience-material', '_blank');
}

/**
 * 委任状（申請･受領）PDF出力
 */
function submitPowerOfAttorneyApplication() {
  window.open('/prints/power-of-attorney-application', '_blank');
}

/**
 * 委任状（同意書取得）PDF出力
 */
function submitPowerOfAttorneyConsent() {
  window.open('/prints/power-of-attorney-consent', '_blank');
}

/**
 * select要素の高さをオプション数に合わせて調整（最大10行）
 * @param {string} selectId - select要素のID
 */
function adjustSelectSize(selectId) {
  const select = document.getElementById(selectId);
  if (!select) return;
  select.size = Math.min(select.options.length, 10) || 1;
}

/**
 * 複数選択リストでクリックによるトグル選択＆長押しドラッグ選択を有効化
 * @param {string} selectId - select要素のID
 */
function enableClickToggleSelect(selectId) {
  const select = document.getElementById(selectId);
  if (!select) return;

  let isDragging = false;
  let dragStartOption = null;
  let dragSelectMode = null; // true: 選択モード, false: 解除モード
  let longPressTimer = null;
  let isLongPress = false;
  const LONG_PRESS_DURATION = 0; // 長押し判定時間(ms)

  select.addEventListener('mousedown', function(e) {
    if (e.target.tagName === 'OPTION') {
      e.preventDefault();
      const option = e.target;

      // 長押し判定タイマー開始
      isLongPress = false;
      longPressTimer = setTimeout(() => {
        isLongPress = true;
        isDragging = true;
        dragStartOption = option;
        // 長押し開始時点の選択状態の逆をドラッグモードとする
        dragSelectMode = !option.selected;
        const scrollTop = select.scrollTop;
        option.selected = dragSelectMode;
        // 複数タイミングで復元を試みる
        select.scrollTop = scrollTop;
        requestAnimationFrame(() => select.scrollTop = scrollTop);
        setTimeout(() => select.scrollTop = scrollTop, 0);
        setTimeout(() => select.scrollTop = scrollTop, 10);
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }, LONG_PRESS_DURATION);

      // フォーカスを維持（スクロール位置を変えない）
      select.focus({ preventScroll: true });
    }
  });

  select.addEventListener('mousemove', function(e) {
    if (isDragging && e.target.tagName === 'OPTION') {
      const option = e.target;
      if (option.selected !== dragSelectMode) {
        const scrollTop = select.scrollTop;
        option.selected = dragSelectMode;
        // 複数タイミングで復元を試みる
        select.scrollTop = scrollTop;
        requestAnimationFrame(() => select.scrollTop = scrollTop);
        setTimeout(() => select.scrollTop = scrollTop, 0);
        setTimeout(() => select.scrollTop = scrollTop, 10);
        select.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  });

  select.addEventListener('mouseup', function(e) {
    // 長押しタイマーをクリア
    if (longPressTimer) {
      clearTimeout(longPressTimer);
      longPressTimer = null;
    }

    // 長押しでなければ通常のクリックトグル
    if (!isLongPress && e.target.tagName === 'OPTION') {
      const option = e.target;
      const scrollTop = select.scrollTop;
      option.selected = !option.selected;
      // 複数タイミングで復元を試みる
      select.scrollTop = scrollTop;
      requestAnimationFrame(() => select.scrollTop = scrollTop);
      setTimeout(() => select.scrollTop = scrollTop, 0);
      setTimeout(() => select.scrollTop = scrollTop, 10);
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // ドラッグ状態をリセット
    isDragging = false;
    dragStartOption = null;
    dragSelectMode = null;
    isLongPress = false;
  });

  select.addEventListener('mouseleave', function() {
    // マウスがselect外に出たらドラッグ終了
    if (longPressTimer) {
      clearTimeout(longPressTimer);
      longPressTimer = null;
    }
    isDragging = false;
    dragStartOption = null;
    dragSelectMode = null;
    isLongPress = false;
  });
}

// ページ読み込み時に利用者選択リストのトグル機能を有効化
document.addEventListener('DOMContentLoaded', function() {
  enableClickToggleSelect('clinic_user_ids');
  enableClickToggleSelect('massage_clinic_user_ids');
  enableClickToggleSelect('receipt_clinic_user_ids');
  enableClickToggleSelect('medical_assistance_clinic_user_ids');
  enableClickToggleSelect('late_elderly_medical_clinic_user_ids');
  enableClickToggleSelect('consent_request_sample_clinic_user_ids');
  enableClickToggleSelect('consent_request_designated_clinic_user_ids');
  enableClickToggleSelect('consent_request_designated_doctor_ids');
  enableClickToggleSelect('consent_form_clinic_user_ids');
  enableClickToggleSelect('treatment_record_clinic_user_ids');
  enableClickToggleSelect('doctor_thank_you_clinic_user_ids');
  enableClickToggleSelect('referrer_thank_you_clinic_user_ids');
});

/**
 * フォームをデフォルト状態にリセット
 * @param {string} formId - フォーム要素のID
 */
function resetFormToDefault(formId) {
  const form = document.getElementById(formId);
  if (!form) return;

  // フォームをリセット
  form.reset();

  // 複数選択のselectは全て未選択に
  const multiSelects = form.querySelectorAll('select[multiple]');
  multiSelects.forEach(select => {
    Array.from(select.options).forEach(option => {
      option.selected = false;
    });
  });
}

/**
 * 利用者選択の全選択/全解除をトグル
 * @param {string} selectId - select要素のID
 */
function toggleSelectAll(selectId) {
  const select = document.getElementById(selectId);
  if (!select) return;

  const options = Array.from(select.options);
  const selectedCount = options.filter(opt => opt.selected).length;
  const allSelected = selectedCount === options.length;

  // 全て選択されている場合は全解除、そうでなければ全選択
  options.forEach(option => {
    option.selected = !allSelected;
  });

  // changeイベントを発火
  select.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * はり・きゅう療養費支給申請書モーダルを開く
 */
function openAcupunctureBenefitModal() {
  const modalElement = document.getElementById('acupunctureBenefitModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('acupunctureBenefitForm');

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * はり・きゅう療養費支給申請書PDF出力
 */
function submitAcupunctureBenefit() {
  const form = document.getElementById('acupunctureBenefitForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const filename = `療養費支給申請書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/acupuncture-benefit/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('acupunctureBenefitModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * あんま・マッサージ療養費支給申請書モーダルを開く
 */
function openMassageBenefitModal() {
  const modalElement = document.getElementById('massageBenefitModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('massageBenefitForm');

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('massage_submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('massage_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * あんま・マッサージ療養費支給申請書PDF出力
 */
function submitMassageBenefit() {
  const form = document.getElementById('massageBenefitForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const filename = `あんま・マッサージ療養費支給申請書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/massage-benefit/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('massageBenefitModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 施術料金領収書モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openTreatmentReceiptModal(type) {
  const modalElement = document.getElementById('treatmentReceiptModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('treatmentReceiptForm');

  // タイプを設定
  const receiptType = document.getElementById('receipt_type');
  if (receiptType) {
    receiptType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('treatmentReceiptModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう施術料金領収書 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ施術料金領収書 出力設定';
    }
  }

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('receipt_submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('receipt_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 施術料金領収書PDF出力
 */
function submitTreatmentReceipt() {
  const form = document.getElementById('treatmentReceiptForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const receiptType = document.getElementById('receipt_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = receiptType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}施術料金領収書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/treatment-receipt/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('treatmentReceiptModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 施術料金一覧表（保険扱い）モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openTreatmentFeeListModal(type) {
  const modalElement = document.getElementById('treatmentFeeListModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('treatmentFeeListForm');

  // タイプを設定
  const feeListType = document.getElementById('fee_list_type');
  if (feeListType) {
    feeListType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('treatmentFeeListModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう 施術料金一覧表（保険扱い） 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ 施術料金一覧表（保険扱い） 出力設定';
    }
  }

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 施術料金一覧表（保険扱い）PDF出力
 */
function submitTreatmentFeeList() {
  const form = document.getElementById('treatmentFeeListForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const feeListType = document.getElementById('fee_list_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = feeListType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}施術料金一覧表（保険扱い）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/treatment-fee-list/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('treatmentFeeListModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 施術料金一覧表（自費）モーダルを開く
 */
function openSelfFeeListModal() {
  const modalElement = document.getElementById('selfFeeListModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('selfFeeListForm');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 施術料金一覧表（自費）PDF出力
 */
function submitSelfFeeList() {
  const form = document.getElementById('selfFeeListForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `施術料金一覧表（自費）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/self-fee-list/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('selfFeeListModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 医療助成費支給申請書モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openMedicalAssistanceModal(type) {
  const modalElement = document.getElementById('medicalAssistanceModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('medicalAssistanceForm');

  // タイプを設定
  const assistanceType = document.getElementById('medical_assistance_type');
  if (assistanceType) {
    assistanceType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('medicalAssistanceModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう医療助成費支給申請書 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ医療助成費支給申請書 出力設定';
    }
  }

  // 提出年月を今月に設定
  const submissionMonth = document.getElementById('medical_assistance_submission_month');
  if (submissionMonth) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    submissionMonth.value = `${year}-${month}`;
  }

  // チェックボックスの排他制御を設定
  setupSignatureOptionCheckboxes();

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('medical_assistance_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 署名オプションのチェックボックスを1つだけ選択可能にする排他制御
 */
function setupSignatureOptionCheckboxes() {
  const checkboxes = document.querySelectorAll('.signature-option-checkbox');

  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      if (this.checked) {
        // 他のチェックボックスを全て外す
        checkboxes.forEach(cb => {
          if (cb !== this) {
            cb.checked = false;
          }
        });
      }
    });
  });
}

/**
 * 医療助成費支給申請書PDF出力
 */
function submitMedicalAssistance() {
  const form = document.getElementById('medicalAssistanceForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const assistanceType = document.getElementById('medical_assistance_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = assistanceType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}医療助成費支給申請書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/medical-assistance/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('medicalAssistanceModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 後期高齢者医療療養費支給申請書モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openLateElderlyMedicalModal(type) {
  const modalElement = document.getElementById('lateElderlyMedicalModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('lateElderlyMedicalForm');

  // タイプを設定
  const lateElderlyMedicalType = document.getElementById('late_elderly_medical_type');
  if (lateElderlyMedicalType) {
    lateElderlyMedicalType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('lateElderlyMedicalModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう後期高齢者医療療養費支給申請書 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ後期高齢者医療療養費支給申請書 出力設定';
    }
  }

  // 提出年月を今月に設定
  const submissionMonth = document.getElementById('late_elderly_medical_submission_month');
  if (submissionMonth) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    submissionMonth.value = `${year}-${month}`;
  }

  // チェックボックスの排他制御を設定
  setupLateElderlySignatureOptionCheckboxes();

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('late_elderly_medical_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 後期高齢者署名オプションのチェックボックスを1つだけ選択可能にする排他制御
 */
function setupLateElderlySignatureOptionCheckboxes() {
  const checkboxes = document.querySelectorAll('.late-elderly-signature-option-checkbox');

  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      if (this.checked) {
        // 他のチェックボックスを全て外す
        checkboxes.forEach(cb => {
          if (cb !== this) {
            cb.checked = false;
          }
        });
      }
    });
  });
}

/**
 * 後期高齢者医療療養費支給申請書PDF出力
 */
function submitLateElderlyMedical() {
  const form = document.getElementById('lateElderlyMedicalForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const lateElderlyMedicalType = document.getElementById('late_elderly_medical_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = lateElderlyMedicalType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}後期高齢者医療療養費支給申請書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/late-elderly-medical/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('lateElderlyMedicalModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 同意書依頼状（サンプル版）モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openConsentRequestSampleModal(type) {
  const modalElement = document.getElementById('consentRequestSampleModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('consentRequestSampleForm');

  // タイプを設定
  const consentRequestType = document.getElementById('consent_request_sample_type');
  if (consentRequestType) {
    consentRequestType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('consentRequestSampleModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう同意書依頼状（サンプル版） 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ同意書依頼状（サンプル版） 出力設定';
    }
  }

  // 提出年月を今月に設定
  const submissionMonth = document.getElementById('consent_request_sample_submission_month');
  if (submissionMonth) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    submissionMonth.value = `${year}-${month}`;
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('consent_request_sample_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 同意書依頼状（サンプル版）PDF出力
 */
function submitConsentRequestSample() {
  const form = document.getElementById('consentRequestSampleForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const consentRequestType = document.getElementById('consent_request_sample_type').value;

  // localStorageからカスタムタイトルを読み込み
  const pdfType = consentRequestType === 'acupuncture'
    ? 'consent_request_letter_sample_acupuncture'
    : 'consent_request_letter_sample_massage';
  const titleStorageKey = 'customTitleText_' + pdfType;
  const customTitleText = localStorage.getItem(titleStorageKey);

  // カスタムタイトルをフォームに追加
  if (customTitleText) {
    let titleInput = document.getElementById('consent_request_sample_custom_title');
    if (!titleInput) {
      titleInput = document.createElement('input');
      titleInput.type = 'hidden';
      titleInput.id = 'consent_request_sample_custom_title';
      titleInput.name = 'custom_title_text';
      form.appendChild(titleInput);
    }
    titleInput.value = customTitleText;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = consentRequestType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}同意書依頼状（サンプル版）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/consent-request-sample/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('consentRequestSampleModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 同意書依頼状（医師指定）モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openConsentRequestDesignatedModal(type) {
  const modalElement = document.getElementById('consentRequestDesignatedModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('consentRequestDesignatedForm');

  // タイプを設定
  const consentRequestType = document.getElementById('consent_request_designated_type');
  if (consentRequestType) {
    consentRequestType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('consentRequestDesignatedModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう同意書依頼状（医師指定） 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ同意書依頼状（医師指定） 出力設定';
    }
  }

  // 提出年月を今月に設定
  const submissionMonth = document.getElementById('consent_request_designated_submission_month');
  if (submissionMonth) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    submissionMonth.value = `${year}-${month}`;
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('consent_request_designated_clinic_user_ids');
  adjustSelectSize('consent_request_designated_doctor_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 同意書依頼状（医師指定）PDF出力
 */
function submitConsentRequestDesignated() {
  const form = document.getElementById('consentRequestDesignatedForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const consentRequestType = document.getElementById('consent_request_designated_type').value;

  // localStorageからカスタムタイトルを読み込み
  const pdfType = consentRequestType === 'acupuncture'
    ? 'consent_request_letter_designated_acupuncture'
    : 'consent_request_letter_designated_massage';
  const titleStorageKey = 'customTitleText_' + pdfType;
  const customTitleText = localStorage.getItem(titleStorageKey);

  // カスタムタイトルをフォームに追加
  if (customTitleText) {
    let titleInput = document.getElementById('consent_request_designated_custom_title');
    if (!titleInput) {
      titleInput = document.createElement('input');
      titleInput.type = 'hidden';
      titleInput.id = 'consent_request_designated_custom_title';
      titleInput.name = 'custom_title_text';
      form.appendChild(titleInput);
    }
    titleInput.value = customTitleText;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = consentRequestType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}同意書依頼状（医師指定）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/consent-request-designated/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('consentRequestDesignatedModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 同意書モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openConsentFormModal(type) {
  const modalElement = document.getElementById('consentFormModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('consentFormForm');

  // タイプを設定
  const consentFormType = document.getElementById('consent_form_type');
  if (consentFormType) {
    consentFormType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('consentFormModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう同意書 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ同意書 出力設定';
    }
  }

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('consent_form_submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('consent_form_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 同意書PDF出力
 */
function submitConsentForm() {
  const form = document.getElementById('consentFormForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const consentFormType = document.getElementById('consent_form_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = consentFormType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}同意書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/consent-form/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('consentFormModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 施術録モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openTreatmentRecordModal(type) {
  const modalElement = document.getElementById('treatmentRecordModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('treatmentRecordForm');

  // タイプを設定
  const recordType = document.getElementById('treatment_record_type');
  if (recordType) {
    recordType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('treatmentRecordModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう施術録 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ施術録 出力設定';
    }
  }

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('treatment_record_submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('treatment_record_clinic_user_ids');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 総括表モーダルを開く
 * @param {string} type - 'acupuncture'（はり・きゅう）または 'massage'（あんま・マッサージ）
 */
function openSummaryTableModal(type) {
  const modalElement = document.getElementById('summaryTableModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('summaryTableForm');

  // タイプを設定
  const summaryType = document.getElementById('summary_table_type');
  if (summaryType) {
    summaryType.value = type;
  }

  // モーダルタイトルを更新
  const modalTitle = document.getElementById('summaryTableModalLabel');
  if (modalTitle) {
    if (type === 'acupuncture') {
      modalTitle.textContent = 'はり・きゅう総括表 出力設定';
    } else {
      modalTitle.textContent = 'あんま・マッサージ総括表 出力設定';
    }
  }

  // 提出年月日を今日に設定
  const submissionDate = document.getElementById('summary_table_submission_date');
  if (submissionDate) {
    submissionDate.value = new Date().toISOString().split('T')[0];
  }

  // サービス提供年月のオプションラベルを更新（該当データなし・件数表示）
  const ymSelect = document.getElementById('summary_table_service_year_month');
  if (ymSelect) {
    const dataAttr = type === 'acupuncture' ? 'acupunctureMonths' : 'massageMonths';
    const dataMonthsMap = JSON.parse(ymSelect.dataset[dataAttr] || '{}');
    Array.from(ymSelect.options).forEach(option => {
      if (!option.value) return;
      const baseLabel = option.value.replace(/^(\d{4})-(\d{2})$/, (_, y, m) => { const n = parseInt(m); return `${y}年${n < 10 ? '\u00a0\u00a0' : ''}${n}月`; });
      if (option.value in dataMonthsMap) {
        option.textContent = baseLabel + ` ｜ 該当データ：${dataMonthsMap[option.value]}件`;
        option.disabled = false;
      } else {
        option.textContent = baseLabel + ' ｜ 該当データ：なし';
        option.disabled = true;
      }
    });
  }

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 総括表PDF出力
 */
function submitSummaryTable() {
  const form = document.getElementById('summaryTableForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const summaryType = document.getElementById('summary_table_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = summaryType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}総括表_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/summary-table/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('summaryTableModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 入金管理表（保険）モーダルを開く
 */
function openInsurancePaymentModal() {
  const modalElement = document.getElementById('insurancePaymentModal');
  if (!modalElement) return;

  // フォームをリセット
  resetFormToDefault('insurancePaymentForm');

  // モーダルをbodyに追加（必要な場合）
  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  // Bootstrapモーダルインスタンスを取得または作成
  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 入金管理表（保険）PDF出力
 */
function submitInsurancePayment() {
  const form = document.getElementById('insurancePaymentForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');
  const filename = `入金管理表（保険）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/insurance-payment/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('insurancePaymentModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 施術録PDF出力
 */
function submitTreatmentRecord() {
  const form = document.getElementById('treatmentRecordForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const recordType = document.getElementById('treatment_record_type').value;

  // 現在日時からファイル名を生成
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const typeName = recordType === 'acupuncture' ? 'はり・きゅう' : 'あんま・マッサージ';
  const filename = `${typeName}施術録_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  // フォームのアクションURLにファイル名を含める
  form.action = `/prints/treatment-record/${encodeURIComponent(filename)}`;

  // フォームを新しいタブで送信
  form.target = '_blank';
  form.submit();

  // モーダルを閉じる
  setTimeout(() => {
    const modalElement = document.getElementById('treatmentRecordModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 紹介者への御礼状モーダルを開く
 */
function openReferrerThankYouModal() {
  const modalElement = document.getElementById('referrerThankYouModal');
  if (!modalElement) return;

  resetFormToDefault('referrerThankYouForm');

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('referrer_thank_you_clinic_user_ids');
  adjustSelectSize('referrer_thank_you_caremanager_id');

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 紹介者への御礼状PDF出力
 */
function submitReferrerThankYou() {
  const form = document.getElementById('referrerThankYouForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `紹介者への御礼状_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  form.action = `/prints/referrer-thank-you/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('referrerThankYouModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 医師への御礼状モーダルを開く
 */
function openDoctorThankYouModal() {
  const modalElement = document.getElementById('doctorThankYouModal');
  if (!modalElement) return;

  resetFormToDefault('doctorThankYouForm');

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('doctor_thank_you_clinic_user_ids');
  adjustSelectSize('doctor_thank_you_doctor_id');

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 利用者数集計表モーダルを開く
 */
function openUserCountSummaryModal() {
  const modalElement = document.getElementById('userCountSummaryModal');
  if (!modalElement) return;

  resetFormToDefault('userCountSummaryForm');

  const ymSelect = document.getElementById('user_count_summary_service_year_month');
  if (ymSelect) {
    const dataMonthsMap = JSON.parse(ymSelect.dataset.months || '{}');
    Array.from(ymSelect.options).forEach(option => {
      if (!option.value) return;
      const baseLabel = option.value.replace(/^(\d{4})-(\d{2})$/, (_, y, m) => { const n = parseInt(m); return `${y}年${n < 10 ? '\u00a0\u00a0' : ''}${n}月`; });
      if (option.value in dataMonthsMap) {
        option.textContent = baseLabel + ` ｜ 該当データ：${dataMonthsMap[option.value]}件`;
        option.disabled = false;
      } else {
        option.textContent = baseLabel + ' ｜ 該当データ：なし';
        option.disabled = true;
      }
    });
  }

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 利用者数集計表PDF出力
 */
function submitUserCountSummary() {
  const form = document.getElementById('userCountSummaryForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `利用者数集計表_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  form.action = `/prints/user-count-summary/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('userCountSummaryModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 医師への御礼状PDF出力
 */
function submitDoctorThankYou() {
  const form = document.getElementById('doctorThankYouForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `医師への御礼状_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;

  form.action = `/prints/doctor-thank-you/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('doctorThankYouModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 実施計画書モーダルを開く
 */
function openImplementationPlanModal() {
  const modalElement = document.getElementById('implementationPlanModal');
  if (!modalElement) return;

  resetFormToDefault('implementationPlanForm');

  // 利用者のあり/なしラベルを更新
  updateImplementationPlanUserLabels();

  // 選択ボックスの高さをオプション数に合わせて調整
  adjustSelectSize('implementation_plan_clinic_user_ids');

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 実施計画書モーダルの利用者オプションに「該当データ：あり/なし」を表示
 */
function updateImplementationPlanUserLabels() {
  const ymSelect = document.getElementById('implementation_plan_service_year_month');
  const userSelect = document.getElementById('implementation_plan_clinic_user_ids');
  if (!ymSelect || !userSelect) return;

  const selectedYm = ymSelect.value;

  Array.from(userSelect.options).forEach(option => {
    const planMonths = JSON.parse(option.dataset.planMonths || '[]');
    // ベース名を初回取得・キャッシュ
    if (!option.dataset.baseName) {
      option.dataset.baseName = option.textContent.trim();
    }
    const baseName = option.dataset.baseName;

    if (!selectedYm) {
      option.textContent = baseName;
      option.disabled = false;
      return;
    }

    if (planMonths.includes(selectedYm)) {
      option.textContent = baseName + ' ｜ 該当データ：あり';
      option.disabled = false;
    } else {
      option.textContent = baseName + ' ｜ 該当データ：なし';
      option.disabled = true;
      option.selected = false;
    }
  });
}

/**
 * 報告書挨拶文モーダルを開く
 */
function openReportGreetingModal() {
  const modalElement = document.getElementById('reportGreetingModal');
  if (!modalElement) return;

  resetFormToDefault('reportGreetingForm');

  // 提出年月日を今日の日付でデフォルト設定
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  const submissionDateEl = document.getElementById('report_greeting_submission_date');
  if (submissionDateEl && !submissionDateEl.value) {
    submissionDateEl.value = `${yyyy}-${mm}-${dd}`;
  }

  // オプションに応じたフィールド表示制御
  updateReportGreetingFields();

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 報告書挨拶状モーダルのオプション切り替え時に医師・ケアマネフィールドを切り替え
 */
function updateReportGreetingFields() {
  const greetingType = document.querySelector('input[name="greeting_type"]:checked')?.value || 'doctor';

  const doctorSection      = document.getElementById('report_greeting_doctor_section');
  const caremanagerSection = document.getElementById('report_greeting_caremanager_section');
  const doctorSelect       = document.getElementById('report_greeting_doctor_id');
  const caremanagerSelect  = document.getElementById('report_greeting_caremanager_id');

  if (!doctorSection || !caremanagerSection) return;

  if (greetingType === 'doctor') {
    doctorSection.classList.remove('d-none');
    caremanagerSection.classList.add('d-none');
    doctorSelect.required     = true;
    caremanagerSelect.required = false;
    caremanagerSelect.value   = '';
  } else if (greetingType === 'caremanager') {
    doctorSection.classList.add('d-none');
    caremanagerSection.classList.remove('d-none');
    doctorSelect.required     = false;
    caremanagerSelect.required = true;
    doctorSelect.value        = '';
  } else {
    // user
    doctorSection.classList.add('d-none');
    caremanagerSection.classList.add('d-none');
    doctorSelect.required     = false;
    caremanagerSelect.required = false;
    doctorSelect.value        = '';
    caremanagerSelect.value   = '';
  }
}

/**
 * 報告書挨拶文PDF出力
 */
function submitReportGreeting() {
  const form = document.getElementById('reportGreetingForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `報告書挨拶文_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  form.action = `/prints/report-greeting/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('reportGreetingModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 予定表モーダルを開く
 */
function openNextMonthScheduleModal() {
  const modalElement = document.getElementById('nextMonthScheduleModal');
  if (!modalElement) return;

  resetFormToDefault('nextMonthScheduleForm');
  adjustSelectSize('next_month_schedule_clinic_user_ids');

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 予定表PDF出力
 */
function submitNextMonthSchedule() {
  const form = document.getElementById('nextMonthScheduleForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `予定表_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  form.action = `/prints/next-month-schedule/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('nextMonthScheduleModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 実施計画書PDF出力
 */
function submitImplementationPlan() {
  const form = document.getElementById('implementationPlanForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `実施計画書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  form.action = `/prints/implementation-plan/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('implementationPlanModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 報告書モーダルを開く
 */
function openReportModal() {
  const modalElement = document.getElementById('reportModal');
  if (!modalElement) return;

  resetFormToDefault('reportForm');

  // 提出年月日を今日の日付でデフォルト設定
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  const todayStr = `${yyyy}-${mm}-${dd}`;

  const submissionDateEl = document.getElementById('report_submission_date');
  if (submissionDateEl && !submissionDateEl.value) {
    submissionDateEl.value = todayStr;
  }

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
  modal.show();
}

/**
 * 報告書PDF出力
 */
function submitReport() {
  const form = document.getElementById('reportForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `報告書_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  form.action = `/prints/report/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('reportModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 要加療期限切れリストモーダルを開く
 */
function openTreatmentExpiryListModal() {
  const modalElement = document.getElementById('treatmentExpiryListModal');
  if (!modalElement) return;

  resetFormToDefault('treatmentExpiryListForm');

  const today = new Date();
  const y = today.getFullYear();
  const m = String(today.getMonth() + 1).padStart(2, '0');
  const d = String(today.getDate()).padStart(2, '0');
  const outputDateEl = document.getElementById('treatment_expiry_list_output_date');
  if (outputDateEl) outputDateEl.value = `${y}-${m}-${d}`;

  if (modalElement.parentElement !== document.body) {
    document.body.appendChild(modalElement);
  }

  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

/**
 * 要加療期限切れリストPDF出力
 */
function submitTreatmentExpiryList() {
  const form = document.getElementById('treatmentExpiryListForm');

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `要加療期限切れリスト_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  form.action = `/prints/treatment-expiry-list/${encodeURIComponent(filename)}`;
  form.target = '_blank';
  form.submit();

  setTimeout(() => {
    const modalElement = document.getElementById('treatmentExpiryListModal');
    if (modalElement) {
      const modalInstance = bootstrap.Modal.getInstance(modalElement);
      if (modalInstance) {
        modalInstance.hide();
      }
    }
  }, 100);
}

/**
 * 利用者情報一覧（同意医師情報）PDF出力
 */
function submitClinicUserConsentInfoList() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `利用者情報一覧（同意医師情報）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  window.open(`/prints/clinic-user-consent-info-list/${encodeURIComponent(filename)}`, '_blank');
}

/**
 * 利用者情報一覧（基本情報）PDF出力
 */
function submitUserInfoBasicList() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `利用者情報一覧（基本情報）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  window.open(`/prints/user-info-basic-list/${encodeURIComponent(filename)}`, '_blank');
}

/**
 * 利用者情報一覧（医療保険情報）PDF出力
 */
function submitUserInfoInsuranceList() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const seconds = String(now.getSeconds()).padStart(2, '0');

  const filename = `利用者情報一覧（医療保険情報）_${year}-${month}-${day}_${hours}-${minutes}-${seconds}.pdf`;
  window.open(`/prints/user-info-insurance-list/${encodeURIComponent(filename)}`, '_blank');
}
