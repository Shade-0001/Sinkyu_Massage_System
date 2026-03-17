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

// 任意の色文字列をrgb(R,G,B)形式（0〜255整数）に変換
function parseColorToRgb255(colorStr) {
  // color(srgb R G B / A) 形式
  const srgbMatch = colorStr.match(/^color\(srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)/);
  if (srgbMatch) {
    return [
      Math.round(parseFloat(srgbMatch[1]) * 255),
      Math.round(parseFloat(srgbMatch[2]) * 255),
      Math.round(parseFloat(srgbMatch[3]) * 255),
    ];
  }
  // rgb(R, G, B) / rgba(R, G, B, A) 形式
  const rgbMatch = colorStr.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  if (rgbMatch) {
    return [parseInt(rgbMatch[1]), parseInt(rgbMatch[2]), parseInt(rgbMatch[3])];
  }
  return null;
}

// 任意の色文字列からアルファ値（0〜1）を取得
function parseAlpha(colorStr) {
  const slashMatch = colorStr.match(/\/\s*([\d.]+)\s*\)/);
  if (slashMatch) return parseFloat(slashMatch[1]);
  const rgbaMatch = colorStr.match(/^rgba\([^,]+,[^,]+,[^,]+,\s*([\d.]+)/);
  if (rgbaMatch) return parseFloat(rgbaMatch[1]);
  return 1;
}

// アルファ合成：透過色を背景色の上に乗せたときの最終的な不透明色を返す
function blendWithBackground(fgColor, bgRgb) {
  const fg = parseColorToRgb255(fgColor);
  const bg = parseColorToRgb255(bgRgb);
  if (!fg || !bg) return fgColor;
  const a = parseAlpha(fgColor);
  const r = Math.round(fg[0] * a + bg[0] * (1 - a));
  const g = Math.round(fg[1] * a + bg[1] * (1 - a));
  const b = Math.round(fg[2] * a + bg[2] * (1 - a));
  return `rgb(${r}, ${g}, ${b})`;
}

// btn-custom-sub: ホバー時にbackground-colorとcolorを入れ替える
document.querySelectorAll('.btn-custom-sub').forEach(el => {
  el.addEventListener('mouseenter', () => {
    // すでにホバー済みの場合はスキップ（子要素への移動で再発火防止）
    if (el.dataset.originalBg) return;

    const style = getComputedStyle(el);
    const originalBg = style.backgroundColor;
    let primaryColor = style.getPropertyValue('--btn-primary-color').trim();
    // --btn-primary-colorが未解決の場合は--btn-bg-colorを使用
    if (!primaryColor || primaryColor.startsWith('var(')) {
      primaryColor = style.getPropertyValue('--btn-bg-color').trim();
    }

    // ページ背景色を取得（透過の場合は白にフォールバック）
    const pageBg = (() => {
      let node = el.parentElement;
      while (node && node !== document.documentElement) {
        const bg = getComputedStyle(node).backgroundColor;
        if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') return bg;
        node = node.parentElement;
      }
      return 'rgb(255, 255, 255)';
    })();

    // ホバー前のbackground-color（透過）をページ背景と合成して不透明色を算出
    const blendedColor = blendWithBackground(originalBg, pageBg);

    el.dataset.originalBg = originalBg;
    el.dataset.originalColor = style.color;

    el.style.backgroundColor = primaryColor;
    el.style.color = blendedColor;
  });

  el.addEventListener('mouseleave', () => {
    el.style.backgroundColor = el.dataset.originalBg ?? '';
    el.style.color = el.dataset.originalColor ?? '';
    delete el.dataset.originalBg;
    delete el.dataset.originalColor;
  });
});
