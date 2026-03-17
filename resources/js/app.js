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

// アルファ合成：透過色を背景色の上に乗せたときの最終的な不透明色を返す
function blendWithBackground(fgRgba, bgRgb) {
  const fg = fgRgba.match(/[\d.]+/g).map(Number);
  const bg = bgRgb.match(/[\d.]+/g).map(Number);
  const isSrgb = fgRgba.startsWith('color(srgb');
  const fgR = isSrgb ? fg[0] * 255 : fg[0];
  const fgG = isSrgb ? fg[1] * 255 : fg[1];
  const fgB = isSrgb ? fg[2] * 255 : fg[2];
  const a = fg[3] ?? 1;
  const r = Math.round(fgR * a + bg[0] * (1 - a));
  const g = Math.round(fgG * a + bg[1] * (1 - a));
  const b = Math.round(fgB * a + bg[2] * (1 - a));
  return `rgb(${r}, ${g}, ${b})`;
}

// btn-custom-sub: ホバー時にbackground-colorとcolorを入れ替える
document.querySelectorAll('.btn-custom-sub').forEach(el => {
  el.addEventListener('mouseenter', () => {
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
