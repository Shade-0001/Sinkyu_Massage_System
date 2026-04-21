//-- resources/js/app.js --//

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


/*┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓*/
/*┃  ユーティリティ                              ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/

// clone要素からトランジションなし･静的状態のbackground-colorを取得
function getStaticBgColor(el) {
  const clone = el.cloneNode(false);
  clone.style.cssText = 'position:absolute;visibility:hidden;transition:none!important;';
  document.body.appendChild(clone);
  const bg = getComputedStyle(clone).backgroundColor;
  document.body.removeChild(clone);
  return bg;
}

// 任意のbackground-color文字列をページ背景と合成して不透明rgb文字列を返す
function blendBgWithPage(bgColor, el) {
  let pageBg = 'rgb(255, 255, 255)';
  let node = el.parentElement;
  while (node && node !== document.documentElement) {
    const bg = getComputedStyle(node).backgroundColor;
    if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') { pageBg = bg; break; }
    node = node.parentElement;
  }

  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = pageBg;
  ctx.fillRect(0, 0, 1, 1);
  ctx.fillStyle = bgColor;
  ctx.fillRect(0, 0, 1, 1);
  const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

  const style = getComputedStyle(el);
  const mode = style.getPropertyValue('--btn-color-mode').trim() || 'hsl';
  if (mode === 'oklch') {
    const lOffset = parseFloat(style.getPropertyValue('--btn-lighten-l').trim()) || 0;
    const cFactor = parseFloat(style.getPropertyValue('--btn-lighten-c').trim()) || 1;
    return rgbLightenOklch(r, g, b, lOffset, cFactor);
  }
  const factor = parseFloat(style.getPropertyValue('--btn-lighten-factor').trim()) || 1.0;
  return rgbLighten(r, g, b, factor);
}

// RGBを知覚均等色空間に変換してL/Cを調整し、rgb文字列で返す
function rgbLightenOklch(r, g, b, lOffset, cFactor) {
  // sRGB → 線形化
  const toLinear = v => {
    v /= 255;
    return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
  };
  const lr = toLinear(r), lg = toLinear(g), lb = toLinear(b);

  // 線形sRGB → 知覚均等Lab
  const l_ = Math.cbrt(0.4122214708 * lr + 0.5363325363 * lg + 0.0514459929 * lb);
  const m_ = Math.cbrt(0.2119034982 * lr + 0.6806995451 * lg + 0.1073969566 * lb);
  const s_ = Math.cbrt(0.0883024619 * lr + 0.2817188376 * lg + 0.6299787005 * lb);
  const L  = 0.2104542553 * l_ + 0.7936177850 * m_ - 0.0040720468 * s_;
  const A  = 1.9779984951 * l_ - 2.4285922050 * m_ + 0.4505937099 * s_;
  const B  = 0.0259040371 * l_ + 0.7827717662 * m_ - 0.8086757660 * s_;

  // 極座標変換（彩度・色相）
  const C = Math.sqrt(A * A + B * B);
  const H = Math.atan2(B, A);

  // 明度・彩度を調整
  const L2 = Math.min(Math.max(L + lOffset, 0), 1);
  const C2 = Math.max(C * cFactor, 0);

  // 極座標 → 直交座標
  const A2 = C2 * Math.cos(H);
  const B2 = C2 * Math.sin(H);

  // 知覚均等Lab → 線形sRGB
  const l2_ = L2 + 0.3963377774 * A2 + 0.2158037573 * B2;
  const m2_ = L2 - 0.1055613458 * A2 - 0.0638541728 * B2;
  const s2_ = L2 - 0.0894841775 * A2 - 1.2914855480 * B2;
  const lr2 = l2_ * l2_ * l2_, lg2 = m2_ * m2_ * m2_, lb2 = s2_ * s2_ * s2_;
  const rL =  4.0767416621 * lr2 - 3.3077115913 * lg2 + 0.2309699292 * lb2;
  const gL = -1.2684380046 * lr2 + 2.6097574011 * lg2 - 0.3413193965 * lb2;
  const bL = -0.0041960863 * lr2 - 0.7034186147 * lg2 + 1.7076147010 * lb2;

  // 線形 → sRGB
  const toSrgb = v => {
    v = Math.min(Math.max(v, 0), 1);
    return Math.round((v <= 0.0031308 ? v * 12.92 : 1.055 * Math.pow(v, 1 / 2.4) - 0.055) * 255);
  };
  return `rgb(${toSrgb(rL)}, ${toSrgb(gL)}, ${toSrgb(bL)})`;
}

// RGBをHSLに変換して明度をfactor倍し、rgb文字列で返す
function rgbLighten(r, g, b, factor) {
  const rn = r / 255, gn = g / 255, bn = b / 255;
  const max = Math.max(rn, gn, bn), min = Math.min(rn, gn, bn);
  const l = (max + min) / 2;
  const d = max - min;
  let h = 0, s = 0;
  if (d !== 0) {
    s = d / (1 - Math.abs(2 * l - 1));
    if (max === rn)      h = ((gn - bn) / d + 6) % 6;
    else if (max === gn) h = (bn - rn) / d + 2;
    else                 h = (rn - gn) / d + 4;
    h /= 6;
  }
  const l2 = Math.min(l * factor, 1);
  const c = (1 - Math.abs(2 * l2 - 1)) * s;
  const x = c * (1 - Math.abs((h * 6) % 2 - 1));
  const m = l2 - c / 2;
  let r2, g2, b2;
  const hi = Math.floor(h * 6) % 6;
  if      (hi === 0) { r2 = c; g2 = x; b2 = 0; }
  else if (hi === 1) { r2 = x; g2 = c; b2 = 0; }
  else if (hi === 2) { r2 = 0; g2 = c; b2 = x; }
  else if (hi === 3) { r2 = 0; g2 = x; b2 = c; }
  else if (hi === 4) { r2 = x; g2 = 0; b2 = c; }
  else               { r2 = c; g2 = 0; b2 = x; }
  return `rgb(${Math.round((r2 + m) * 255)}, ${Math.round((g2 + m) * 255)}, ${Math.round((b2 + m) * 255)})`;
}



/*┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓*/
/*┃  EXボタン関連                                ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/

// クリック終了時にホバーハイライトを一時的に無効化→フェードイン復活
function onBtnCustomMouseup(e) {
  const btn = e.target.closest('.btn-ex-main');
  if (!btn) return;

  clearTimeout(btn._hoverHighlightTimer);
  btn.classList.remove('btn-hover-highlight-fadein');
  btn.classList.add('btn-hover-highlight-off');

  btn._hoverHighlightTimer = setTimeout(() => {
    btn.classList.remove('btn-hover-highlight-off');
    btn.classList.add('btn-hover-highlight-fadein');

    setTimeout(() => {
      btn.classList.remove('btn-hover-highlight-fadein');
    }, 500);
  }, 1000);
}
document.addEventListener('mouseup', onBtnCustomMouseup);

/*──────────────────────────────────────────────*/
/*  サブボタン関連                               */
/*──────────────────────────────────────────────*/
// ホバー時に使う色を事前計算して要素に保存（初回のみ・_btnSubOriginalColorを確定）
function initBtnSubColors(el) {
  const staticBg = getStaticBgColor(el);
  const style = getComputedStyle(el);
  let primaryColor = style.getPropertyValue('--btn-primary-color').trim();
  if (!primaryColor || primaryColor.startsWith('var(')) {
    primaryColor = style.getPropertyValue('--btn-bg-color').trim();
  }

  el._btnSubOriginalBg = staticBg;
  el._btnSubHoverBg = primaryColor;
  el._btnSubOriginalHoverBg = primaryColor;
  el._btnSubBlendedColor = blendBgWithPage(staticBg, el);
  el._btnSubOriginalColor = style.color;
}

// ホバー色のみ再計算（_btnSubOriginalColorは上書きしない）
function refreshBtnSubHoverColors(el) {
  const staticBg = getStaticBgColor(el);
  const style = getComputedStyle(el);
  let primaryColor = style.getPropertyValue('--btn-primary-color').trim();
  if (!primaryColor || primaryColor.startsWith('var(')) {
    primaryColor = style.getPropertyValue('--btn-bg-color').trim();
  }
  el._btnSubOriginalBg = staticBg;
  el._btnSubHoverBg = primaryColor;
  el._btnSubBlendedColor = blendBgWithPage(staticBg, el);
}

// ホバー開始：background-colorをprimaryColor、colorをblendedColorに切り替え
function onBtnSubMouseenter(e) {
  const el = e.currentTarget;
  if (!el._btnSubHoverBg || el._btnSubHovering) return;
  // アクティブ中は--btn-primary-colorが変わっている可能性があるため再計算（originalColorは保持）
  if (el.classList.contains('btn-ex-active')) refreshBtnSubHoverColors(el);
  el._btnSubHovering = true;
  el.style.backgroundColor = el._btnSubHoverBg;
  el.style.color = el._btnSubBlendedColor;
}

// ホバー終了：invert+アクティブ中は色を維持、それ以外はリセット
function onBtnSubMouseleave(e) {
  const el = e.currentTarget;
  if (el.classList.contains('btn-ex-sub-toggle-invert') && el.classList.contains('btn-ex-active')) return;
  el._btnSubHovering = false;
  el.style.backgroundColor = '';
  el.style.color = el.classList.contains('btn-ex-active') ? '' : (el._btnSubOriginalColor ?? '');
}

// btn-exをアクティブ化（btn-ex-activeを付与・subかつinvertなら色も適用）
function activateBtnCustom(btn) {
  if (!btn || (!btn.classList.contains('btn-ex-main') && !btn.classList.contains('btn-ex-sub'))) return;
  if (btn.classList.contains('btn-ex-sub') && btn.classList.contains('btn-ex-sub-toggle-invert')) {
    if (!btn._btnSubHoverBg) initBtnSubColors(btn);
    btn._btnSubHovering = true;
    btn.style.backgroundColor = btn._btnSubHoverBg;
    btn.style.color = btn._btnSubBlendedColor;
  } else {
    btn.style.color = '';
  }
  btn.classList.add('btn-ex-active');
}

// btn-exを非アクティブ化（btn-ex-activeを削除・subかつinvertなら色もリセット）
function deactivateBtnCustom(btn) {
  if (!btn || (!btn.classList.contains('btn-ex-main') && !btn.classList.contains('btn-ex-sub'))) return;
  if (btn.classList.contains('btn-ex-sub') && btn.classList.contains('btn-ex-sub-toggle-invert')) {
    if (btn._btnSubOriginalBg !== undefined) {
      btn._btnSubHoverBg = btn._btnSubOriginalHoverBg ?? btn._btnSubHoverBg;
      btn._btnSubBlendedColor = blendBgWithPage(btn._btnSubOriginalBg, btn);
    }
    btn._btnSubHovering = false;
    btn.style.backgroundColor = '';
    btn.style.color = btn._btnSubOriginalColor ?? '';
  } else {
    btn.style.color = '';
  }
  btn.classList.remove('btn-ex-active');
}

window.activateBtnCustom = activateBtnCustom;
window.deactivateBtnCustom = deactivateBtnCustom;

// collapseトグル展開時：activateBtnCustomに委譲
function onBtnSubCollapseShow(collapseEl) {
  const btn = document.querySelector(`[data-bs-toggle="collapse"][data-bs-target="#${collapseEl.id}"]`);
  activateBtnCustom(btn);
}

// collapseトグル格納時：deactivateBtnCustomに委譲
function onBtnSubCollapseHide(collapseEl) {
  const btn = document.querySelector(`[data-bs-toggle="collapse"][data-bs-target="#${collapseEl.id}"]`);
  deactivateBtnCustom(btn);
}

// 各.btn-ex-sub要素に色の事前計算とイベントリスナーを登録
function setupBtnSub(el) {
  setTimeout(initBtnSubColors, 200, el);
  el.addEventListener('mouseenter', onBtnSubMouseenter);
  el.addEventListener('mouseleave', onBtnSubMouseleave);
}

document.querySelectorAll('.btn-ex-sub').forEach(setupBtnSub);

// data-bs-toggle="btn-ex" : 単独トグル（クリックでbtn-ex-activeをオン/オフ）
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-bs-toggle="btn-ex"]');
  if (!btn) return;
  btn.classList.contains('btn-ex-active') ? deactivateBtnCustom(btn) : activateBtnCustom(btn);
});

// data-bs-toggle="btn-ex-group" : 排他グループ（クリックしたボタンをon、兄弟をoff）
document.addEventListener('click', e => {
  const group = e.target.closest('[data-bs-toggle="btn-ex-group"]');
  if (!group) return;
  const btn = e.target.closest('.btn-ex-main, .btn-ex-sub');
  if (!btn || !group.contains(btn)) return;
  group.querySelectorAll('.btn-ex-main, .btn-ex-sub').forEach(b => {
    b === btn ? activateBtnCustom(b) : deactivateBtnCustom(b);
  });
});

// collapseイベントでホバー色を制御
document.addEventListener('show.bs.collapse', e => onBtnSubCollapseShow(e.target));
document.addEventListener('hide.bs.collapse', e => onBtnSubCollapseHide(e.target));

// ページ読み込み時、初期アクティブ状態を適用（initBtnSubColorsの遅延後に実行）
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    // collapse連動ボタン：既に展開中のcollapseに対応するボタンをアクティブ化
    document.querySelectorAll('[data-bs-toggle="collapse"].btn-ex-sub').forEach(btn => {
      const targetId = btn.getAttribute('data-bs-target');
      if (!targetId) return;
      const collapseEl = document.querySelector(targetId);
      if (!collapseEl || !collapseEl.classList.contains('show')) return;
      if (!btn._btnSubHoverBg) return;
      activateBtnCustom(btn);
    });
    // btn-ex-groupボタン：HTMLでbtn-ex-activeが付いているボタンをアクティブ化
    document.querySelectorAll('[data-bs-toggle="btn-ex-group"] .btn-ex-main.btn-ex-active, [data-bs-toggle="btn-ex-group"] .btn-ex-sub.btn-ex-active').forEach(btn => {
      activateBtnCustom(btn);
    });
  }, 250);
});



/*┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓*/
/*┃  テーブル四隅セル取得                         ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/

// thead/tbody の全セルをグリッドマップに展開し、視覚的な四隅のセルを返す
// 戻り値: { topLeft, topRight, bottomLeft, bottomRight } （重複あり得る）
window.getTableCornerCells = function(table) {
  const sections = [table.tHead, table.tBodies[0]].filter(Boolean);
  // グリッドマップ: map[row][col] = td/th 要素
  const map = [];
  let globalRow = 0;

  for (const section of sections) {
    for (const tr of section.rows) {
      let col = 0;
      for (const cell of tr.cells) {
        // 既に埋まってるcolをスキップ
        while (map[globalRow]?.[col]) col++;
        const rowspan = cell.rowSpan || 1;
        const colspan = cell.colSpan || 1;
        for (let r = 0; r < rowspan; r++) {
          for (let c = 0; c < colspan; c++) {
            const rr = globalRow + r;
            const cc = col + c;
            if (!map[rr]) map[rr] = [];
            map[rr][cc] = cell;
          }
        }
        col += colspan;
      }
      globalRow++;
    }
  }

  const maxRow = map.length - 1;
  const maxCol = Math.max(...map.map(row => row.length - 1));

  return {
    topLeft:     map[0]?.[0],
    topRight:    map[0]?.[maxCol],
    bottomLeft:  map[maxRow]?.[0],
    bottomRight: map[maxRow]?.[maxCol],
  };
};

// テーブルの四隅セルに border-radius を適用する
// radius: CSSの値文字列（例: '5px'）、省略時は .table の border-radius を使用
window.applyTableCornerRadius = function(table, radius) {
  const r = radius ?? getComputedStyle(table).borderRadius;
  const { topLeft, topRight, bottomLeft, bottomRight } = getTableCornerCells(table);
  if (topLeft)     topLeft.style.borderTopLeftRadius     = r;
  if (topRight)    topRight.style.borderTopRightRadius    = r;
  if (bottomLeft)  bottomLeft.style.borderBottomLeftRadius  = r;
  if (bottomRight) bottomRight.style.borderBottomRightRadius = r;
};

// DOMContentLoaded時に .table クラスを持つ全テーブルに自動適用
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('table.table').forEach(t => applyTableCornerRadius(t));
});




/*┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓*/
/*┃  DataTables 共通初期化                        ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/
window.initDataTable = function(tableId, options) {
  const defaults = {
    language: {
      url: '/js/dataTables-ja.json',
      paginate: {
        previous: '<span class="nf nf-fa-caret_left fs-5"></span>',
        next: '<span class="nf nf-fa-caret_right fs-5"></span>'
      }
    },
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
  };
  return $(tableId).DataTable($.extend(true, {}, defaults, options));
};


/*┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓*/
/*┃  DataTables ページネーション                  ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/
// .page-link に btn-ex-sub 相当のホバー色変化を適用（イベント委譲）
function resolveColor(colorStr) {
  const div = document.createElement('div');
  div.style.cssText = 'position:absolute;visibility:hidden;width:0;height:0;';
  div.style.backgroundColor = colorStr;
  document.body.appendChild(div);
  const resolved = getComputedStyle(div).backgroundColor;
  document.body.removeChild(div);
  return resolved;
}

function setupDtPageLink(el) {
  if (el._dtPgInitialized) return;
  el._dtPgInitialized = true;
  const style = getComputedStyle(el);
  const primaryColor = resolveColor(style.getPropertyValue('--dt-pg-color').trim());
  el._dtPgOriginalBg = getStaticBgColor(el);
  el._dtPgHoverBg = primaryColor;
  el._dtPgBlendedColor = blendBgWithPage(el._dtPgOriginalBg, el);
  el.addEventListener('mouseenter', () => {
    if (el.closest('.page-item.disabled') || el.closest('.page-item.active')) return;
    el.style.setProperty('background-color', el._dtPgHoverBg, 'important');
    el.style.setProperty('color', el._dtPgBlendedColor, 'important');
  });
  el.addEventListener('mouseleave', () => {
    el.style.removeProperty('background-color');
    el.style.removeProperty('color');
  });
}

function setupAllDtPageLinks() {
  document.querySelectorAll('.dataTables_paginate .page-link').forEach(setupDtPageLink);
}

// DataTablesのdrawイベント（ページ切り替え含む）でpage-linkを登録
$(document).on('draw.dt', setupAllDtPageLinks);

// 初回描画はdraw.dtが発火しないケースがあるためDOMContentLoaded後にも実行
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(setupAllDtPageLinks, 500);
});
