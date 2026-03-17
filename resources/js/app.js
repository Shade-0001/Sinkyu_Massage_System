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
  return rgbLighten(r, g, b, 1.2);
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
/*┃  カスタムボタン関連                           ┃*/
/*┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛*/

// クリック終了時にホバーハイライトを一時的に無効化→フェードイン復活
function onBtnCustomMouseup(e) {
  const btn = e.target.closest('.btn-custom');
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
// ホバー時に使う色を事前計算して要素に保存
function initBtnSubColors(el) {
  const staticBg = getStaticBgColor(el);
  const style = getComputedStyle(el);
  let primaryColor = style.getPropertyValue('--btn-primary-color').trim();
  if (!primaryColor || primaryColor.startsWith('var(')) {
    primaryColor = style.getPropertyValue('--btn-bg-color').trim();
  }

  el._btnSubOriginalBg = staticBg;
  el._btnSubHoverBg = primaryColor;
  el._btnSubBlendedColor = blendBgWithPage(staticBg, el);
  el._btnSubOriginalColor = style.color;
}

// ホバー開始：background-colorをprimaryColor、colorをblendedColorに切り替え
function onBtnSubMouseenter(e) {
  const el = e.currentTarget;
  if (!el._btnSubHoverBg || el._btnSubHovering) return;
  el._btnSubHovering = true;
  el.style.backgroundColor = el._btnSubHoverBg;
  el.style.color = el._btnSubBlendedColor;
}

// ホバー終了：background-colorとcolorを元の状態に戻す
function onBtnSubMouseleave(e) {
  const el = e.currentTarget;
  el._btnSubHovering = false;
  el.style.backgroundColor = '';
  el.style.color = el._btnSubOriginalColor ?? '';
}

// 各.btn-custom-sub要素に色の事前計算とイベントリスナーを登録
function setupBtnSub(el) {
  setTimeout(initBtnSubColors, 200, el);
  el.addEventListener('mouseenter', onBtnSubMouseenter);
  el.addEventListener('mouseleave', onBtnSubMouseleave);
}

document.querySelectorAll('.btn-custom-sub').forEach(setupBtnSub);
