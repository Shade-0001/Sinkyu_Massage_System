// カテゴリアコーディオン対応のrenderFieldSettings関数（新版）
function renderFieldSettings() {
  const container = document.getElementById('field-settings');
  container.innerHTML = '';

  // 現在のPDFタイプを取得
  const currentPdfType = window.coordinateAdjusterData?.currentPdfType || 'therapy_benefit_acupuncture';
  
  // PDFタイプに応じたカテゴリ定義を取得
  const fieldCategories = getFieldCategories(currentPdfType);
  const categoryOrder = getCategoryOrder(currentPdfType);

  // radioGroupとcompositeGroupでグループ化されたフィールドを追跡
  const processedGroups = new Set();
  const processedCompositeGroups = new Set();
  const processedKeys = new Set();

  // sampleDataFieldMappingの順序でフィールドを処理（coordinatesに存在しなくても表示）
  const orderedKeys = Object.keys(sampleDataFieldMapping);

  // coordinatesに存在するが、sampleDataFieldMappingにないキーも追加
  Object.keys(coordinates).forEach(key => {
    if (!orderedKeys.includes(key)) {
      orderedKeys.push(key);
    }
  });

  // カテゴリごとにフィールドをグループ化
  const categorizedFields = {};
  categoryOrder.forEach(category => {
    categorizedFields[category] = [];
  });
  categorizedFields['uncategorized'] = []; // 未分類用

  // PDFタイプ別にフィールドをフィルタリングする関数
  function shouldIncludeField(key, pdfType) {
    if (pdfType === 'treatment_receipt') {
      // 施術料金領収書は専用のフィールド定義を使用（現在は空）
      // coordinatesに存在するフィールドのみ表示
      return coordinates.hasOwnProperty(key);
    } else if (pdfType === 'therapy_benefit_acupuncture') {
      // 鍼灸用PDFではマッサージ専用フィールドを除外
      const massageOnlyFields = [
        'illness_name_symptom',
        'fee_massage_trunk_unit', 'fee_massage_trunk_count', 'fee_massage_trunk_total',
        'fee_massage_upper_limb_r_unit', 'fee_massage_upper_limb_r_count', 'fee_massage_upper_limb_r_total',
        'fee_massage_upper_limb_l_unit', 'fee_massage_upper_limb_l_count', 'fee_massage_upper_limb_l_total',
        'fee_massage_lower_limb_r_unit', 'fee_massage_lower_limb_r_count', 'fee_massage_lower_limb_r_total',
        'fee_massage_lower_limb_l_unit', 'fee_massage_lower_limb_l_count', 'fee_massage_lower_limb_l_total',
        'fee_manual_correction_unit', 'fee_manual_correction_count', 'fee_manual_correction_total',
        'fee_fomentation_unit', 'fee_fomentation_count', 'fee_fomentation_total',
        'fee_fomentation_electric_light_unit', 'fee_fomentation_electric_light_count', 'fee_fomentation_electric_light_total'
      ];
      return !massageOnlyFields.includes(key);
    } else if (pdfType === 'therapy_benefit_massage') {
      // マッサージ用PDFでは鍼灸専用フィールドを除外
      const acupunctureOnlyFields = [
        'fee_initial_examination_hari', 'fee_initial_examination_kyu', 'fee_initial_examination_combined',
        'fee_initial_examination_amount',
        'therapy_content_electric_needle', 'therapy_content_electric_moxa', 'therapy_content_electric_light',
        'fee_hari_unit', 'fee_hari_count', 'fee_hari_total',
        'fee_kyu_unit', 'fee_kyu_count', 'fee_kyu_total',
        'fee_hari_kyu_unit', 'fee_hari_kyu_count', 'fee_hari_kyu_total',
        'fee_electric_unit', 'fee_electric_count', 'fee_electric_total',
        // 傷病名（鍼灸用）
        'illness_name_1', 'illness_name_2', 'illness_name_3', 'illness_name_4',
        'illness_name_5', 'illness_name_6', 'illness_name_7', 'illness_name_other_text',
        // 旧マッサージフィールド（5部位に分割したため除外）
        'fee_massage_unit', 'fee_massage_count', 'fee_massage_total'
      ];
      return !acupunctureOnlyFields.includes(key);
    }
    return true;
  }

  // フィールドをカテゴリに振り分け
  orderedKeys.forEach(key => {
    // PDFタイプに応じてフィールドをフィルタリング
    if (!shouldIncludeField(key, currentPdfType)) {
      return; // このフィールドをスキップ
    }

    const category = fieldCategories[key] || 'uncategorized';
    if (!categorizedFields[category]) {
      categorizedFields[category] = [];
    }
    categorizedFields[category].push(key);
  });

  // カテゴリごとにアコーディオンを作成
  categoryOrder.forEach(category => {
    const fields = categorizedFields[category];
    if (!fields || fields.length === 0) return;

    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'category-group';

    const categoryLabel = categoryLabels[category] || category;

    categoryDiv.innerHTML = `
      <h5 class="category-header" onclick="toggleCategory('${category}')">
        <span class="toggle-icon" id="toggle-category-${category}">▶</span> ${categoryLabel}
      </h5>
      <div class="category-content" id="category-content-${category}">
        <!-- カテゴリ内のフィールドをここに配置 -->
      </div>
    `;

    container.appendChild(categoryDiv);

    const categoryContent = document.getElementById(`category-content-${category}`);

    // カテゴリ内のフィールドを処理
    fields.forEach(key => {
      if (processedKeys.has(key)) return;

      let field = coordinates[key];
      // coordinatesに存在しない場合はsampleDataFieldMappingから作成
      if (!field && sampleDataFieldMapping[key]) {
        field = {
          x: 0,
          y: 0,
          fontSize: 10,
          letterSpacing: 0,
          textAlign: 'left',
          ...sampleDataFieldMapping[key]
        };
        coordinates[key] = field;
      }
      if (!field) return;

      // compositeGroupの処理
      if (field.compositeGroup && !processedCompositeGroups.has(field.compositeGroup)) {
        processedCompositeGroups.add(field.compositeGroup);

        const groupFields = Object.entries(coordinates)
          .filter(([k, v]) => v.compositeGroup === field.compositeGroup)
          .sort((a, b) => {
            const indexA = orderedKeys.indexOf(a[0]);
            const indexB = orderedKeys.indexOf(b[0]);
            return indexA - indexB;
          });

        groupFields.forEach(([k]) => processedKeys.add(k));

        const firstKey = groupFields[0][0];
        let selectedKey = firstKey;

        const div = document.createElement('div');
        div.className = 'field-group';
        div.setAttribute('data-composite-group', field.compositeGroup);

        const groupLabel = field.compositeLabel || field.compositeGroup;

        const options = groupFields.map(([k, v]) => {
          const mapping = sampleDataFieldMapping[k];
          let optionLabel = v.label;
          if (mapping) {
            if (mapping.optionLabel) {
              optionLabel = mapping.optionLabel;
            } else if (mapping.label) {
              optionLabel = mapping.label;
            }
          }
          return `<option value="${k}" ${selectedKey === k ? 'selected' : ''}>${optionLabel}</option>`;
        }).join('');

        div.innerHTML = `
          <h6 class="field-header" onclick="toggleField('${field.compositeGroup}')" style="cursor: pointer; user-select: none;">
            <span class="toggle-icon" id="toggle-${field.compositeGroup}">▶</span> ${groupLabel}
          </h6>

          <div class="field-controls" id="controls-${field.compositeGroup}">
            <div class="coordinate-input">
              <label>要素選択:</label>
              <select onchange="updateCompositeGroupSelection('${field.compositeGroup}', this.value)"
                      class="form-control form-control-sm"
                      style="width: auto; display: inline-block; margin-left: 10px;">
                ${options}
              </select>
            </div>

            <div id="compositegroup-fields-${field.compositeGroup}">
              <!-- 選択された要素の詳細設定をここに表示 -->
            </div>
          </div>
        `;

        categoryContent.appendChild(div);
        updateCompositeGroupSelection(field.compositeGroup, selectedKey);
        return;
      }

      if (field.compositeGroup) {
        return;
      }

      // radioGroupの処理
      if (field.radioGroup && !processedGroups.has(field.radioGroup)) {
        processedGroups.add(field.radioGroup);

        const groupFields = Object.entries(coordinates)
          .filter(([k, v]) => v.radioGroup === field.radioGroup)
          .sort((a, b) => {
            const indexA = orderedKeys.indexOf(a[0]);
            const indexB = orderedKeys.indexOf(b[0]);
            if (indexA !== -1 && indexB !== -1) return indexA - indexB;

            const numA = parseInt(a[0].match(/\d+$/)?.[0] || 0);
            const numB = parseInt(b[0].match(/\d+$/)?.[0] || 0);
            return numA - numB;
          });

        groupFields.forEach(([k]) => processedKeys.add(k));

        const firstField = groupFields[0][1];
        const firstKey = groupFields[0][0];

        let selectedKey = firstKey;
        for (const [k, v] of groupFields) {
          if (v.isSelected) {
            selectedKey = k;
            break;
          }
        }

        const div = document.createElement('div');
        div.className = 'field-group';
        div.setAttribute('data-radio-group', field.radioGroup);

        const groupLabel = field.label || field.radioGroup;

        const options = groupFields.map(([k, v]) => {
          return `<option value="${k}" ${selectedKey === k ? 'selected' : ''}>${v.optionLabel || v.label}</option>`;
        }).join('');

        div.innerHTML = `
          <h6 class="field-header" onclick="toggleField('${field.radioGroup}')" style="cursor: pointer; user-select: none;">
            <span class="toggle-icon" id="toggle-${field.radioGroup}">▶</span> ${groupLabel}
          </h6>

          <div class="field-controls" id="controls-${field.radioGroup}">
            <div class="coordinate-input">
              <label>選択:</label>
              <select onchange="updateRadioGroupSelection('${field.radioGroup}', this.value)"
                      class="form-control form-control-sm"
                      style="width: auto; display: inline-block; margin-left: 10px;">
                ${options}
              </select>
            </div>

            <div id="radiogroup-fields-${field.radioGroup}">
              <!-- 選択されたオプションの詳細設定をここに表示 -->
            </div>
          </div>
        `;

        categoryContent.appendChild(div);
        updateRadioGroupSelection(field.radioGroup, selectedKey);
        return;
      }

      if (field.radioGroup) {
        return;
      }


      // 通常フィールドの処理
      processedKeys.add(key);

      const div = document.createElement('div');
      div.className = 'field-group';

      // フィールドの詳細HTMLを生成（既存のロジックを使用）
      div.innerHTML = renderSingleFieldHTML(key, field);

      categoryContent.appendChild(div);
    });
  });

  // 未分類フィールドがあれば最後に追加
  if (categorizedFields['uncategorized'] && categorizedFields['uncategorized'].length > 0) {
    // 同様の処理...
  }
}

// カテゴリアコーディオンの開閉
function toggleCategory(categoryId) {
  const content = document.getElementById(`category-content-${categoryId}`);
  const icon = document.getElementById(`toggle-category-${categoryId}`);

  if (content.classList.contains('show')) {
    // 格納
    content.style.maxHeight = content.scrollHeight + 'px';
    setTimeout(() => {
      content.style.maxHeight = '0';
      content.style.paddingTop = '0';
      content.style.paddingBottom = '0';
    }, 10);
    content.classList.remove('show');
    icon.textContent = '▶';
  } else {
    // 展開
    content.classList.add('show');
    content.style.maxHeight = content.scrollHeight + 'px';
    content.style.paddingTop = '10px';
    content.style.paddingBottom = '10px';
    icon.textContent = '▼';

    // アニメーション完了後にmax-heightを自動調整可能にする
    setTimeout(() => {
      if (content.classList.contains('show')) {
        content.style.maxHeight = 'none';
      }
    }, 300);
  }
}

// 単一フィールドのHTML生成（既存のロジックから抽出）
function renderSingleFieldHTML(key, field) {
  return `
    <h6 class="field-header" onclick="toggleField('${key}')" style="cursor: pointer; user-select: none;">
      <span class="toggle-icon" id="toggle-${key}">▶</span> ${field.label || key}
    </h6>

    <div class="field-controls" id="controls-${key}">
      ${field.ellipseX !== undefined ? `
      <div class="coordinate-input">
        <label>X座標:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseX', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseX', -0.5)"
                ontouchend="stopLongPress()">←</button>
        <input type="number" step="0.5" value="${field.ellipseX}"
               onchange="updateCoordinate('${key}', 'ellipseX', this.value)"
               class="form-control form-control-sm" data-property="ellipseX">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseX', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseX', 0.5)"
                ontouchend="stopLongPress()">→</button>
      </div>
      ` : ''}

      ${field.ellipseY !== undefined ? `
      <div class="coordinate-input">
        <label>Y座標:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseY', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseY', -0.5)"
                ontouchend="stopLongPress()">↑</button>
        <input type="number" step="0.5" value="${field.ellipseY}"
               onchange="updateCoordinate('${key}', 'ellipseY', this.value)"
               class="form-control form-control-sm" data-property="ellipseY">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseY', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseY', 0.5)"
                ontouchend="stopLongPress()">↓</button>
      </div>
      ` : ''}

      ${field.x !== undefined ? `
      <div class="coordinate-input">
        <label>${field.ellipseX !== undefined ? 'X座標（テキスト）:' : 'X座標:'}</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'x', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'x', -0.5)"
                ontouchend="stopLongPress()">←</button>
        <input type="number" step="0.5" value="${field.x}"
               onchange="updateCoordinate('${key}', 'x', this.value)"
               class="form-control form-control-sm" data-property="x">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'x', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'x', 0.5)"
                ontouchend="stopLongPress()">→</button>
      </div>
      ` : ''}

      ${field.y !== undefined ? `
      <div class="coordinate-input">
        <label>${field.ellipseY !== undefined ? 'Y座標（テキスト）:' : 'Y座標:'}</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'y', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'y', -0.5)"
                ontouchend="stopLongPress()">↑</button>
        <input type="number" step="0.5" value="${field.y}"
               onchange="updateCoordinate('${key}', 'y', this.value)"
               class="form-control form-control-sm" data-property="y">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'y', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'y', 0.5)"
                ontouchend="stopLongPress()">↓</button>
      </div>
      ` : ''}

      ${field.fontSize !== undefined ? `
      <div class="coordinate-input">
        <label>フォントサイズ:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'fontSize', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'fontSize', -0.5)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.5" value="${field.fontSize}"
               onchange="updateCoordinate('${key}', 'fontSize', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="fontSize">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'fontSize', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'fontSize', 0.5)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.letterSpacing !== undefined ? `
      <div class="coordinate-input">
        <label>文字間隔:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'letterSpacing', -0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'letterSpacing', -0.1)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.1" value="${field.letterSpacing}"
               onchange="updateCoordinate('${key}', 'letterSpacing', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="letterSpacing">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'letterSpacing', 0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'letterSpacing', 0.1)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.postalCodeGap !== undefined ? `
      <div class="coordinate-input">
        <label>郵便番号間隔:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'postalCodeGap', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'postalCodeGap', -0.5)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.5" value="${field.postalCodeGap || 2}"
               onchange="updateCoordinate('${key}', 'postalCodeGap', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="postalCodeGap">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'postalCodeGap', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'postalCodeGap', 0.5)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.textAlign !== undefined ? `
      <div class="coordinate-input">
        <label>配置:</label>
        <select onchange="updateCoordinate('${key}', 'textAlign', this.value)"
                class="form-control form-control-sm" style="width: auto;" data-property="textAlign">
          <option value="left" ${field.textAlign === 'left' ? 'selected' : ''}>左</option>
          <option value="center" ${field.textAlign === 'center' ? 'selected' : ''}>中央</option>
          <option value="right" ${field.textAlign === 'right' ? 'selected' : ''}>右</option>
        </select>
      </div>
      ` : ''}

      ${field.ellipseWidth !== undefined ? `
      <div class="coordinate-input">
        <label>楕円幅:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseWidth', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseWidth', -0.5)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.5" value="${field.ellipseWidth}"
               onchange="updateCoordinate('${key}', 'ellipseWidth', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="ellipseWidth">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseWidth', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseWidth', 0.5)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.ellipseHeight !== undefined ? `
      <div class="coordinate-input">
        <label>楕円高さ:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseHeight', -0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseHeight', -0.5)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.5" value="${field.ellipseHeight}"
               onchange="updateCoordinate('${key}', 'ellipseHeight', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="ellipseHeight">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'ellipseHeight', 0.5)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'ellipseHeight', 0.5)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.circleRadius !== undefined ? `
      <div class="coordinate-input">
        <label>円のサイズ:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'circleRadius', -0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'circleRadius', -0.1)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.1" value="${field.circleRadius}"
               onchange="updateCoordinate('${key}', 'circleRadius', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="circleRadius">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'circleRadius', 0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'circleRadius', 0.1)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.doubleCircleInnerRadius !== undefined ? `
      <div class="coordinate-input">
        <label>二重丸の内円のサイズ:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', -0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', -0.1)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.1" value="${field.doubleCircleInnerRadius}"
               onchange="updateCoordinate('${key}', 'doubleCircleInnerRadius', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="doubleCircleInnerRadius">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'doubleCircleInnerRadius', 0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'doubleCircleInnerRadius', 0.1)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${field.circleSpacing !== undefined ? `
      <div class="coordinate-input">
        <label>円の間隔:</label>
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'circleSpacing', -0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'circleSpacing', -0.1)"
                ontouchend="stopLongPress()">−</button>
        <input type="number" step="0.1" value="${field.circleSpacing}"
               onchange="updateCoordinate('${key}', 'circleSpacing', this.value)"
               class="form-control form-control-sm" style="width: 80px;" data-property="circleSpacing">
        <button class="btn btn-sm btn-outline-secondary btn-adjust"
                onmousedown="startLongPress('${key}', 'circleSpacing', 0.1)"
                onmouseup="stopLongPress()"
                onmouseleave="stopLongPress()"
                ontouchstart="startLongPress('${key}', 'circleSpacing', 0.1)"
                ontouchend="stopLongPress()">+</button>
      </div>
      ` : ''}

      ${(() => {
        const sampleDataHtml = getSampleDataInput(key);
        return sampleDataHtml || '';
      })()}
    </div>
  `;
}
function updateCompositeGroupSelection(groupName, selectedKey) {
  // radioGroupの場合、isSelectedを更新
  const selectedField = coordinates[selectedKey];
  if (selectedField && selectedField.radioGroup) {
    Object.keys(coordinates).forEach(key => {
      const field = coordinates[key];
      if (field.radioGroup === selectedField.radioGroup) {
        field.isSelected = (key === selectedKey);
      }
    });

    // サンプルデータを更新
    const mapping = sampleDataFieldMapping[selectedKey];
    if (mapping && mapping.field && mapping.optionLabel) {
      updateSampleData(mapping.field, mapping.optionLabel);
    }
  }

  const fieldsContainer = document.getElementById(`compositegroup-fields-${groupName}`);
  if (!fieldsContainer) return;

  fieldsContainer.innerHTML = '';

  if (!selectedField) return;

  const detailsDiv = document.createElement('div');
  detailsDiv.style.borderTop = '1px solid #ddd';
  detailsDiv.style.marginTop = '10px';
  detailsDiv.style.paddingTop = '10px';

  // 選択されたフィールドのマッピング情報を取得
  const mapping = sampleDataFieldMapping[selectedKey];
  const fieldLabel = mapping ? (mapping.optionLabel || mapping.label) : selectedField.label;

  // フィールド名表示
  const labelDiv = document.createElement('div');
  labelDiv.style.marginBottom = '10px';
  labelDiv.innerHTML = `<strong>調整対象: ${fieldLabel}</strong>`;
  detailsDiv.appendChild(labelDiv);

  // X座標
  const xDiv = document.createElement('div');
  xDiv.className = 'coordinate-input';
  xDiv.innerHTML = `<label>X座標:</label>`;
  
  const xBtnLeft = document.createElement('button');
  xBtnLeft.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnLeft.innerHTML = '←';
  xBtnLeft.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', -0.5));
  xBtnLeft.addEventListener('mouseup', stopLongPress);
  xBtnLeft.addEventListener('mouseleave', stopLongPress);
  xBtnLeft.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', -0.5));
  xBtnLeft.addEventListener('touchend', stopLongPress);
  
  const xInput = document.createElement('input');
  xInput.type = 'number';
  xInput.step = '0.5';
  xInput.value = selectedField.x;
  xInput.className = 'form-control form-control-sm';
  xInput.style.width = '80px';
  xInput.style.display = 'inline-block';
  xInput.style.marginLeft = '5px';
  xInput.style.marginRight = '5px';
  xInput.setAttribute('data-property', 'x');
  xInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'x', this.value);
  });
  
  const xBtnRight = document.createElement('button');
  xBtnRight.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  xBtnRight.innerHTML = '→';
  xBtnRight.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('mouseup', stopLongPress);
  xBtnRight.addEventListener('mouseleave', stopLongPress);
  xBtnRight.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', 0.5));
  xBtnRight.addEventListener('touchend', stopLongPress);
  
  xDiv.appendChild(xBtnLeft);
  xDiv.appendChild(xInput);
  xDiv.appendChild(xBtnRight);
  detailsDiv.appendChild(xDiv);

  // Y座標
  const yDiv = document.createElement('div');
  yDiv.className = 'coordinate-input';
  yDiv.innerHTML = `<label>Y座標:</label>`;
  
  const yBtnUp = document.createElement('button');
  yBtnUp.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnUp.innerHTML = '↑';
  yBtnUp.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('mouseup', stopLongPress);
  yBtnUp.addEventListener('mouseleave', stopLongPress);
  yBtnUp.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', -0.5));
  yBtnUp.addEventListener('touchend', stopLongPress);
  
  const yInput = document.createElement('input');
  yInput.type = 'number';
  yInput.step = '0.5';
  yInput.value = selectedField.y;
  yInput.className = 'form-control form-control-sm';
  yInput.style.width = '80px';
  yInput.style.display = 'inline-block';
  yInput.style.marginLeft = '5px';
  yInput.style.marginRight = '5px';
  yInput.setAttribute('data-property', 'y');
  yInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'y', this.value);
  });
  
  const yBtnDown = document.createElement('button');
  yBtnDown.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  yBtnDown.innerHTML = '↓';
  yBtnDown.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('mouseup', stopLongPress);
  yBtnDown.addEventListener('mouseleave', stopLongPress);
  yBtnDown.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', 0.5));
  yBtnDown.addEventListener('touchend', stopLongPress);
  
  yDiv.appendChild(yBtnUp);
  yDiv.appendChild(yInput);
  yDiv.appendChild(yBtnDown);
  detailsDiv.appendChild(yDiv);

  // サークルフィールドかどうかを判定
  const isShapeOnly = selectedField.ellipseX !== undefined || selectedField.ellipseY !== undefined || selectedField.circleRadius !== undefined;

  // フォントサイズ（サークルフィールドでは非表示）
  if (!isShapeOnly && selectedField.fontSize !== undefined) {
  const fsDiv = document.createElement('div');
  fsDiv.className = 'coordinate-input';
  fsDiv.innerHTML = `<label>フォントサイズ:</label>`;
  
  const fsBtnMinus = document.createElement('button');
  fsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnMinus.innerHTML = '−';
  fsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('mouseup', stopLongPress);
  fsBtnMinus.addEventListener('mouseleave', stopLongPress);
  fsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const fsInput = document.createElement('input');
  fsInput.type = 'number';
  fsInput.step = '0.5';
  fsInput.value = selectedField.fontSize;
  fsInput.className = 'form-control form-control-sm';
  fsInput.style.width = '80px';
  fsInput.style.display = 'inline-block';
  fsInput.style.marginLeft = '5px';
  fsInput.style.marginRight = '5px';
  fsInput.setAttribute('data-property', 'fontSize');
  fsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'fontSize', this.value);
  });
  
  const fsBtnPlus = document.createElement('button');
  fsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnPlus.innerHTML = '+';
  fsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('mouseup', stopLongPress);
  fsBtnPlus.addEventListener('mouseleave', stopLongPress);
  fsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('touchend', stopLongPress);
  
  fsDiv.appendChild(fsBtnMinus);
  fsDiv.appendChild(fsInput);
  fsDiv.appendChild(fsBtnPlus);
  detailsDiv.appendChild(fsDiv);
  }

  // 文字間隔（サークルフィールドでは非表示）
  if (!isShapeOnly && selectedField.letterSpacing !== undefined) {
  const lsDiv = document.createElement('div');
  lsDiv.className = 'coordinate-input';
  lsDiv.innerHTML = `<label>文字間隔:</label>`;
  
  const lsBtnMinus = document.createElement('button');
  lsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnMinus.innerHTML = '−';
  lsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('mouseup', stopLongPress);
  lsBtnMinus.addEventListener('mouseleave', stopLongPress);
  lsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const lsInput = document.createElement('input');
  lsInput.type = 'number';
  lsInput.step = '0.1';
  lsInput.value = selectedField.letterSpacing || 0;
  lsInput.className = 'form-control form-control-sm';
  lsInput.style.width = '80px';
  lsInput.style.display = 'inline-block';
  lsInput.style.marginLeft = '5px';
  lsInput.style.marginRight = '5px';
  lsInput.setAttribute('data-property', 'letterSpacing');
  lsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'letterSpacing', this.value);
  });
  
  const lsBtnPlus = document.createElement('button');
  lsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnPlus.innerHTML = '+';
  lsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('mouseup', stopLongPress);
  lsBtnPlus.addEventListener('mouseleave', stopLongPress);
  lsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('touchend', stopLongPress);
  
  lsDiv.appendChild(lsBtnMinus);
  lsDiv.appendChild(lsInput);
  lsDiv.appendChild(lsBtnPlus);
  detailsDiv.appendChild(lsDiv);
  }

  // 郵便番号間隔（postal_codeタイプのフィールド）
  if (!isShapeOnly && selectedField.postalCodeGap !== undefined) {
  const pgDiv = document.createElement('div');
  pgDiv.className = 'coordinate-input';
  pgDiv.innerHTML = `<label>郵便番号間隔:</label>`;
  
  const pgBtnMinus = document.createElement('button');
  pgBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  pgBtnMinus.innerHTML = '−';
  pgBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'postalCodeGap', -0.5));
  pgBtnMinus.addEventListener('mouseup', stopLongPress);
  pgBtnMinus.addEventListener('mouseleave', stopLongPress);
  pgBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'postalCodeGap', -0.5));
  pgBtnMinus.addEventListener('touchend', stopLongPress);
  
  const pgInput = document.createElement('input');
  pgInput.type = 'number';
  pgInput.step = '0.5';
  pgInput.value = selectedField.postalCodeGap || 2;
  pgInput.className = 'form-control form-control-sm';
  pgInput.style.width = '80px';
  pgInput.style.display = 'inline-block';
  pgInput.style.marginLeft = '5px';
  pgInput.style.marginRight = '5px';
  pgInput.setAttribute('data-property', 'postalCodeGap');
  pgInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'postalCodeGap', this.value);
  });
  
  const pgBtnPlus = document.createElement('button');
  pgBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  pgBtnPlus.innerHTML = '+';
  pgBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'postalCodeGap', 0.5));
  pgBtnPlus.addEventListener('mouseup', stopLongPress);
  pgBtnPlus.addEventListener('mouseleave', stopLongPress);
  pgBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'postalCodeGap', 0.5));
  pgBtnPlus.addEventListener('touchend', stopLongPress);
  
  pgDiv.appendChild(pgBtnMinus);
  pgDiv.appendChild(pgInput);
  pgDiv.appendChild(pgBtnPlus);
  detailsDiv.appendChild(pgDiv);
  }

  // テキスト配置（サークルフィールドでは非表示）
  if (!isShapeOnly && selectedField.textAlign !== undefined) {
  const taDiv = document.createElement('div');
  taDiv.className = 'coordinate-input';
  taDiv.innerHTML = `<label>テキスト配置:</label>`;
  
  const taBtnGroup = document.createElement('div');
  taBtnGroup.className = 'btn-group btn-group-sm d-flex';
  taBtnGroup.setAttribute('role', 'group');
  taBtnGroup.style.marginLeft = '10px';
  
  const taLeft = document.createElement('button');
  taLeft.type = 'button';
  taLeft.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'left' ? 'active' : ''}`;
  taLeft.innerHTML = '左';
  taLeft.title = '左揃え';
  taLeft.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'left'));
  
  const taCenter = document.createElement('button');
  taCenter.type = 'button';
  taCenter.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'center' || !selectedField.textAlign ? 'active' : ''}`;
  taCenter.innerHTML = '中央';
  taCenter.title = '中央揃え';
  taCenter.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'center'));
  
  const taRight = document.createElement('button');
  taRight.type = 'button';
  taRight.className = `btn btn-outline-secondary flex-fill ${selectedField.textAlign === 'right' ? 'active' : ''}`;
  taRight.innerHTML = '右';
  taRight.title = '右揃え';
  taRight.addEventListener('click', () => updateCoordinate(selectedKey, 'textAlign', 'right'));
  
  taBtnGroup.appendChild(taLeft);
  taBtnGroup.appendChild(taCenter);
  taBtnGroup.appendChild(taRight);
  taDiv.appendChild(taBtnGroup);
  detailsDiv.appendChild(taDiv);
  }

  // 折り返し幅（複数行テキストの場合）
  if (!isShapeOnly && selectedField.width !== undefined) {
    const wDiv = document.createElement('div');
    wDiv.className = 'coordinate-input';
    wDiv.innerHTML = `<label>折り返し幅:</label>`;

    const wBtnMinus = document.createElement('button');
    wBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    wBtnMinus.innerHTML = '−';
    wBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'width', -5));
    wBtnMinus.addEventListener('mouseup', stopLongPress);
    wBtnMinus.addEventListener('mouseleave', stopLongPress);
    wBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'width', -5));
    wBtnMinus.addEventListener('touchend', stopLongPress);

    const wInput = document.createElement('input');
    wInput.type = 'number';
    wInput.step = '5';
    wInput.value = selectedField.width || 180;
    wInput.className = 'form-control form-control-sm';
    wInput.style.width = '80px';
    wInput.style.display = 'inline-block';
    wInput.style.marginLeft = '5px';
    wInput.style.marginRight = '5px';
    wInput.setAttribute('data-property', 'width');
    wInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'width', this.value);
    });

    const wBtnPlus = document.createElement('button');
    wBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    wBtnPlus.innerHTML = '+';
    wBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'width', 5));
    wBtnPlus.addEventListener('mouseup', stopLongPress);
    wBtnPlus.addEventListener('mouseleave', stopLongPress);
    wBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'width', 5));
    wBtnPlus.addEventListener('touchend', stopLongPress);

    wDiv.appendChild(wBtnMinus);
    wDiv.appendChild(wInput);
    wDiv.appendChild(wBtnPlus);
    detailsDiv.appendChild(wDiv);
  }

  // 行間（複数行テキストの場合）
  if (!isShapeOnly && selectedField.lineHeight !== undefined) {
    const lhDiv = document.createElement('div');
    lhDiv.className = 'coordinate-input';
    lhDiv.innerHTML = `<label>行間:</label>`;

    const lhBtnMinus = document.createElement('button');
    lhBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    lhBtnMinus.innerHTML = '−';
    lhBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'lineHeight', -0.5));
    lhBtnMinus.addEventListener('mouseup', stopLongPress);
    lhBtnMinus.addEventListener('mouseleave', stopLongPress);
    lhBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'lineHeight', -0.5));
    lhBtnMinus.addEventListener('touchend', stopLongPress);

    const lhInput = document.createElement('input');
    lhInput.type = 'number';
    lhInput.step = '0.5';
    lhInput.value = selectedField.lineHeight || 5;
    lhInput.className = 'form-control form-control-sm';
    lhInput.style.width = '80px';
    lhInput.style.display = 'inline-block';
    lhInput.style.marginLeft = '5px';
    lhInput.style.marginRight = '5px';
    lhInput.setAttribute('data-property', 'lineHeight');
    lhInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'lineHeight', this.value);
    });

    const lhBtnPlus = document.createElement('button');
    lhBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    lhBtnPlus.innerHTML = '+';
    lhBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'lineHeight', 0.5));
    lhBtnPlus.addEventListener('mouseup', stopLongPress);
    lhBtnPlus.addEventListener('mouseleave', stopLongPress);
    lhBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'lineHeight', 0.5));
    lhBtnPlus.addEventListener('touchend', stopLongPress);

    lhDiv.appendChild(lhBtnMinus);
    lhDiv.appendChild(lhInput);
    lhDiv.appendChild(lhBtnPlus);
    detailsDiv.appendChild(lhDiv);
  }

  // 楕円設定
  if (selectedField.ellipseWidth !== undefined) {
    const ewDiv = document.createElement('div');
    ewDiv.className = 'coordinate-input';
    ewDiv.innerHTML = `<label>楕円幅:</label>`;
    
    const ewBtnMinus = document.createElement('button');
    ewBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnMinus.innerHTML = '−';
    ewBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('mouseup', stopLongPress);
    ewBtnMinus.addEventListener('mouseleave', stopLongPress);
    ewBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ewInput = document.createElement('input');
    ewInput.type = 'number';
    ewInput.step = '0.5';
    ewInput.value = selectedField.ellipseWidth || 8;
    ewInput.className = 'form-control form-control-sm';
    ewInput.style.width = '80px';
    ewInput.style.display = 'inline-block';
    ewInput.style.marginLeft = '5px';
    ewInput.style.marginRight = '5px';
    ewInput.setAttribute('data-property', 'ellipseWidth');
    ewInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseWidth', this.value);
    });
    
    const ewBtnPlus = document.createElement('button');
    ewBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnPlus.innerHTML = '+';
    ewBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('mouseup', stopLongPress);
    ewBtnPlus.addEventListener('mouseleave', stopLongPress);
    ewBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('touchend', stopLongPress);
    
    ewDiv.appendChild(ewBtnMinus);
    ewDiv.appendChild(ewInput);
    ewDiv.appendChild(ewBtnPlus);
    detailsDiv.appendChild(ewDiv);
  }

  if (selectedField.ellipseHeight !== undefined) {
    const ehDiv = document.createElement('div');
    ehDiv.className = 'coordinate-input';
    ehDiv.innerHTML = `<label>楕円高さ:</label>`;
    
    const ehBtnMinus = document.createElement('button');
    ehBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnMinus.innerHTML = '−';
    ehBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('mouseup', stopLongPress);
    ehBtnMinus.addEventListener('mouseleave', stopLongPress);
    ehBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ehInput = document.createElement('input');
    ehInput.type = 'number';
    ehInput.step = '0.5';
    ehInput.value = selectedField.ellipseHeight || 5;
    ehInput.className = 'form-control form-control-sm';
    ehInput.style.width = '80px';
    ehInput.style.display = 'inline-block';
    ehInput.style.marginLeft = '5px';
    ehInput.style.marginRight = '5px';
    ehInput.setAttribute('data-property', 'ellipseHeight');
    ehInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseHeight', this.value);
    });
    
    const ehBtnPlus = document.createElement('button');
    ehBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnPlus.innerHTML = '+';
    ehBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('mouseup', stopLongPress);
    ehBtnPlus.addEventListener('mouseleave', stopLongPress);
    ehBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('touchend', stopLongPress);
    
    ehDiv.appendChild(ehBtnMinus);
    ehDiv.appendChild(ehInput);
    ehDiv.appendChild(ehBtnPlus);
    detailsDiv.appendChild(ehDiv);
  }

  // サンプルデータ入力（楕円フィールド以外）
  const isEllipseField = (selectedField.ellipseWidth !== undefined && selectedField.ellipseWidth !== null) || 
                          (selectedField.ellipseHeight !== undefined && selectedField.ellipseHeight !== null);
  if (!isEllipseField) {
    const sampleDataHtml = getSampleDataInput(selectedKey);
    if (sampleDataHtml) {
      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = sampleDataHtml;
      detailsDiv.appendChild(tempDiv.firstElementChild);
    }
  }

  fieldsContainer.appendChild(detailsDiv);

  autoSave();
  autoPreview();
}

// ラジオグループの選択を更新
function updateRadioGroupSelection(groupName, selectedKey) {
  // 座標のisSelectedを更新
  Object.keys(coordinates).forEach(key => {
    const field = coordinates[key];
    if (field.radioGroup === groupName) {
      field.isSelected = (key === selectedKey);
    }
  });

  // サンプルデータを更新（radioGroupフィールドの場合）
  const selectedField = coordinates[selectedKey];
  if (selectedField && selectedField.radioGroup) {
    const mapping = sampleDataFieldMapping[selectedKey];
    if (mapping && mapping.field && mapping.optionLabel) {
      // optionLabelをサンプルデータとして設定
      updateSampleData(mapping.field, mapping.optionLabel);
    }
  }

  // グループ内の設定詳細を表示
  const fieldsContainer = document.getElementById(`radiogroup-fields-${groupName}`);
  if (!fieldsContainer) return;

  fieldsContainer.innerHTML = '';

  if (!selectedField) return;

  const detailsDiv = document.createElement('div');
  detailsDiv.style.borderTop = '1px solid #ddd';
  detailsDiv.style.marginTop = '10px';
  detailsDiv.style.paddingTop = '10px';

  // ellipseXが定義されている場合
  if (selectedField.ellipseX !== undefined) {
    const ellipseXDiv = document.createElement('div');
    ellipseXDiv.className = 'coordinate-input';
    ellipseXDiv.innerHTML = `<label>X座標:</label>`;

    const ellipseXBtnLeft = document.createElement('button');
    ellipseXBtnLeft.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ellipseXBtnLeft.innerHTML = '←';
    ellipseXBtnLeft.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseX', -0.5));
    ellipseXBtnLeft.addEventListener('mouseup', stopLongPress);
    ellipseXBtnLeft.addEventListener('mouseleave', stopLongPress);
    ellipseXBtnLeft.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseX', -0.5));
    ellipseXBtnLeft.addEventListener('touchend', stopLongPress);

    const ellipseXInput = document.createElement('input');
    ellipseXInput.type = 'number';
    ellipseXInput.step = '0.5';
    ellipseXInput.value = selectedField.ellipseX;
    ellipseXInput.className = 'form-control form-control-sm';
    ellipseXInput.style.width = '80px';
    ellipseXInput.style.display = 'inline-block';
    ellipseXInput.style.marginLeft = '5px';
    ellipseXInput.style.marginRight = '5px';
    ellipseXInput.setAttribute('data-property', 'ellipseX');
    ellipseXInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseX', this.value);
    });

    const ellipseXBtnRight = document.createElement('button');
    ellipseXBtnRight.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ellipseXBtnRight.innerHTML = '→';
    ellipseXBtnRight.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseX', 0.5));
    ellipseXBtnRight.addEventListener('mouseup', stopLongPress);
    ellipseXBtnRight.addEventListener('mouseleave', stopLongPress);
    ellipseXBtnRight.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseX', 0.5));
    ellipseXBtnRight.addEventListener('touchend', stopLongPress);

    ellipseXDiv.appendChild(ellipseXBtnLeft);
    ellipseXDiv.appendChild(ellipseXInput);
    ellipseXDiv.appendChild(ellipseXBtnRight);
    detailsDiv.appendChild(ellipseXDiv);
  }

  // ellipseYが定義されている場合
  if (selectedField.ellipseY !== undefined) {
    const ellipseYDiv = document.createElement('div');
    ellipseYDiv.className = 'coordinate-input';
    ellipseYDiv.innerHTML = `<label>Y座標:</label>`;

    const ellipseYBtnUp = document.createElement('button');
    ellipseYBtnUp.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ellipseYBtnUp.innerHTML = '↑';
    ellipseYBtnUp.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseY', -0.5));
    ellipseYBtnUp.addEventListener('mouseup', stopLongPress);
    ellipseYBtnUp.addEventListener('mouseleave', stopLongPress);
    ellipseYBtnUp.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseY', -0.5));
    ellipseYBtnUp.addEventListener('touchend', stopLongPress);

    const ellipseYInput = document.createElement('input');
    ellipseYInput.type = 'number';
    ellipseYInput.step = '0.5';
    ellipseYInput.value = selectedField.ellipseY;
    ellipseYInput.className = 'form-control form-control-sm';
    ellipseYInput.style.width = '80px';
    ellipseYInput.style.display = 'inline-block';
    ellipseYInput.style.marginLeft = '5px';
    ellipseYInput.style.marginRight = '5px';
    ellipseYInput.setAttribute('data-property', 'ellipseY');
    ellipseYInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseY', this.value);
    });

    const ellipseYBtnDown = document.createElement('button');
    ellipseYBtnDown.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ellipseYBtnDown.innerHTML = '↓';
    ellipseYBtnDown.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseY', 0.5));
    ellipseYBtnDown.addEventListener('mouseup', stopLongPress);
    ellipseYBtnDown.addEventListener('mouseleave', stopLongPress);
    ellipseYBtnDown.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseY', 0.5));
    ellipseYBtnDown.addEventListener('touchend', stopLongPress);

    ellipseYDiv.appendChild(ellipseYBtnUp);
    ellipseYDiv.appendChild(ellipseYInput);
    ellipseYDiv.appendChild(ellipseYBtnDown);
    detailsDiv.appendChild(ellipseYDiv);
  }

  // X座標（ellipseXがある場合は非表示）
  if (selectedField.x !== undefined && selectedField.ellipseX === undefined) {
    const xDiv = document.createElement('div');
    xDiv.className = 'coordinate-input';
    xDiv.innerHTML = `<label>X座標:</label>`;
    const xBtnLeft = document.createElement('button');
    xBtnLeft.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    xBtnLeft.innerHTML = '←';
    xBtnLeft.addEventListener('mousedown', function() {
      startLongPress(selectedKey, 'x', -0.5);
    });
    xBtnLeft.addEventListener('mouseup', stopLongPress);
    xBtnLeft.addEventListener('mouseleave', stopLongPress);
    xBtnLeft.addEventListener('touchstart', function() {
      startLongPress(selectedKey, 'x', -0.5);
    });
    xBtnLeft.addEventListener('touchend', stopLongPress);

    const xInput = document.createElement('input');
    xInput.type = 'number';
    xInput.step = '0.5';
    xInput.value = selectedField.x;
    xInput.className = 'form-control form-control-sm';
    xInput.style.width = '80px';
    xInput.style.display = 'inline-block';
    xInput.style.marginLeft = '5px';
    xInput.style.marginRight = '5px';
    xInput.setAttribute('data-property', 'x');
    xInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'x', this.value);
    });

    const xBtnRight = document.createElement('button');
    xBtnRight.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    xBtnRight.innerHTML = '→';
    xBtnRight.addEventListener('mousedown', () => startLongPress(selectedKey, 'x', 0.5));
    xBtnRight.addEventListener('mouseup', stopLongPress);
    xBtnRight.addEventListener('mouseleave', stopLongPress);
    xBtnRight.addEventListener('touchstart', () => startLongPress(selectedKey, 'x', 0.5));
    xBtnRight.addEventListener('touchend', stopLongPress);

    xDiv.appendChild(xBtnLeft);
    xDiv.appendChild(xInput);
    xDiv.appendChild(xBtnRight);
    detailsDiv.appendChild(xDiv);
  }

  // Y座標（ellipseYがある場合は非表示）
  if (selectedField.y !== undefined && selectedField.ellipseY === undefined) {
    const yDiv = document.createElement('div');
    yDiv.className = 'coordinate-input';
    yDiv.innerHTML = `<label>Y座標:</label>`;
  
    const yBtnUp = document.createElement('button');
    yBtnUp.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    yBtnUp.innerHTML = '↑';
    yBtnUp.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', -0.5));
    yBtnUp.addEventListener('mouseup', stopLongPress);
    yBtnUp.addEventListener('mouseleave', stopLongPress);
    yBtnUp.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', -0.5));
    yBtnUp.addEventListener('touchend', stopLongPress);
  
    const yInput = document.createElement('input');
    yInput.type = 'number';
    yInput.step = '0.5';
    yInput.value = selectedField.y;
    yInput.className = 'form-control form-control-sm';
    yInput.style.width = '80px';
    yInput.style.display = 'inline-block';
    yInput.style.marginLeft = '5px';
    yInput.style.marginRight = '5px';
    yInput.setAttribute('data-property', 'y');
    yInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'y', this.value);
    });
  
    const yBtnDown = document.createElement('button');
    yBtnDown.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    yBtnDown.innerHTML = '↓';
    yBtnDown.addEventListener('mousedown', () => startLongPress(selectedKey, 'y', 0.5));
    yBtnDown.addEventListener('mouseup', stopLongPress);
    yBtnDown.addEventListener('mouseleave', stopLongPress);
    yBtnDown.addEventListener('touchstart', () => startLongPress(selectedKey, 'y', 0.5));
    yBtnDown.addEventListener('touchend', stopLongPress);
  
    yDiv.appendChild(yBtnUp);
    yDiv.appendChild(yInput);
    yDiv.appendChild(yBtnDown);
    detailsDiv.appendChild(yDiv);
  }

  // サークルフィールドかどうかを判定（ellipseX/ellipseYがあればサークル）
  const isShapeOnly = selectedField.ellipseX !== undefined || selectedField.ellipseY !== undefined || selectedField.circleRadius !== undefined;

  // フォントサイズ（サークルフィールドでは非表示）
  if (!isShapeOnly && selectedField.fontSize !== undefined) {
    const fsDiv = document.createElement('div');
    fsDiv.className = 'coordinate-input';
    fsDiv.innerHTML = `<label>フォントサイズ:</label>`;
  
  const fsBtnMinus = document.createElement('button');
  fsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnMinus.innerHTML = '−';
  fsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('mouseup', stopLongPress);
  fsBtnMinus.addEventListener('mouseleave', stopLongPress);
  fsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', -0.5));
  fsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const fsInput = document.createElement('input');
  fsInput.type = 'number';
  fsInput.step = '0.5';
  fsInput.value = selectedField.fontSize;
  fsInput.className = 'form-control form-control-sm';
  fsInput.style.width = '80px';
  fsInput.style.display = 'inline-block';
  fsInput.style.marginLeft = '5px';
  fsInput.setAttribute('data-property', 'fontSize');
  fsInput.style.marginRight = '5px';
  fsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'fontSize', this.value);
  });
  
  const fsBtnPlus = document.createElement('button');
  fsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  fsBtnPlus.innerHTML = '+';
  fsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('mouseup', stopLongPress);
  fsBtnPlus.addEventListener('mouseleave', stopLongPress);
  fsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'fontSize', 0.5));
  fsBtnPlus.addEventListener('touchend', stopLongPress);
  
    fsDiv.appendChild(fsBtnMinus);
    fsDiv.appendChild(fsInput);
    fsDiv.appendChild(fsBtnPlus);
    detailsDiv.appendChild(fsDiv);
  }

  // 文字間隔（サークルフィールドでは非表示）
  if (!isShapeOnly && selectedField.letterSpacing !== undefined) {
    const lsDiv = document.createElement('div');
    lsDiv.className = 'coordinate-input';
    lsDiv.innerHTML = `<label>文字間隔:</label>`;
  
  const lsBtnMinus = document.createElement('button');
  lsBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnMinus.innerHTML = '−';
  lsBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('mouseup', stopLongPress);
  lsBtnMinus.addEventListener('mouseleave', stopLongPress);
  lsBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', -0.1));
  lsBtnMinus.addEventListener('touchend', stopLongPress);
  
  const lsInput = document.createElement('input');
  lsInput.type = 'number';
  lsInput.step = '0.1';
  lsInput.value = selectedField.letterSpacing || 0;
  lsInput.className = 'form-control form-control-sm';
  lsInput.style.width = '80px';
  lsInput.style.display = 'inline-block';
  lsInput.style.marginLeft = '5px';
  lsInput.setAttribute('data-property', 'letterSpacing');
  lsInput.style.marginRight = '5px';
  lsInput.addEventListener('change', function() {
    updateCoordinate(selectedKey, 'letterSpacing', this.value);
  });
  
  const lsBtnPlus = document.createElement('button');
  lsBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
  lsBtnPlus.innerHTML = '+';
  lsBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('mouseup', stopLongPress);
  lsBtnPlus.addEventListener('mouseleave', stopLongPress);
  lsBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'letterSpacing', 0.1));
  lsBtnPlus.addEventListener('touchend', stopLongPress);
  
    lsDiv.appendChild(lsBtnMinus);
    lsDiv.appendChild(lsInput);
    lsDiv.appendChild(lsBtnPlus);
    detailsDiv.appendChild(lsDiv);
  }

  // 円半径（楕円を使用している場合、またはcalendarタイプの場合は表示しない）
  if (selectedField.circleRadius !== undefined && selectedField.ellipseWidth === undefined && selectedField.ellipseHeight === undefined && selectedField.type !== 'calendar') {
    const crDiv = document.createElement('div');
    crDiv.className = 'coordinate-input';
    crDiv.innerHTML = `<label>○半径:</label>`;
    
    const crBtnMinus = document.createElement('button');
    crBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnMinus.innerHTML = '−';
    crBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('mouseup', stopLongPress);
    crBtnMinus.addEventListener('mouseleave', stopLongPress);
    crBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('touchend', stopLongPress);
    
    const crInput = document.createElement('input');
    crInput.type = 'number';
    crInput.step = '0.1';
    crInput.value = selectedField.circleRadius || 1.2;
    crInput.className = 'form-control form-control-sm';
    crInput.style.width = '80px';
    crInput.style.display = 'inline-block';
    crInput.setAttribute('data-property', 'circleRadius');
    crInput.style.marginLeft = '5px';
    crInput.style.marginRight = '5px';
    crInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'circleRadius', this.value);
    });
    
    const crBtnPlus = document.createElement('button');
    crBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnPlus.innerHTML = '+';
    crBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('mouseup', stopLongPress);
    crBtnPlus.addEventListener('mouseleave', stopLongPress);
    crBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('touchend', stopLongPress);
    
    crDiv.appendChild(crBtnMinus);
    crDiv.appendChild(crInput);
    crDiv.appendChild(crBtnPlus);
    detailsDiv.appendChild(crDiv);
  }

  // 楕円幅
  if (selectedField.ellipseWidth !== undefined) {
    const ewDiv = document.createElement('div');
    ewDiv.className = 'coordinate-input';
    ewDiv.innerHTML = `<label>楕円幅:</label>`;
    
    const ewBtnMinus = document.createElement('button');
    ewBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnMinus.innerHTML = '−';
    ewBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('mouseup', stopLongPress);
    ewBtnMinus.addEventListener('mouseleave', stopLongPress);
    ewBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', -0.5));
    ewBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ewInput = document.createElement('input');
    ewInput.type = 'number';
    ewInput.step = '0.5';
    ewInput.value = selectedField.ellipseWidth || 8;
    ewInput.className = 'form-control form-control-sm';
    ewInput.style.width = '80px';
    ewInput.style.display = 'inline-block';
    ewInput.setAttribute('data-property', 'ellipseWidth');
    ewInput.style.marginLeft = '5px';
    ewInput.style.marginRight = '5px';
    ewInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseWidth', this.value);
    });
    
    const ewBtnPlus = document.createElement('button');
    ewBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ewBtnPlus.innerHTML = '+';
    ewBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('mouseup', stopLongPress);
    ewBtnPlus.addEventListener('mouseleave', stopLongPress);
    ewBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseWidth', 0.5));
    ewBtnPlus.addEventListener('touchend', stopLongPress);
    
    ewDiv.appendChild(ewBtnMinus);
    ewDiv.appendChild(ewInput);
    ewDiv.appendChild(ewBtnPlus);
    detailsDiv.appendChild(ewDiv);
  }

  // 楕円高さ
  if (selectedField.ellipseHeight !== undefined) {
    const ehDiv = document.createElement('div');
    ehDiv.className = 'coordinate-input';
    ehDiv.innerHTML = `<label>楕円高さ:</label>`;
    
    const ehBtnMinus = document.createElement('button');
    ehBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnMinus.innerHTML = '−';
    ehBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('mouseup', stopLongPress);
    ehBtnMinus.addEventListener('mouseleave', stopLongPress);
    ehBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', -0.5));
    ehBtnMinus.addEventListener('touchend', stopLongPress);
    
    const ehInput = document.createElement('input');
    ehInput.type = 'number';
    ehInput.step = '0.5';
    ehInput.value = selectedField.ellipseHeight || 5;
    ehInput.className = 'form-control form-control-sm';
    ehInput.style.width = '80px';
    ehInput.style.display = 'inline-block';
    ehInput.setAttribute('data-property', 'ellipseHeight');
    ehInput.style.marginLeft = '5px';
    ehInput.style.marginRight = '5px';
    ehInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'ellipseHeight', this.value);
    });
    
    const ehBtnPlus = document.createElement('button');
    ehBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    ehBtnPlus.innerHTML = '+';
    ehBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('mouseup', stopLongPress);
    ehBtnPlus.addEventListener('mouseleave', stopLongPress);
    ehBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'ellipseHeight', 0.5));
    ehBtnPlus.addEventListener('touchend', stopLongPress);
    
    ehDiv.appendChild(ehBtnMinus);
    ehDiv.appendChild(ehInput);
    ehDiv.appendChild(ehBtnPlus);
    detailsDiv.appendChild(ehDiv);
  }

  // 円のサイズ（calendarタイプ用）
  if (selectedField.type === 'calendar' && selectedField.circleRadius !== undefined) {
    const crDiv = document.createElement('div');
    crDiv.className = 'coordinate-input';
    crDiv.innerHTML = `<label>円のサイズ:</label>`;
    
    const crBtnMinus = document.createElement('button');
    crBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnMinus.innerHTML = '−';
    crBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('mouseup', stopLongPress);
    crBtnMinus.addEventListener('mouseleave', stopLongPress);
    crBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', -0.1));
    crBtnMinus.addEventListener('touchend', stopLongPress);
    
    const crInput = document.createElement('input');
    crInput.type = 'number';
    crInput.step = '0.1';
    crInput.value = selectedField.circleRadius || 1.2;
    crInput.className = 'form-control form-control-sm';
    crInput.style.width = '80px';
    crInput.style.display = 'inline-block';
    crInput.setAttribute('data-property', 'circleRadius');
    crInput.style.marginLeft = '5px';
    crInput.style.marginRight = '5px';
    crInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'circleRadius', this.value);
    });
    
    const crBtnPlus = document.createElement('button');
    crBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    crBtnPlus.innerHTML = '+';
    crBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('mouseup', stopLongPress);
    crBtnPlus.addEventListener('mouseleave', stopLongPress);
    crBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleRadius', 0.1));
    crBtnPlus.addEventListener('touchend', stopLongPress);
    
    crDiv.appendChild(crBtnMinus);
    crDiv.appendChild(crInput);
    crDiv.appendChild(crBtnPlus);
    detailsDiv.appendChild(crDiv);
  }

  // 二重丸の内円のサイズ（calendarタイプ用）
  if (selectedField.type === 'calendar' && selectedField.doubleCircleInnerRadius !== undefined) {
    const dcirDiv = document.createElement('div');
    dcirDiv.className = 'coordinate-input';
    dcirDiv.innerHTML = `<label>二重丸の内円のサイズ:</label>`;
    
    const dcirBtnMinus = document.createElement('button');
    dcirBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    dcirBtnMinus.innerHTML = '−';
    dcirBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'doubleCircleInnerRadius', -0.1));
    dcirBtnMinus.addEventListener('mouseup', stopLongPress);
    dcirBtnMinus.addEventListener('mouseleave', stopLongPress);
    dcirBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'doubleCircleInnerRadius', -0.1));
    dcirBtnMinus.addEventListener('touchend', stopLongPress);
    
    const dcirInput = document.createElement('input');
    dcirInput.type = 'number';
    dcirInput.step = '0.1';
    dcirInput.value = selectedField.doubleCircleInnerRadius || 0.4;
    dcirInput.className = 'form-control form-control-sm';
    dcirInput.style.width = '80px';
    dcirInput.style.display = 'inline-block';
    dcirInput.setAttribute('data-property', 'doubleCircleInnerRadius');
    dcirInput.style.marginLeft = '5px';
    dcirInput.style.marginRight = '5px';
    dcirInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'doubleCircleInnerRadius', this.value);
    });
    
    const dcirBtnPlus = document.createElement('button');
    dcirBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    dcirBtnPlus.innerHTML = '+';
    dcirBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'doubleCircleInnerRadius', 0.1));
    dcirBtnPlus.addEventListener('mouseup', stopLongPress);
    dcirBtnPlus.addEventListener('mouseleave', stopLongPress);
    dcirBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'doubleCircleInnerRadius', 0.1));
    dcirBtnPlus.addEventListener('touchend', stopLongPress);
    
    dcirDiv.appendChild(dcirBtnMinus);
    dcirDiv.appendChild(dcirInput);
    dcirDiv.appendChild(dcirBtnPlus);
    detailsDiv.appendChild(dcirDiv);
  }

  // 円の間隔（calendarタイプ用）
  if (selectedField.type === 'calendar' && selectedField.circleSpacing !== undefined) {
    const cwDiv = document.createElement('div');
    cwDiv.className = 'coordinate-input';
    cwDiv.innerHTML = `<label>円の間隔:</label>`;
    
    const cwBtnMinus = document.createElement('button');
    cwBtnMinus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    cwBtnMinus.innerHTML = '−';
    cwBtnMinus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleSpacing', -0.1));
    cwBtnMinus.addEventListener('mouseup', stopLongPress);
    cwBtnMinus.addEventListener('mouseleave', stopLongPress);
    cwBtnMinus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleSpacing', -0.1));
    cwBtnMinus.addEventListener('touchend', stopLongPress);
    
    const cwInput = document.createElement('input');
    cwInput.type = 'number';
    cwInput.step = '0.1';
    cwInput.value = selectedField.circleSpacing || 6.45;
    cwInput.className = 'form-control form-control-sm';
    cwInput.style.width = '80px';
    cwInput.style.display = 'inline-block';
    cwInput.setAttribute('data-property', 'circleSpacing');
    cwInput.style.marginLeft = '5px';
    cwInput.style.marginRight = '5px';
    cwInput.addEventListener('change', function() {
      updateCoordinate(selectedKey, 'circleSpacing', this.value);
    });
    
    const cwBtnPlus = document.createElement('button');
    cwBtnPlus.className = 'btn btn-sm btn-outline-secondary btn-adjust';
    cwBtnPlus.innerHTML = '+';
    cwBtnPlus.addEventListener('mousedown', () => startLongPress(selectedKey, 'circleSpacing', 0.1));
    cwBtnPlus.addEventListener('mouseup', stopLongPress);
    cwBtnPlus.addEventListener('mouseleave', stopLongPress);
    cwBtnPlus.addEventListener('touchstart', () => startLongPress(selectedKey, 'circleSpacing', 0.1));
    cwBtnPlus.addEventListener('touchend', stopLongPress);
    
    cwDiv.appendChild(cwBtnMinus);
    cwDiv.appendChild(cwInput);
    cwDiv.appendChild(cwBtnPlus);
    detailsDiv.appendChild(cwDiv);
  }

  fieldsContainer.appendChild(detailsDiv);

  autoSave();
  autoPreview();
}


// サンプルデータ入力欄を生成
function getSampleDataInput(key) {
  const showSampleData = document.getElementById('show-sample-data')?.checked;
  if (!showSampleData) return '';

  const mapping = sampleDataFieldMapping[key];
  if (!mapping) return '';

  let inputHtml = '';

  // combine属性がある場合は複数のフィールドの入力欄を作成
  if (mapping.combine && Array.isArray(mapping.combine)) {
    inputHtml = '<div>';
    mapping.combine.forEach(fieldName => {
      const currentValue = customSampleData[fieldName] || '';
      let fieldLabel = '';
      let labelPrefix = '';

      // 氏名系の判定
      if (fieldName.includes('last') || fieldName.includes('first')) {
        fieldLabel = fieldName.includes('last') ? '姓' : '名';
        const isKana = fieldName.includes('kana');
        labelPrefix = isKana ? '氏名カナ' : '氏名';
      }
      // 記号番号系の判定
      else if (fieldName.includes('kigou')) {
        fieldLabel = '記号';
        labelPrefix = '記号番号';
      }
      else if (fieldName.includes('bangou')) {
        fieldLabel = '番号';
        labelPrefix = '記号番号';
      }
      else {
        fieldLabel = fieldName;
        labelPrefix = mapping.label || '';
      }

      inputHtml += `
        <div class="coordinate-input">
          <label>サンプル${labelPrefix}（${fieldLabel}）:</label>
          <input type="text"
                 value="${currentValue}"
                 onchange="updateSampleData('${fieldName}', this.value)"
                 class="form-control form-control-sm">
        </div>
      `;
    });
    inputHtml += '</div>';
    return inputHtml;
  }

  const currentValue = customSampleData[mapping.field] || '';

  if (mapping.type === 'text' || mapping.type === 'number') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="${mapping.type}"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm">
      </div>
    `;
  } else if (mapping.type === 'date') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="text"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm"
               placeholder="例: 2024-01-01">
      </div>
    `;
  } else if (mapping.type === 'select') {
    let options = '';

    // masterKeyからmasterDataを参照してオプション生成
    if (mapping.masterKey) {
      const masterKey = mapping.masterKey;
      const valueField = mapping.valueField;
      const masterOptions = masterData[masterKey] || [];

      options = masterOptions.map(opt => {
        const optValue = opt[valueField] || opt;
        return `<option value="${optValue}" ${currentValue === optValue ? 'selected' : ''}>${optValue}</option>`;
      }).join('');
    }
    // optionsから直接オプション生成
    else if (mapping.options) {
      options = mapping.options.map((opt, index) => {
        const label = mapping.optionLabels ? mapping.optionLabels[index] : opt;
        return `<option value="${opt}" ${currentValue === opt ? 'selected' : ''}>${label}</option>`;
      }).join('');
    }

    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <select onchange="updateSampleData('${mapping.field}', this.value)"
                class="form-control form-control-sm">
          ${options}
        </select>
      </div>
    `;
  } else if (mapping.type === 'postal_code') {
    inputHtml = `
      <div class="coordinate-input">
        <label>サンプル${mapping.label}:</label>
        <input type="text"
               value="${currentValue}"
               onchange="updateSampleData('${mapping.field}', this.value)"
               class="form-control form-control-sm"
               placeholder="例: 1600022">
      </div>
    `;
  }
  // 楕円フィールドでtypeが定義されていない場合（例: therapy_content_electric_needle）
  // チェックボックスを生成してサークルの描画を切り替え
  else if (mapping && !mapping.type && mapping.ellipseWidth !== undefined) {
    const isChecked = customSampleData[key] === true;
    inputHtml = `
      <div class="coordinate-input">
        <div class="form-check">
          <input class="form-check-input" type="checkbox"
                 id="sample_${key}"
                 ${isChecked ? 'checked' : ''}
                 onchange="updateSampleData('${key}', this.checked)">
          <label class="form-check-label" for="sample_${key}">
            有効化
          </label>
        </div>
      </div>
    `;
  }
  // 楕円フィールドで、mappingが存在しない場合
  // coordinatesから直接フィールド情報を取得してチェックボックスを生成
  else if (!mapping && coordinates[key] && coordinates[key].ellipseWidth !== undefined) {
    const field = coordinates[key];
    const isChecked = customSampleData[key] === true;
    inputHtml = `
      <div class="coordinate-input">
        <div class="form-check">
          <input class="form-check-input" type="checkbox"
                 id="sample_${key}"
                 ${isChecked ? 'checked' : ''}
                 onchange="updateSampleData('${key}', this.checked)">
          <label class="form-check-label" for="sample_${key}">
            有効化
          </label>
        </div>
      </div>
    `;
  }

  return inputHtml;
}

// フィールドの折りたたみ切り替え
function toggleField(key) {
  const controls = document.getElementById('controls-' + key);
  const toggle = document.getElementById('toggle-' + key);

  if (controls.classList.contains('show')) {
    // 格納
    controls.style.maxHeight = controls.scrollHeight + 'px';
    setTimeout(() => {
      controls.style.maxHeight = '0';
    }, 10);
    controls.classList.remove('show');
    toggle.textContent = '▶';
  } else {
    // 展開
    controls.classList.add('show');
    controls.style.maxHeight = controls.scrollHeight + 'px';
    toggle.textContent = '▼';

    // アニメーション完了後にmax-heightをnoneに設定（リサイズ対応）
    controls.addEventListener('transitionend', function handler() {
      if (controls.classList.contains('show')) {
        controls.style.maxHeight = 'none';
      }
      controls.removeEventListener('transitionend', handler);
    });
  }
}


// 施術料金データを表示
function displayTreatmentFees() {
  const container = document.getElementById('treatment-fees-display');
  if (!container) return;

  if (!treatmentFees) {
    container.innerHTML = '<div style="color: #999;">施術料金データなし</div>';
    return;
  }

  // 料金項目のラベルマッピング
  const feeLabels = {
    // 鍼灸関連
    hari_first: 'はり（初検）',
    hari_normal: 'はり（2回目以降）',
    hari_and_elec_needle_first: 'はり+電気鍼（初検）',
    hari_and_elec_needle_normal: 'はり+電気鍼（2回目以降）',
    kyu_first: 'きゅう（初検）',
    kyu_normal: 'きゅう（2回目以降）',
    kyu_and_elec_moxa_heater_first: 'きゅう+電気温灸器（初検）',
    kyu_and_elec_moxa_heater_normal: 'きゅう+電気温灸器（2回目以降）',
    hari_and_kyu_first: 'はり+きゅう（初検）',
    hari_and_kyu_normal: 'はり+きゅう（2回目以降）',
    hari_and_kyu_elec_first: 'はり+きゅう+電気（初検）',
    hari_and_kyu_elec_normal: 'はり+きゅう+電気（2回目以降）',
    housecall_max_2km_first: '往療（2km以内・初検）',
    housecall_max_2km_normal: '往療（2km以内・2回目以降）',
    housecall_additional_max_4km_first: '往療加算（4km以内・初検）',
    housecall_additional_max_4km_normal: '往療加算（4km以内・2回目以降）',

    // マッサージ関連
    massage_trunk_first: 'マッサージ 体幹（初検）',
    massage_trunk_normal: 'マッサージ 体幹（2回目以降）',
    massage_upper_limb_r_first: 'マッサージ 上肢右（初検）',
    massage_upper_limb_r_normal: 'マッサージ 上肢右（2回目以降）',
    massage_upper_limb_l_first: 'マッサージ 上肢左（初検）',
    massage_upper_limb_l_normal: 'マッサージ 上肢左（2回目以降）',
    massage_lower_limb_r_first: 'マッサージ 下肢右（初検）',
    massage_lower_limb_r_normal: 'マッサージ 下肢右（2回目以降）',
    massage_lower_limb_l_first: 'マッサージ 下肢左（初検）',
    massage_lower_limb_l_normal: 'マッサージ 下肢左（2回目以降）',
    manual_correction_first: '変形徒手矯正術（初検）',
    manual_correction_normal: '変形徒手矯正術（2回目以降）',
    fomentation_first: '温罨法（初検）',
    fomentation_normal: '温罨法（2回目以降）',
    fomentation_and_elec_ray_first: '温罨法+電気光線（初検）',
    fomentation_and_elec_ray_normal: '温罨法+電気光線（2回目以降）'
  };

  // PDFタイプに応じて表示する項目をフィルタリング
  let html = '<div style="max-height: 300px; overflow-y: auto;">';

  // 適用期間を表示
  if (treatmentFees.period_start && treatmentFees.period_end) {
    html += `<div style="margin-bottom: 8px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 4px;">`;
    html += `適用期間: ${treatmentFees.period_start} 〜 ${treatmentFees.period_end}`;
    html += `</div>`;
  }

  Object.keys(feeLabels).forEach(key => {
    // PDFタイプに応じてフィルタリング
    if (currentPdfType === 'therapy_benefit_acupuncture') {
      // 鍼灸用PDFでは鍼灸関連の料金のみ表示
      if (!key.startsWith('hari_') && !key.startsWith('kyu_') && !key.startsWith('housecall_')) {
        return;
      }
    } else if (currentPdfType === 'therapy_benefit_massage') {
      // マッサージ用PDFではマッサージ関連の料金のみ表示
      if (!key.startsWith('massage_') && !key.startsWith('manual_') && !key.startsWith('fomentation_')) {
        return;
      }
    }

    const value = treatmentFees[key];
    if (value !== null && value !== undefined) {
      html += `<div style="margin-bottom: 4px; display: flex; justify-content: space-between;">`;
      html += `<span style="flex: 1; font-size: 0.75em;">${feeLabels[key]}:</span>`;
      html += `<span style="font-weight: bold; min-width: 60px; text-align: right;">${Number(value).toLocaleString()}円</span>`;
      html += `</div>`;
    }
  });

  html += '</div>';
  container.innerHTML = html;
}
