// Bladeファイルから渡された変数を取得
const coordinateAdjusterData = window.coordinateAdjusterData || {};
let currentPdfType = coordinateAdjusterData.currentPdfType; // let に変更して更新可能に
const masterData = coordinateAdjusterData.masterData || {};
const treatmentFees = coordinateAdjusterData.treatmentFees;
const csrfToken = coordinateAdjusterData.csrfToken;

// 座標データ管理用変数
let coordinates = {};
let originalCoordinates = {};

// カスタムタイトルテキスト（PDFタイトル）
let customTitleText = '';

// ============================================================
// PDFタイプに応じたフィールド定義を取得
// ============================================================
// ⚠️ 必読: storage/app/config/PDF_TYPES_README.md
// 読まずに作業すると、座標調整画面で不具合が発生する
// ============================================================
//
// 1. storage/app/config/pdf_types.json に新しいタイプのエントリを追加
//    【重要】fieldsFile を空文字列 "" にしない！（フィールドラベルが英語になる）
//
// 2. storage/app/config/ に座標設定JSONファイルを作成
//    【重要】初期値は必ず {} にすること（[] にすると座標がリロードのたびにリセットされる）
//    原因：[] はJSで配列として扱われ、文字列キーがJSON.stringifyで消えるため保存が機能しない
//
// 3. public/js/coordinate-adjuster_fields.js に専用のフィールド定義オブジェクトを追加
//    （または既存のフィールド定義を使い回す）
//
// 4. この関数（getFieldDefinitions）に新しいPDFタイプのケースを追加
//
// ⚠️【注意点】getFieldDefinitions()関数の実装上の注意点（2026/02/16）
// ============================================================
// 問題：
//   fieldsFileの空文字チェック（return {}）をPDFタイプ別分岐より先に書くと、
//   pdf_types.jsonでfieldsFile=""のPDFタイプが追加されたとき、
//   専用のフィールド定義が読み込まれずフィールドラベルが英語になる。
//
// 解決策：
//   getFieldDefinitions()関数では、必ずPDFタイプ別の分岐処理を先に記述し、
//   fieldsFileの空文字チェックは最後に配置すること。
//
// 理由：
//   一部のPDFタイプ（例: consent_acupuncture）はテンプレートPDFを持たないため、
//   pdf_types.jsonでfieldsFile=""と設定される。
//   もしfieldsFileチェックが先に実行されると、専用のcase文に到達する前に
//   空オブジェクト{}が返されてしまい、フィールド定義が読み込まれない。
// ============================================================
//    【重要】追加しないとフィールドラベルが英語（キー名）で表示される
//
// 5. coordinate-adjuster_categories.js の以下2つの関数に追加
//    - getFieldCategories()
//    - getCategoryOrder()
//    【重要】追加しないと不適切なカテゴリが表示される
//
// 6. app/Services/Print/ にPDF生成サービスクラスを作成
//
// 【例】同意書依頼状（医師指定版）の場合：
// - pdf_types.jsonに追加: "consent_request_letter_designated_acupuncture"
//   fieldsFile: "consent_request_letter_sample_acupuncture.js" （サンプル版と同じ定義を使い回し）
// - 座標JSONファイル作成: consent_request_letter_designated_acupuncture_coordinates.json
// - フィールド定義: fieldDefinitionsConsentRequestLetterSampleAcupuncture を使い回し
// - この関数に追加: currentPdfType === 'consent_request_letter_designated_acupuncture'
// - カテゴリ関数に追加: getFieldCategories() と getCategoryOrder()
// - サービスクラス作成: ConsentRequestLetterDesignatedAcupuncturePdfService.php
// ============================================================
function getFieldDefinitions() {
  const pdfTypes = coordinateAdjusterData.pdfTypes || {};
  const currentConfig = pdfTypes[currentPdfType] || {};

  // PDFタイプ別のフィールド定義を優先的に返す
  if (currentPdfType === 'power_of_attorney_application') {
    return fieldDefinitionsPowerOfAttorneyApplication;
  }

  if (currentPdfType === 'power_of_attorney_consent') {
    return fieldDefinitionsPowerOfAttorneyConsent;
  }

  if (currentPdfType === 'treatment_receipt') {
    return fieldDefinitionsTreatmentReceipt;
  }

  // 同意書（はり・きゅう）用
  if (currentPdfType === 'consent_acupuncture') {
    return fieldDefinitionsConsentAcupuncture;
  }

  // 同意書（あんま・マッサージ）用
  if (currentPdfType === 'consent_massage') {
    return fieldDefinitionsConsentMassage;
  }

  // 同意書依頼状（サンプル版・医師指定版）はり・きゅう用
  // ※座標とフィールドが同じなので、同じフィールド定義を使い回す
  if (currentPdfType === 'consent_request_letter_sample_acupuncture' ||
      currentPdfType === 'consent_request_letter_designated_acupuncture') {
    return fieldDefinitionsConsentRequestLetterSampleAcupuncture;
  }

  // 同意書依頼状（サンプル版・医師指定版）マッサージ用
  // ※はり・きゅう版と座標・フィールドが同じなので、同じフィールド定義を使い回す
  if (currentPdfType === 'consent_request_letter_sample_massage' ||
      currentPdfType === 'consent_request_letter_designated_massage') {
    return fieldDefinitionsConsentRequestLetterSampleAcupuncture;
  }

  // 施術録（はり・きゅう）用
  if (currentPdfType === 'treatment_record_acupuncture') {
    return fieldDefinitionsTreatmentRecordAcupuncture;
  }

  // 総括表用
  if (currentPdfType === 'summary_table') {
    return fieldDefinitionsSummaryTable;
  }

  // 実施計画書用
  if (currentPdfType === 'implementation_plan') {
    return fieldDefinitionsImplementationPlan;
  }

  // 医師への御礼状用
  if (currentPdfType === 'thank_you_letter_doctor') {
    return fieldDefinitionsThankYouLetterDoctor;
  }

  // 紹介者への御礼状用
  if (currentPdfType === 'thank_you_letter_referrer') {
    return fieldDefinitionsThankYouLetterReferrer;
  }

  // fieldsFileが空の場合は空オブジェクトを返す
  if (!currentConfig.fieldsFile || currentConfig.fieldsFile === '') {
    return {};
  }

  return fieldDefinitions;
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

        // フィールド定義から新規フィールドを追加（既存フィールドは保持）
        // 現在のPDFタイプに関連するフィールドのみを追加
        const fieldMapping = getFieldDefinitions();
        const fieldCategories = getFieldCategories(currentPdfType);
        const hasCategories = Object.keys(fieldCategories).length > 0;

        Object.keys(fieldMapping).forEach(key => {
          const definition = fieldMapping[key];

          // カテゴリが定義されている場合のみ、カテゴリに含まれないフィールドをスキップ
          if (hasCategories && !fieldCategories.hasOwnProperty(key)) {
            return;
          }

          // coordinatesに存在しない場合のみ新規作成
          if (!coordinates[key]) {
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
          } else {
            // 既存フィールドにはUI調整プロパティのみフィールド定義からマージ
            // （座標JSONにないがフィールド定義で定義されているプロパティを補完）
            const uiOnlyProps = ['rowLineHeight', 'verticalSpacing', 'lineHeight', 'maxCharsPerLine'];
            uiOnlyProps.forEach(prop => {
              if (definition[prop] !== undefined && coordinates[key][prop] === undefined) {
                coordinates[key][prop] = definition[prop];
              }
            });
          }
        });
        
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
  const fieldDefs = getFieldDefinitions();
  const fieldMapping = fieldDefs[key];
  if (fieldMapping && fieldMapping.type === 'postal_code') {
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

  // radioGroupとcompositeGroup名を取得
  const radioGroupName = fieldMapping?.radioGroup;
  const compositeGroupName = coordinates[key].compositeGroup;

  // テキスト配置更新の場合はボタンのアクティブ状態を更新
  if (property === 'textAlign') {
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

  // UI要素（input）を更新
  let controls = document.getElementById('controls-' + key);
  if (!controls && radioGroupName) {
    controls = document.getElementById('radiogroup-fields-' + radioGroupName);
  }
  if (!controls && compositeGroupName) {
    controls = document.getElementById('compositegroup-fields-' + compositeGroupName);
  }
  if (!controls && key.startsWith('treatment_days_')) {
    controls = document.getElementById('selected-day-controls');
  }

  if (controls) {
    const input = controls.querySelector(`input[data-property="${property}"]`);
    if (input && property !== 'textAlign' && property !== 'sampleText') {
      input.value = parseFloat(value);
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
  const fieldDefs = getFieldDefinitions();
  const fieldMapping = fieldDefs[key];
  if (fieldMapping && fieldMapping.type === 'postal_code') {
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
  const radioGroupName = fieldMapping?.radioGroup;
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

    requestBody.custom_sample_data = processedSampleData;
  }

  // custom_title_textは常に送信（ノーマルモード時も描画するため）
  if (customTitleText) {
    requestBody.custom_title_text = customTitleText;
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

        // 施術日セレクトボックスが既に表示されている場合は更新
        renderFieldSettings();
      } else {
        window.currentTreatmentDays = [];
      }
    })
    .catch(error => {
      window.currentTreatmentDays = [];
    });
}

// カスタムサンプルデータをlocalStorageから読み込み
function loadCustomSampleData() {
  const storageKey = 'customSampleData_' + currentPdfType;
  const stored = localStorage.getItem(storageKey);
  if (stored) {
    try {
      const savedData = JSON.parse(stored);
      // デフォルト値とマージ（空文字やnullの場合はデフォルト値を優先）
      Object.keys(savedData).forEach(key => {
        if (savedData[key] !== '' && savedData[key] !== null && savedData[key] !== undefined) {
          customSampleData[key] = savedData[key];
        }
      });
    } catch (e) {
      console.error('サンプルデータの読み込みエラー:', e);
    }
  }

  // customTitleTextを個別に読み込み
  const titleStorageKey = 'customTitleText_' + currentPdfType;
  const storedTitle = localStorage.getItem(titleStorageKey);
  if (storedTitle) {
    customTitleText = storedTitle;
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
  customSampleData[field] = value;

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));

  // プレビューを自動更新
  autoPreview();
}

// カスタムタイトルテキストを更新
function updateCustomTitleText(value) {
  customTitleText = value;

  // localStorageに保存
  const storageKey = 'customTitleText_' + currentPdfType;
  localStorage.setItem(storageKey, value);

  // プレビューを自動更新
  autoPreview();
}

// 結合フィールド（combine属性あり）のサンプルデータを更新
function updateCombinedSampleData(combineFields, value) {
  // スペースで分割（全角・半角両対応）
  const parts = value.split(/[\s　]+/).filter(p => p);

  // 分割した値を各フィールドに割り当て
  combineFields.forEach((field, index) => {
    customSampleData[field] = parts[index] || '';
  });

  // localStorageに保存
  const storageKey = 'customSampleData_' + currentPdfType;
  localStorage.setItem(storageKey, JSON.stringify(customSampleData));

  // プレビューを自動更新
  autoPreview();
}

