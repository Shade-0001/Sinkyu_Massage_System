// public/js/prints.js

/**
 * 複数選択リストでクリックによるトグル選択を有効化
 * @param {string} selectId - select要素のID
 */
function enableClickToggleSelect(selectId) {
  const select = document.getElementById(selectId);
  if (!select) return;

  select.addEventListener('mousedown', function(e) {
    if (e.target.tagName === 'OPTION') {
      e.preventDefault();
      const option = e.target;
      option.selected = !option.selected;

      // changeイベントを発火させる
      select.dispatchEvent(new Event('change', { bubbles: true }));

      // フォーカスを維持
      select.focus();
    }
  });
}

// ページ読み込み時に利用者選択リストのトグル機能を有効化
document.addEventListener('DOMContentLoaded', function() {
  enableClickToggleSelect('clinic_user_ids');
  enableClickToggleSelect('massage_clinic_user_ids');
});

/**
 * はり・きゅう療養費支給申請書モーダルを開く
 */
function openAcupunctureBenefitModal() {
  const modalElement = document.getElementById('acupunctureBenefitModal');
  if (!modalElement) return;

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
