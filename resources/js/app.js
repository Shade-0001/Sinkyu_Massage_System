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

    el.dataset.originalBg = originalBg;
    el.dataset.originalColor = style.color;

    el.style.backgroundColor = primaryColor;
    el.style.color = originalBg;
  });

  el.addEventListener('mouseleave', () => {
    el.style.backgroundColor = el.dataset.originalBg ?? '';
    el.style.color = el.dataset.originalColor ?? '';
    delete el.dataset.originalBg;
    delete el.dataset.originalColor;
  });
});
