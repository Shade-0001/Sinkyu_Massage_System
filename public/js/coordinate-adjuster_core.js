// Bladeファイルから渡された変数を取得
const { currentPdfType, masterData, treatmentFees, csrfToken } = window.coordinateAdjusterData;

// 座標データ管理用変数
let coordinates = {};
let originalCoordinates = {};

// 初期化
document.addEventListener('DOMContentLoaded', function() {
  loadCoordinates();
  loadCustomSampleData();
  displayTreatmentFees();

  // イベントリスナー
  document.getElementById('btn-reset').addEventListener('click', resetCoordinates);
  document.getElementById('clinic-user-select').addEventListener('change', function() {
    previewPdf();
  });
  document.getElementById('pdf-type-select').addEventListener('change', function() {
    const newPdfType = this.value;
    // ページをリロードして新しいPDFタイプを適用
    window.location.href = '/prints/coordinate-adjuster?pdf_type=' + newPdfType;
  });
  document.getElementById('show-sample-data').addEventListener('change', function() {
    const isChecked = this.checked;
    // チェックを外してもラジオグループはリセットせず、プレビュー利用者の実データから判定する
    renderFieldSettings(); // サンプルデータ入力欄の表示/非表示を更新
    previewPdf();
  });
});

// ラジオグループをデフォルト状態にリセット
function resetRadioGroupsToDefault() {
  // 各radioGroupの最初のフィールドをisSelected: trueに設定
  const processedGroups = new Set();

  Object.keys(coordinates).forEach(key => {
    const field = coordinates[key];
    if (field.radioGroup && !processedGroups.has(field.radioGroup)) {
      processedGroups.add(field.radioGroup);

      // グループ内のフィールドをリセット
      Object.keys(coordinates).forEach(k => {
        const f = coordinates[k];
        if (f.radioGroup === field.radioGroup) {
          f.isSelected = false;
        }
      });

      // 最初のフィールドをisSelected: trueに
      coordinates[key].isSelected = true;
    }
  });
}

// 座標読み込み
function loadCoordinates() {
  fetch('/prints/get-coordinates?pdf_type=' + currentPdfType)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        coordinates = data.coordinates;

        // フィールド定義のデフォルト値をマージ
        Object.keys(sampleDataFieldMapping).forEach(key => {
          if (coordinates[key]) {
            const definition = sampleDataFieldMapping[key];
            // ellipseWidth, ellipseHeight, circleRadius, lineWidth などのデフォルト値を設定
            if (definition.ellipseWidth !== undefined && coordinates[key].ellipseWidth === undefined) {
              coordinates[key].ellipseWidth = definition.ellipseWidth;
            }
            if (definition.ellipseHeight !== undefined && coordinates[key].ellipseHeight === undefined) {
              coordinates[key].ellipseHeight = definition.ellipseHeight;
            }
            if (definition.circleRadius !== undefined && coordinates[key].circleRadius === undefined) {
              coordinates[key].circleRadius = definition.circleRadius;
            }
            if (definition.lineWidth !== undefined && coordinates[key].lineWidth === undefined) {
              coordinates[key].lineWidth = definition.lineWidth;
            }
            if (definition.width !== undefined && coordinates[key].width === undefined) {
              coordinates[key].width = definition.width;
            }
            if (definition.lineHeight !== undefined && coordinates[key].lineHeight === undefined) {
              coordinates[key].lineHeight = definition.lineHeight;
            }
            // radioGroup, optionLabel, label, type などのメタデータをマージ
            if (definition.radioGroup !== undefined) {
              coordinates[key].radioGroup = definition.radioGroup;
            }
            if (definition.optionLabel !== undefined) {
              coordinates[key].optionLabel = definition.optionLabel;
            }
            if (definition.label !== undefined) {
              coordinates[key].label = definition.label;
            }
            if (definition.type !== undefined) {
              coordinates[key].type = definition.type;
            }
            if (definition.postalCodeGap !== undefined && coordinates[key].postalCodeGap === undefined) {
              coordinates[key].postalCodeGap = definition.postalCodeGap;
            }
            // compositeGroup, compositeLabelをマージ
            if (definition.compositeGroup !== undefined) {
              coordinates[key].compositeGroup = definition.compositeGroup;
            }
            if (definition.compositeLabel !== undefined) {
              coordinates[key].compositeLabel = definition.compositeLabel;
            }
          }
        });

        originalCoordinates = JSON.parse(JSON.stringify(coordinates));
        resetRadioGroupsToDefault();
        renderFieldSettings();
        previewPdf();
      } else {
        alert('座標データの読み込みに失敗しました');
      }
    })
    .catch(error => {
      console.error('座標読み込みエラー:', error);
      alert('座標データの読み込みに失敗しました');
    });
}

// 座標更新
function updateCoordinate(key, property, value) {
  // テキスト配置の場合は文字列、その他は数値に変換
  if (property === 'textAlign') {
    coordinates[key][property] = value;
  } else {
    coordinates[key][property] = parseFloat(value);
  }

  // postal_codeタイプの場合、x/y/postalCodeGap変更時にfirstX/firstY/lastXも同期
  const mapping = sampleDataFieldMapping[key];
  if (mapping && mapping.type === 'postal_code') {
    if (property === 'x') {
      coordinates[key]['firstX'] = parseFloat(value);
      // lastXも再計算
      const gap = coordinates[key]['postalCodeGap'] || 2;
      coordinates[key]['lastX'] = parseFloat(value) + gap;
    } else if (property === 'y') {
      coordinates[key]['firstY'] = parseFloat(value);
      coordinates[key]['lastY'] = parseFloat(value);
    } else if (property === 'postalCodeGap') {
      // postalCodeGap変更時はlastXを再計算
      const firstX = coordinates[key]['firstX'] || coordinates[key]['x'] || 0;
      coordinates[key]['lastX'] = firstX + parseFloat(value);
    }
  }

  // テキスト配置更新の場合はボタンのアクティブ状態を更新
  if (property === 'textAlign') {
    const radioGroupName = coordinates[key].radioGroup;
    const compositeGroupName = coordinates[key].compositeGroup;

    let controls = document.getElementById('controls-' + key);
    if (!controls && radioGroupName) {
      controls = document.getElementById('radiogroup-fields-' + radioGroupName);
    }
    if (!controls && compositeGroupName) {
      controls = document.getElementById('compositegroup-fields-' + compositeGroupName);
    }

    if (controls) {
      const buttons = controls.querySelectorAll('.btn-group .btn');
      buttons.forEach(btn => btn.classList.remove('active'));

      const activeIndex = ['left', 'center', 'right'].indexOf(value);
      if (activeIndex >= 0 && buttons[activeIndex]) {
        buttons[activeIndex].classList.add('active');
      }
    }
  }

  autoPreview();
  autoSave();
}

// 微調整ボタン
function adjustValue(key, property, delta) {
  const currentValue = coordinates[key][property] || 0;

  // delta の精度に応じて丸め桁を決定
  let roundDigits = 1; // デフォルトは小数第1位
  if (Math.abs(delta) === 0.05) {
    roundDigits = 2; // ±0.05 の場合は小数第2位
  } else if (Math.abs(delta) === 0.1) {
    roundDigits = 1; // ±0.1 の場合は小数第1位
  }

  const multiplier = Math.pow(10, roundDigits);
  const newValue = Math.round((currentValue + delta) * multiplier) / multiplier;
  coordinates[key][property] = newValue;

  // postal_codeタイプの場合、x/y/postalCodeGap変更時にfirstX/firstY/lastXも同期
  const mapping = sampleDataFieldMapping[key];
  if (mapping && mapping.type === 'postal_code') {
    if (property === 'x') {
      coordinates[key]['firstX'] = newValue;
      // lastXも再計算
      const gap = coordinates[key]['postalCodeGap'] || 2;
      coordinates[key]['lastX'] = newValue + gap;
    } else if (property === 'y') {
      coordinates[key]['firstY'] = newValue;
      coordinates[key]['lastY'] = newValue;
    } else if (property === 'postalCodeGap') {
      // postalCodeGap変更時はlastXを再計算
      const firstX = coordinates[key]['firstX'] || coordinates[key]['x'] || 0;
      coordinates[key]['lastX'] = firstX + newValue;
    }
  }

  // 該当のinput要素を data-property 属性で探して更新
  // ラジオグループとcompositeGroupの場合とそうでない場合の両方に対応
  const controlsId = 'controls-' + key;
  const radioGroupName = coordinates[key].radioGroup;
  const compositeGroupName = coordinates[key].compositeGroup;

  let controls = document.getElementById(controlsId);
  if (!controls && radioGroupName) {
    // ラジオグループの場合
    controls = document.getElementById('radiogroup-fields-' + radioGroupName);
  }
  if (!controls && compositeGroupName) {
    // compositeGroupの場合
    controls = document.getElementById('compositegroup-fields-' + compositeGroupName);
  }

  if (controls) {
    const input = controls.querySelector(`input[data-property="${property}"]`);
    if (input) {
      input.value = newValue;
    }
  }

  autoPreview();
  autoSave();
}

// 自動プレビュー（デバウンス付き）
let previewTimeout = null;
function autoPreview() {
  clearTimeout(previewTimeout);
  previewTimeout = setTimeout(() => {
    previewPdf();
  }, 500); // 500ms後にプレビュー更新
}

// 自動保存（デバウンス付き）
let saveTimeout = null;
function autoSave() {
  clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    saveCoordinates(true); // 自動保存フラグ
  }, 1000); // 1秒後に自動保存
}

// 長押し処理
let longPressInterval = null;
let longPressTimeout = null;

function startLongPress(key, property, delta) {
  // 即座に1回実行
  adjustValue(key, property, delta);

  // 100ms後から連続実行開始
  longPressTimeout = setTimeout(() => {
    longPressInterval = setInterval(() => {
      adjustValue(key, property, delta);
    }, 1); // 5msごとに実行
  }, 100);
}

function stopLongPress() {
  if (longPressTimeout) {
    clearTimeout(longPressTimeout);
    longPressTimeout = null;
  }
  if (longPressInterval) {
    clearInterval(longPressInterval);
    longPressInterval = null;
  }
}

// 保存
function saveCoordinates(isAuto = false) {
  const saveIndicator = document.getElementById('save-indicator');
  const saveStatus = document.getElementById('save-status');

  // 保存中インジケーター表示
  if (isAuto) {
    saveIndicator.style.display = 'inline-block';
  }

  // isSelectedフラグを除外した座標データを作成
  const coordinatesToSave = {};
  Object.keys(coordinates).forEach(key => {
    coordinatesToSave[key] = {};
    Object.keys(coordinates[key]).forEach(prop => {
      if (prop !== 'isSelected') {
        coordinatesToSave[key][prop] = coordinates[key][prop];
      }
    });
  });

  fetch('/prints/save-coordinates', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      coordinates: coordinatesToSave,
      pdf_type: currentPdfType
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        originalCoordinates = JSON.parse(JSON.stringify(coordinates));

        if (isAuto) {
          // 自動保存時は控えめな通知
          saveIndicator.style.display = 'none';
          saveStatus.style.display = 'block';
          setTimeout(() => {
            saveStatus.style.display = 'none';
          }, 2000);
        }
      } else {
        if (isAuto) {
          saveIndicator.style.display = 'none';
        }
        console.error('保存失敗:', data.message);
      }
    })
    .catch(error => {
      if (isAuto) {
        saveIndicator.style.display = 'none';
      }
      console.error('保存エラー:', error);
    });
}

// プレビュー
function previewPdf() {
  const iframe = document.getElementById('pdf-iframe');
  const loadingBadge = document.getElementById('preview-loading');
  const overlay = document.getElementById('preview-overlay');
  const clinicUserSelect = document.getElementById('clinic-user-select');
  const clinicUserId = clinicUserSelect ? clinicUserSelect.value : null;
  const showSampleData = document.getElementById('show-sample-data').checked;

  // ローディング表示
  loadingBadge.style.display = 'inline-block';
  overlay.style.display = 'flex';

  // プレビュー用の座標データを作成
  let coordinatesForPreview = coordinates;

  // サンプルデータ表示がOFFの場合は、isSelectedフラグを除外
  if (!showSampleData) {
    coordinatesForPreview = {};
    Object.keys(coordinates).forEach(key => {
      coordinatesForPreview[key] = {};
      Object.keys(coordinates[key]).forEach(prop => {
        if (prop !== 'isSelected') {
          coordinatesForPreview[key][prop] = coordinates[key][prop];
        }
      });
    });
  }

  const requestBody = {
    coordinates: coordinatesForPreview,
    clinic_user_id: clinicUserId,
    pdf_type: currentPdfType,
    show_sample_data: showSampleData
  };

  // カスタムサンプルデータがある場合は追加
  if (showSampleData && customSampleData) {
    requestBody.custom_sample_data = customSampleData;
  }

  fetch('/prints/preview-pdf', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(requestBody)
  })
    .then(response => response.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob);
      iframe.src = url;

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    })
    .catch(error => {
      console.error('プレビューエラー:', error);
      alert('プレビュー表示に失敗しました');

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
    });
}

// リセット
function resetCoordinates() {
  if (!confirm('変更を破棄して元に戻しますか？')) return;

  coordinates = JSON.parse(JSON.stringify(originalCoordinates));
  renderFieldSettings();
  previewPdf();
}

// カスタムサンプルデータをlocalStorageから読み込み
function loadCustomSampleData() {
  const storageKey = 'customSampleData_' + currentPdfType;
  const stored = localStorage.getItem(storageKey);
  if (stored) {
    try {
      const savedData = JSON.parse(stored);
      // デフォルト値とマージ
      customSampleData = { ...customSampleData, ...savedData };
    } catch (e) {
      console.error('サンプルデータの読み込みエラー:', e);
    }
  }

  // consent_dateのデフォルト値を設定
  if (!customSampleData.consent_date) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    customSampleData.consent_date = `${yyyy}-${mm}-${dd}`;
  }
}

// サンプルデータを更新
function updateSampleData(field, value) {
  customSampleData[field] = value;

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));

  // プレビューを自動更新
  autoPreview();
}
