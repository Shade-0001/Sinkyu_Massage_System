//-- resources/js/app.js --//

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// btn-custom: クリック終了時にホバーハイライトを一時的に無効化→フェードイン復活
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

// hex色文字列をRGB配列に変換
function hexToRgb255(hex) {
  const m = hex.replace('#', '').match(/.{2}/g);
  if (!m) return null;
  return [parseInt(m[0], 16), parseInt(m[1], 16), parseInt(m[2], 16)];
}

// btn-custom-sub: ページロード時に色を事前計算して保存、ホバー時に入れ替え
document.querySelectorAll('.btn-custom-sub').forEach(el => {
  // トランジションが落ち着いた後に事前計算（ロード直後のトランジション補間値を避ける）
  setTimeout(() => {
    const style = getComputedStyle(el);
    let primaryColor = style.getPropertyValue('--btn-primary-color').trim();
    if (!primaryColor || primaryColor.startsWith('var(')) {
      primaryColor = style.getPropertyValue('--btn-bg-color').trim();
    }

    // primaryColorをRGB配列に変換（hex / rgb / color(srgb) 対応）
    let primaryRgb =
      hexToRgb255(primaryColor) ||
      (() => {
        const m = primaryColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        return m ? [parseInt(m[1]), parseInt(m[2]), parseInt(m[3])] : null;
      })() ||
      (() => {
        const m = primaryColor.match(/^color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
        return m ? [Math.round(m[1]*255), Math.round(m[2]*255), Math.round(m[3]*255)] : null;
      })();

    if (!primaryRgb) return;

    // ページ背景色を取得
    let pageBgRgb = null;
    let node = el.parentElement;
    while (node && node !== document.documentElement) {
      const bg = getComputedStyle(node).backgroundColor;
      if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') {
        const m = bg.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (m) { pageBgRgb = [parseInt(m[1]), parseInt(m[2]), parseInt(m[3])]; break; }
      }
      node = node.parentElement;
    }
    if (!pageBgRgb) pageBgRgb = [255, 255, 255];

    // color-mix(in srgb, primaryColor 30%, transparent) をページ背景と合成した不透明色
    const blendedColor = `rgb(${Math.round(primaryRgb[0]*0.3 + pageBgRgb[0]*0.7)}, ${Math.round(primaryRgb[1]*0.3 + pageBgRgb[1]*0.7)}, ${Math.round(primaryRgb[2]*0.3 + pageBgRgb[2]*0.7)})`;
    // ホバー時のbackground-colorはprimaryColorをそのまま使う
    const hoverBg = `rgb(${primaryRgb[0]}, ${primaryRgb[1]}, ${primaryRgb[2]})`;

    // 事前計算した値を保存
    el._btnSubHoverBg = hoverBg;
    el._btnSubBlendedColor = blendedColor;
    el._btnSubOriginalColor = style.color;
  }, 200);

  el.addEventListener('mouseenter', () => {
    if (!el._btnSubHoverBg || el._btnSubHovering) return;
    el._btnSubHovering = true;
    el.style.backgroundColor = el._btnSubHoverBg;
    el.style.color = el._btnSubBlendedColor;
  });

  el.addEventListener('mouseleave', () => {
    el._btnSubHovering = false;
    el.style.backgroundColor = '';
    el.style.color = el._btnSubOriginalColor ?? '';
  });
});
