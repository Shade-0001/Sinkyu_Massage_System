// public/js/sidebar.js

document.addEventListener('DOMContentLoaded', function() {
  const toggleButton = document.getElementById('sidebar-toggle');

  // トグルボタンのクリックイベント
  if (toggleButton) {
    toggleButton.addEventListener('click', function() {
      const newState = document.documentElement.dataset.sidebar === 'open' ? 'closed' : 'open';
      document.documentElement.dataset.sidebar = newState;
      localStorage.setItem('sidebarState', newState);
    });
  }

  // アクティブリンクの設定
  const currentPath = window.location.pathname;
  const sidebarLinks = document.querySelectorAll('.sidebar-link');

  sidebarLinks.forEach(link => {
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
    }
  });

  // サブメニューの展開/格納機能
  const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');

  submenuToggles.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-target');
      const submenu = document.getElementById(targetId);
      const arrow = this.querySelector('.submenu-arrow');

      if (submenu.classList.contains('open')) {
        // 格納
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        setTimeout(() => {
          submenu.style.maxHeight = '0';
        }, 10);
        submenu.classList.remove('open');
        arrow.classList.remove('rotated');
      } else {
        // 展開
        submenu.classList.add('open');
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        arrow.classList.add('rotated');

        // アニメーション完了後にmax-heightをautoに設定（リサイズ対応）
        submenu.addEventListener('transitionend', function handler() {
          if (submenu.classList.contains('open')) {
            submenu.style.maxHeight = 'none';
          }
          submenu.removeEventListener('transitionend', handler);
        });
      }
    });
  });

  // サブメニューリンクのアクティブ状態設定
  const submenuLinks = document.querySelectorAll('.submenu-link');

  submenuLinks.forEach(link => {
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
      // 親サブメニューを自動展開（アニメーション付き）
      const parentSubmenu = link.closest('.submenu');
      if (parentSubmenu) {
        const parentToggle = document.querySelector(`[data-target="${parentSubmenu.id}"]`);
        if (parentToggle) {
          const arrow = parentToggle.querySelector('.submenu-arrow');
          arrow.classList.add('rotated');
        }

        // 次のフレームでアニメーション開始
        setTimeout(() => {
          parentSubmenu.classList.add('open');
          parentSubmenu.style.maxHeight = parentSubmenu.scrollHeight + 'px';

          // アニメーション完了後にmax-heightをnoneに設定
          parentSubmenu.addEventListener('transitionend', function handler() {
            if (parentSubmenu.classList.contains('open')) {
              parentSubmenu.style.maxHeight = 'none';
            }
            parentSubmenu.removeEventListener('transitionend', handler);
          });
        }, 100);
      }
    }
  });
});
