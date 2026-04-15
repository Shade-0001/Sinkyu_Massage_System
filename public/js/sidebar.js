// public/js/sidebar.js

const SIDEBAR_BREAKPOINT = 992; // lg

// フッターのX軸マージン（.main-contentのpaddingを打ち消すネガティブマージン）
function updateFooterOffset() {
  const mainContent = document.querySelector('.main-content');
  if (!mainContent) return;
  const style = getComputedStyle(mainContent);
  mainContent.style.setProperty('--footer-padding-left', parseFloat(style.paddingLeft) + 'px');
  mainContent.style.setProperty('--footer-padding-right', parseFloat(style.paddingRight) + 'px');
}

document.addEventListener('DOMContentLoaded', function() {
  const toggleButton = document.getElementById('sidebar-toggle');

  // トグルボタンのクリックイベント
  if (toggleButton) {
    toggleButton.addEventListener('click', function() {
      const newState = document.documentElement.dataset.sidebar === 'open' ? 'closed' : 'open';
      document.documentElement.dataset.sidebar = newState;
      localStorage.setItem('sidebarState', newState);

      if (newState === 'closed') {
        document.querySelectorAll('.submenu').forEach(submenu => {
          const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
          closeSubmenu(submenu, toggle);
        });
        localStorage.removeItem('submenuStates');
      } else {
        // 現在ページのサブメニューを強制展開（sidebar-activeクラスで判定）
        const activeLink = document.querySelector('.submenu-link.sidebar-active');
        if (activeLink) {
          const parentSubmenu = activeLink.closest('.submenu');
          if (parentSubmenu && !parentSubmenu.classList.contains('open')) {
            // 強制展開時：他の展開中サブメニューを全て格納
            document.querySelectorAll('.submenu.open').forEach(submenu => {
              if (submenu.id !== parentSubmenu.id) {
                const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
                closeSubmenu(submenu, toggle);
              }
            });
            const parentToggle = document.querySelector(`[data-target="${parentSubmenu.id}"]`);
            openSubmenu(parentSubmenu, parentToggle, true);
            saveSubmenuStates(parentSubmenu.id, 'auto');
          }
        }
      }
    });
  }

  // 初期値セット・リサイズ時更新
  updateFooterOffset();
  window.addEventListener('resize', updateFooterOffset);

  // ウィンドウ幅によるサイドバーの自動格納・復元
  let autoCollapsed = false; // このセッションで自動格納したかどうか

  function handleResize() {
    const isNarrow = window.innerWidth < SIDEBAR_BREAKPOINT;
    const currentState = document.documentElement.dataset.sidebar;

    if (isNarrow && currentState === 'open') {
      // 幅が狭くなったら自動格納（localStorageは変更しない）
      document.documentElement.dataset.sidebar = 'closed';
      autoCollapsed = true;
    } else if (!isNarrow && autoCollapsed && currentState === 'closed') {
      // 幅が戻ったら自動復元（自動格納した場合のみ）
      document.documentElement.dataset.sidebar = 'open';
      autoCollapsed = false;
    }
  }

  // ページロード時に即時チェック
  if (window.innerWidth < SIDEBAR_BREAKPOINT && document.documentElement.dataset.sidebar === 'open') {
    document.documentElement.dataset.sidebar = 'closed';
    autoCollapsed = true;
  }

  window.addEventListener('resize', handleResize);

  // アクティブリンクの設定
  const currentPath = window.location.pathname;
  const sidebarLinks = document.querySelectorAll('.sidebar-link');

  sidebarLinks.forEach(link => {
    if (link.getAttribute('href') === currentPath) {
      link.classList.add('active');
    }
  });

  // サブメニュー展開状態の保存・復元ユーティリティ
  // type: 'manual'（ユーザー操作）| 'auto'（強制展開）| null（状態のみ更新）
  function saveSubmenuStates(changedId, type) {
    const states = JSON.parse(localStorage.getItem('submenuStates') || '{}');
    document.querySelectorAll('.submenu').forEach(submenu => {
      const isOpen = submenu.classList.contains('open');
      if (!isOpen) {
        states[submenu.id] = false;
      } else if (submenu.id === changedId && type) {
        states[submenu.id] = type;
      } else if (!states[submenu.id]) {
        states[submenu.id] = 'manual';
      }
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
      saveSubmenuStates(targetId, 'manual');
    });
  });

  // 現在ページのアクティブサブメニューIDを取得
  const activeSubmenuLink = document.querySelector('.submenu-link.sidebar-active');
  const activeSubmenuId = activeSubmenuLink ? activeSubmenuLink.closest('.submenu')?.id : null;

  // localStorageから展開状態を復元
  // 'auto'展開かつ現在ページがそのサブメニュー配下でない場合は格納
  const submenuStates = JSON.parse(localStorage.getItem('submenuStates') || '{}');
  document.querySelectorAll('.submenu').forEach(submenu => {
    const state = submenuStates[submenu.id];
    if (!state) return;
    if (state === 'auto' && submenu.id !== activeSubmenuId) {
      // 強制展開かつ別系統ページ → 格納（インラインスクリプトで展開済みの場合も閉じる）
      submenu.classList.remove('open');
      submenu.style.maxHeight = '0';
      const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
      const arrow = toggle ? toggle.querySelector('.submenu-arrow') : null;
      if (arrow) arrow.classList.remove('rotated');
      saveSubmenuStates(submenu.id, null);
      return;
    }
    if (!submenu.classList.contains('open')) {
      const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
      openSubmenu(submenu, toggle, false);
    }
  });

  // アクティブなサブメニューリンクの親を強制展開（sidebar-activeクラスで判定）
  if (activeSubmenuLink) {
    const parentSubmenu = activeSubmenuLink.closest('.submenu');
    if (parentSubmenu && !parentSubmenu.classList.contains('open')) {
      // 強制展開時：他の展開中サブメニューを全て格納
      document.querySelectorAll('.submenu.open').forEach(submenu => {
        if (submenu.id !== parentSubmenu.id) {
          const toggle = document.querySelector(`[data-target="${submenu.id}"]`);
          closeSubmenu(submenu, toggle);
        }
      });
      const parentToggle = document.querySelector(`[data-target="${parentSubmenu.id}"]`);
      openSubmenu(parentSubmenu, parentToggle, true);
      saveSubmenuStates(parentSubmenu.id, 'auto');
    }
  }
});
