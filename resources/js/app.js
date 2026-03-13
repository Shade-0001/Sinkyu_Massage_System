//-- resources/js/app.js --//

import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// btn-custom-* クリック後のハイライト抑制
document.addEventListener('mousedown', e => {
  const btn = e.target.closest('[class*="btn-custom-"]');
  if (!btn) return;
  btn.classList.add('btn-clicked');
  btn.addEventListener('mouseup', () => {
    setTimeout(() => btn.classList.remove('btn-clicked'), 1000);
  }, { once: true });
});
