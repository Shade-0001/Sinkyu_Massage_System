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

  // サブメニュー展開状態の保存・復元ユーティリティ
  function saveSubmenuStates() {
    const states = {};
    document.querySelectorAll('.submenu').forEach(submenu => {
      states[submenu.id] = submenu.classList.contains('open');
    });
    localStorage.setItem('submenuStates', JSON.stringify(states));
  }

  function openSubmenu(submenu, toggle, animate) {
    const arrow = toggle ? toggle.querySelector('.submenu-arrow') : null;
    submenu.classList.add('open');
    if (arrow) arrow.classList.add('rotated');
    if (animate) {
      submenu.style.maxHeight = submenu.scrollHeight + 'px';
      submenu.addEventListener('transitionend', function handler() {
        if (submenu.classList.contains('open')) {
          submenu.style.maxHeight = 'none';
        }
        submenu.removeEventListener('transitionend', handler);
      });
    } else {
      submenu.style.maxHeight = 'none';
    }
  }

  function closeSubmenu(submenu, toggle) {
    const arrow = toggle ? toggle.querySelector('.submenu-arrow') : null;
    submenu.style.maxHeight = submenu.scrollHeight + 'px';
    setTimeout(() => {
      submenu.style.maxHeight = '0';
    }, 10);
    submenu.classList.remove('open');
    if (arrow) arrow.classList.remove('rotated');
  }

  // サブメニューの展開･格納機能
  const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');

  submenuToggles.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      const targetId = this.getAttribute('data-target');
      const submenu = document.getElementById(targetId);

      if (submenu.classList.contains('open')) {
        closeSubmenu(submenu, this);
      } else {
        openSubmenu(submenu, this, true);
      }
      saveSubmenuStates();
    });
  });

  // localStorageから展開状態を復元（インラインスクリプト未適用分のみ）
  const submenuStates = JSON.parse(localStorage.getItem('submenuStates') || '{}');
  document.querySelectorAll('.submenu').forEach(submenu => {
    if (submenuStates[submenu.id] && !submenu.classList.contains('open')) {
      const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
      openSubmenu(submenu, toggle, false);
    }
  });

  // サブメニューリンクのアクティブ状態設定（アクティブな親は強制展開）
  const submenuLinks = document.querySelectorAll('.submenu-link');

  submenuLinks.forEach(link => {
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
      const parentSubmenu = link.closest('.submenu');
      if (parentSubmenu && !parentSubmenu.classList.contains('open')) {
        const parentToggle = document.querySelector(`[data-target="${parentSubmenu.id}"]`);
        openSubmenu(parentSubmenu, parentToggle, false);
        saveSubmenuStates();
      }
    }
  });
});
