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

// clone要素からトランジションなし・静的状態のbackground-colorを取得
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
  // ページ背景色を取得（rgb形式で返ってくる想定）
  let pageBg = 'rgb(255, 255, 255)';
  let node = el.parentElement;
  while (node && node !== document.documentElement) {
    const bg = getComputedStyle(node).backgroundColor;
    if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent') { pageBg = bg; break; }
    node = node.parentElement;
  }

  // bgColorとpageBgをcanvasで合成して正確なrgbを得る
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 1;
  const ctx = canvas.getContext('2d');
  // ページ背景を先に塗る
  ctx.fillStyle = pageBg;
  ctx.fillRect(0, 0, 1, 1);
  // 透過色を重ねる
  ctx.fillStyle = bgColor;
  ctx.fillRect(0, 0, 1, 1);
  const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
  return `rgb(${r}, ${g}, ${b})`;
}

// btn-custom-sub: ホバー時にbackground-colorとcolorを入れ替え
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

function onBtnSubMouseenter(e) {
  const el = e.currentTarget;
  if (!el._btnSubHoverBg || el._btnSubHovering) return;
  el._btnSubHovering = true;
  el.style.backgroundColor = el._btnSubHoverBg;
  el.style.color = el._btnSubBlendedColor;
}

function onBtnSubMouseleave(e) {
  const el = e.currentTarget;
  el._btnSubHovering = false;
  el.style.backgroundColor = '';
  el.style.color = el._btnSubOriginalColor ?? '';
}

function setupBtnSub(el) {
  setTimeout(initBtnSubColors, 200, el);
  el.addEventListener('mouseenter', onBtnSubMouseenter);
  el.addEventListener('mouseleave', onBtnSubMouseleave);
}

document.querySelectorAll('.btn-custom-sub').forEach(setupBtnSub);
