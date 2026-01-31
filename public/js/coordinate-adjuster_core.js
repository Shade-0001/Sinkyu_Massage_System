// Bladeファイルから渡された変数を取得
const coordinateAdjusterData = window.coordinateAdjusterData || {};
let currentPdfType = coordinateAdjusterData.currentPdfType; // let に変更して更新可能に
const masterData = coordinateAdjusterData.masterData || {};
const treatmentFees = coordinateAdjusterData.treatmentFees;
const csrfToken = coordinateAdjusterData.csrfToken;

// 座標データ管理用変数
let coordinates = {};
let originalCoordinates = {};

// PDFタイプに応じたフィールドマッピングを取得
function getSampleDataFieldMapping() {
  if (currentPdfType === 'treatment_receipt') {
    return sampleDataFieldMappingTreatmentReceipt;
  }
  return sampleDataFieldMapping;
}

// 初期化
document.addEventListener('DOMContentLoaded', function() {
  // 施術日の初期化（非同期読み込み前の初期値）
  window.currentTreatmentDays = [];

  loadCoordinates();
  loadCustomSampleData();
  displayTreatmentFees();
  loadTreatmentDays(); // 施術日を読み込む（非同期）

  // イベントリスナー
  document.getElementById('clinic-user-select').addEventListener('change', function() {
    loadTreatmentDays(); // 利用者変更時に施術日を再読み込み
    previewPdf();
  });
  document.getElementById('year-month-select').addEventListener('change', function() {
    loadTreatmentDays(); // 年月変更時に施術日を再読み込み
    previewPdf();
  });
  document.getElementById('pdf-type-select').addEventListener('change', function() {
    const newPdfType = this.value;
    // グローバル変数を更新
    currentPdfType = newPdfType;
    window.coordinateAdjusterData.currentPdfType = newPdfType;
    // 現在選択中の利用者IDを取得
    const clinicUserSelect = document.getElementById('clinic-user-select');
    const clinicUserId = clinicUserSelect ? clinicUserSelect.value : '';
    // ページをリロードして新しいPDFタイプを適用（利用者IDも保持）
    let url = '/prints/coordinate-adjuster?pdf_type=' + newPdfType;
    if (clinicUserId) {
      url += '&clinic_user_id=' + clinicUserId;
    }
    window.location.href = url;
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
        console.log('座標読み込み完了（JSON）:', Object.keys(coordinates).length, 'フィールド');

        // フィールド定義から新規フィールドを追加（既存フィールドは保持）
        // 現在のPDFタイプに関連するフィールドのみを追加
        const fieldMapping = getSampleDataFieldMapping();
        const fieldCategories = getFieldCategories(currentPdfType);
        let newFieldCount = 0;
        Object.keys(fieldMapping).forEach(key => {
          const definition = fieldMapping[key];

          // 現在のPDFタイプに関連しないフィールドはスキップ
          if (!fieldCategories.hasOwnProperty(key)) {
            return;
          }

          // coordinatesに存在しない場合のみ新規作成
          if (!coordinates[key]) {
            newFieldCount++;
            const isEllipseField = definition.radioGroup !== undefined &&
                                    (definition.ellipseWidth !== undefined || definition.ellipseHeight !== undefined);
            coordinates[key] = {
              x: 0,
              y: 0,
              textAlign: 'left',
              ...definition
            };
            // 楕円フィールド以外にはfontSizeとletterSpacingを設定
            if (!isEllipseField) {
              coordinates[key].fontSize = 10;
              coordinates[key].letterSpacing = 0;
            }
          }
          // 既存フィールドには何もしない（JSONの値をそのまま保持）
        });

        console.log(`新規フィールド追加: ${newFieldCount}件、合計: ${Object.keys(coordinates).length}フィールド`);
        
        originalCoordinates = JSON.parse(JSON.stringify(coordinates));
        resetRadioGroupsToDefault();
        renderFieldSettings();
        // 初期ロード時にプレビューを表示
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
  // 文字列プロパティの場合はそのまま、その他は数値に変換
  if (property === 'textAlign' || property === 'sampleText') {
    coordinates[key][property] = value;
  } else {
    coordinates[key][property] = parseFloat(value);
  }

  // postal_codeタイプの場合、x/y/postalCodeGap変更時にfirstX/firstY/lastXも同期
  const fieldMapping = getSampleDataFieldMapping();
  const mapping = fieldMapping[key];
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
  const fieldMapping = getSampleDataFieldMapping();
  const mapping = fieldMapping[key];
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
  // 施術日（個別調整）の場合
  if (!controls && key.startsWith('treatment_days_')) {
    controls = document.getElementById('selected-day-controls');
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
    // インターバル終了後に保存とプレビューを実行
    autoPreview();
    autoSave();
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

  // 座標データをそのまま保存（isSelectedフラグも含む）
  const coordinatesToSave = JSON.parse(JSON.stringify(coordinates));

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
// プレビュー実行中フラグとAbortController
let previewPdfInProgress = false;
let previewAbortController = null;
let lastPreviewTime = 0;
const PREVIEW_DEBOUNCE_MS = 200; // 200ms以内の重複呼び出しを無視

function previewPdf() {
  const now = Date.now();
  
  // 200ms以内の重複呼び出しを無視
  if (now - lastPreviewTime < PREVIEW_DEBOUNCE_MS) {
    return;
  }
  lastPreviewTime = now;
  
  // 既に実行中の場合は前のリクエストをキャンセル
  if (previewPdfInProgress && previewAbortController) {
    previewAbortController.abort();
  }
  
  previewPdfInProgress = true;
  previewAbortController = new AbortController();
  
  const iframe = document.getElementById('pdf-iframe');
  const loadingBadge = document.getElementById('preview-loading');
  const overlay = document.getElementById('preview-overlay');
  const clinicUserSelect = document.getElementById('clinic-user-select');
  const clinicUserId = clinicUserSelect ? clinicUserSelect.value : null;
  const yearMonthSelect = document.getElementById('year-month-select');
  const yearMonth = yearMonthSelect ? yearMonthSelect.value : null;
  const showSampleData = document.getElementById('show-sample-data').checked;

  // ローディング表示
  loadingBadge.style.display = 'inline-block';
  overlay.style.display = 'flex';

  // プレビュー用の座標データを作成（サンプルデータモード時はisSelectedフラグを含める）
  const coordinatesForPreview = {};
  Object.keys(coordinates).forEach(key => {
    coordinatesForPreview[key] = {};
    Object.keys(coordinates[key]).forEach(prop => {
      // サンプルデータモード時はisSelectedを含め、通常モード時は除外
      if (showSampleData || prop !== 'isSelected') {
        coordinatesForPreview[key][prop] = coordinates[key][prop];
      }
    });
  });

  const requestBody = {
    coordinates: coordinatesForPreview,
    clinic_user_id: clinicUserId,
    year_month: yearMonth,
    pdf_type: currentPdfType,
    show_sample_data: showSampleData
  };

  // カスタムサンプルデータがある場合は追加
  if (showSampleData && customSampleData) {
    // combine属性があるフィールドの値を変換
    const processedSampleData = {};

    // 座標JSONに定義されているキーの値を取得
    Object.keys(coordinates).forEach(key => {
      const value = getSampleValue(key);
      if (value !== undefined && value !== null && value !== '') {
        processedSampleData[key] = value;
      }
    });

    // customSampleDataに含まれる全てのキーも追加（combine属性で分割された個別フィールドを含む）
    Object.keys(customSampleData).forEach(key => {
      if (customSampleData[key] !== undefined && customSampleData[key] !== null && customSampleData[key] !== '') {
        processedSampleData[key] = customSampleData[key];
      }
    });

    console.log('[previewPdf] 送信するサンプルデータ:', processedSampleData);
    console.log('[previewPdf] public_funds_payer_number:', processedSampleData.public_funds_payer_number);
    requestBody.custom_sample_data = processedSampleData;
  }

  fetch('/prints/preview-pdf', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(requestBody),
    signal: previewAbortController.signal
  })
    .then(response => response.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob);
      iframe.src = url;

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
      
      // 実行中フラグをクリア
      previewPdfInProgress = false;
    })
    .catch(error => {
      // AbortErrorは無視（意図的なキャンセル）
      if (error.name === 'AbortError') {
        return;
      }
      
      console.error('プレビューエラー:', error);
      alert('プレビュー表示に失敗しました');

      // ローディング非表示
      loadingBadge.style.display = 'none';
      overlay.style.display = 'none';
      
      // 実行中フラグをクリア
      previewPdfInProgress = false;
    });
}

// 利用者の施術日を取得
function loadTreatmentDays() {
  const clinicUserSelect = document.getElementById('clinic-user-select');
  const clinicUserId = clinicUserSelect ? clinicUserSelect.value : null;
  const yearMonthSelect = document.getElementById('year-month-select');
  const serviceYearMonth = yearMonthSelect ? yearMonthSelect.value : new Date().toISOString().slice(0, 7);
  const showSampleData = document.getElementById('show-sample-data')?.checked;

  // サンプルモード時は取得不要
  if (showSampleData) {
    window.currentTreatmentDays = Array.from({length: 31}, (_, i) => i + 1);
    return;
  }

  // 利用者が選択されていない場合は空配列
  if (!clinicUserId) {
    window.currentTreatmentDays = [];
    return;
  }

  // 施術日を取得
  fetch(`/prints/get-treatment-days?clinic_user_id=${clinicUserId}&service_year_month=${serviceYearMonth}&pdf_type=${currentPdfType}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.currentTreatmentDays = data.treatment_days || [];
        console.log('[施術日取得] 利用者ID:', clinicUserId, '施術日:', window.currentTreatmentDays);

        // 施術日セレクトボックスが既に表示されている場合は更新
        renderFieldSettings();
      } else {
        console.warn('[施術日取得] 失敗:', data.message);
        window.currentTreatmentDays = [];
      }
    })
    .catch(error => {
      console.error('[施術日取得] エラー:', error);
      window.currentTreatmentDays = [];
    });
}

// カスタムサンプルデータをlocalStorageから読み込み
function loadCustomSampleData() {
  const storageKey = 'customSampleData_' + currentPdfType;
  const stored = localStorage.getItem(storageKey);
  console.log('[loadCustomSampleData] LocalStorageから読み込み:', storageKey, stored ? 'データあり' : 'データなし');
  if (stored) {
    try {
      const savedData = JSON.parse(stored);
      console.log('[loadCustomSampleData] 保存データ:', savedData);
      // デフォルト値とマージ（空文字やnullの場合はデフォルト値を優先）
      Object.keys(savedData).forEach(key => {
        if (savedData[key] !== '' && savedData[key] !== null && savedData[key] !== undefined) {
          customSampleData[key] = savedData[key];
        }
      });
      console.log('[loadCustomSampleData] マージ後のcustomSampleData.public_funds_payer_number:', customSampleData.public_funds_payer_number);
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

  // localStorageの空文字データをクリーンアップして再保存
  const cleanedData = {};
  Object.keys(customSampleData).forEach(key => {
    if (customSampleData[key] !== '' && customSampleData[key] !== null && customSampleData[key] !== undefined) {
      cleanedData[key] = customSampleData[key];
    }
  });
  localStorage.setItem(storageKey, JSON.stringify(cleanedData));
}

// サンプルデータを更新
function updateSampleData(field, value) {
  console.log('[updateSampleData] フィールド更新:', { field, value, before: customSampleData[field] });
  customSampleData[field] = value;

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));
  console.log('[updateSampleData] LocalStorage保存完了:', storageKey);

  // プレビューを自動更新
  autoPreview();
}

// 結合フィールド（combine属性あり）のサンプルデータを更新
function updateCombinedSampleData(combineFields, value) {
  console.log('updateCombinedSampleData called', { combineFields, value });

  // スペースで分割（全角・半角両対応）
  const parts = value.split(/[\s　]+/).filter(p => p);
  console.log('Split parts:', parts);

  // 分割した値を各フィールドに割り当て
  combineFields.forEach((field, index) => {
    customSampleData[field] = parts[index] || '';
    console.log(`Set ${field} = ${parts[index] || ''}`);
  });

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));

  // プレビューを自動更新
  autoPreview();
}

