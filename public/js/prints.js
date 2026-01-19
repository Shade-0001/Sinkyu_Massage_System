// public/js/prints.js

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
