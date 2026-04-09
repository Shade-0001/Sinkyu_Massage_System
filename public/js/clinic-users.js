//-- public/js/clinic-users.js --//


// 郵便番号から住所を検索する関数
async function searchAddress() {
  await searchAndFillAddress('postal_code', {
    combined: false,
    address1: 'address_1',
    address2: 'address_2'
  });
}

// ページ読み込み時に実行
document.addEventListener('DOMContentLoaded', function() {
  // 郵便番号入力時の処理
  setupPostalCodeInput('postal_code', searchAddress);

  // 生年月日入力時に年齢を自動計算
  const birthdayInput = document.getElementById('birthday');
  if (birthdayInput) {
    birthdayInput.addEventListener('change', () => calculateAndFillAge('birthday', 'age'));

    // 既に生年月日が入力されていれば年齢を計算
    if (birthdayInput.value) {
      calculateAndFillAge('birthday', 'age');
    }
  }

  // 往診距離入力時に往診加算距離を自動計算
  const housecallDistanceInput = document.getElementById('housecall_distance');
  const housecallAdditionalDistanceInput = document.getElementById('housecall_additional_distance');
  if (housecallDistanceInput && housecallAdditionalDistanceInput) {
    const calcAdditional = () => {
      const dist = parseFloat(housecallDistanceInput.value);
      housecallAdditionalDistanceInput.value = isNaN(dist) ? '' : Math.max(0, dist - 4);
    };
    housecallDistanceInput.addEventListener('input', calcAdditional);

    // 既に往診距離が入力されていれば計算
    if (housecallDistanceInput.value !== '') {
      calcAdditional();
    }
  }

});
