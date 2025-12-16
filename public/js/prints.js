// public/js/prints.js

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
