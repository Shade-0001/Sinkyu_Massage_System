//-- resources/js/app.js --//

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// btn-custom-blue: クリック終了時にホバーハイライトを一時的に無効化
document.addEventListener('mouseup', (e) => {
  const btn = e.target.closest('.btn-custom-blue');
  if (!btn) return;

  btn.classList.add('btn-hover-highlight-off');

  clearTimeout(btn._hoverHighlightTimer);
  btn._hoverHighlightTimer = setTimeout(() => {
    btn.classList.remove('btn-hover-highlight-off');
  }, 1000);
});
