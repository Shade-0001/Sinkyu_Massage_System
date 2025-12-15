// public/js/prints.js

document.addEventListener('DOMContentLoaded', function() {
  console.log('prints.js loaded');

  // モーダル要素の取得
  const acupunctureBenefitModal = document.getElementById('acupunctureBenefitModal');
  console.log('Modal element:', acupunctureBenefitModal);

  if (acupunctureBenefitModal) {
    // Bootstrapモーダルインスタンス作成
    window.acupunctureBenefitModalInstance = new bootstrap.Modal(acupunctureBenefitModal);
    console.log('Modal instance created:', window.acupunctureBenefitModalInstance);
  } else {
    console.error('Modal element not found!');
  }

  // クリックイベントテスト
  document.addEventListener('click', function(e) {
    console.log('Click detected:', e.target);
  });
});

/**
 * はり・きゅう療養費支給申請書モーダルを開く
 */
function openAcupunctureBenefitModal() {
  console.log('openAcupunctureBenefitModal called');
  console.log('Modal instance:', window.acupunctureBenefitModalInstance);

  if (window.acupunctureBenefitModalInstance) {
    window.acupunctureBenefitModalInstance.show();
    console.log('Modal show() called');

    // backdrop の z-index を強制的に修正
    setTimeout(() => {
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.style.zIndex = '1040';
        backdrop.style.pointerEvents = 'none'; // クリックを透過
        console.log('Backdrop z-index fixed to 1040 and pointer-events disabled');
      }
      const modal = document.getElementById('acupunctureBenefitModal');
      if (modal) {
        modal.style.zIndex = '1050';
        modal.style.pointerEvents = 'auto'; // モーダルはクリック可能
        console.log('Modal z-index set to 1050 and pointer-events enabled');
      }
    }, 100); // タイミングを100msに延長
  } else {
    console.error('Modal instance not available!');
  }
}

/**
 * はり・きゅう療養費支給申請書PDF出力
 */
function submitAcupunctureBenefit() {
  console.log('submitAcupunctureBenefit called');

  const form = document.getElementById('acupunctureBenefitForm');
  console.log('Form element:', form);

  if (!form.checkValidity()) {
    console.log('Form validation failed');
    form.reportValidity();
    return;
  }

  console.log('Form is valid, submitting...');

  const formData = new FormData(form);
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  fetch(form.dataset.action || '/prints/acupuncture-benefit', {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
    }
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('PDF生成に失敗しました');
    }
    return response.blob();
  })
  .then(blob => {
    // PDFダウンロード
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'acupuncture_benefit_' + document.getElementById('service_year_month').value + '.pdf';
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);

    // モーダルを閉じる
    if (window.acupunctureBenefitModalInstance) {
      window.acupunctureBenefitModalInstance.hide();
    }

    alert('PDF出力が完了しました');
  })
  .catch(error => {
    console.error('Error:', error);
    alert('エラーが発生しました: ' + error.message);
  });
}
