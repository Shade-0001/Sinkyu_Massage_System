<div class="px-3 d-flex align-items-center gap-3 fw-bold">
  <!-- サイドバートグルボタン -->
  <button id="sidebar-toggle" type="button" class="px-2 py-2 hover-highlight-30 rounded-1">
    <div id="sidebar-toggle-icon">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </button>

  <div class="vr opacity-50 my-1"></div>

  <a href="{{route('index')}}" class="text-decoration-none text-gray-90 py-1 px-2 rounded-1 hover-highlight-30 highlight-target-text-white">ホーム</a>

  <div class="vr opacity-50 my-1"></div>

  <div class="ms-auto d-flex align-self-stretch align-items-center gap-3">

    <!-- 通知ベルアイコン -->
    <div class="notice-widget position-relative d-flex align-items-center">
      <!-- ベルボタン -->
      <button id="notice-bell-btn" type="button"
        class="notice-bell-btn px-2 py-1 hover-highlight-30 rounded-1 position-relative border-0 bg-transparent text-gray-90"
        aria-label="お知らせ">
        <span class="nf nf-fa-bell fs-5"></span>
        <!-- 未読バッジ -->
        <span id="notice-unread-badge"
          class="notice-unread-badge position-absolute d-none"
          style="top:-4px;left:-4px;min-width:18px;height:18px;font-size:10px;line-height:18px;padding:0 4px;border-radius:9px;background:#EDBE00;color:#fff;text-align:center;font-weight:bold;">
          0
        </span>
      </button>

      <!-- 通知リストパネル -->
      <div id="notice-list-panel" class="notice-list-panel d-none position-absolute shadow-lg">
        <div class="notice-list-header px-3 py-2 border-bottom border-secondary border-opacity-25 text-gray-90 fw-bold small">
          お知らせ
        </div>
        <div id="notice-list-body" class="notice-list-body">
          <div class="text-muted small px-3 py-3 text-center">読み込み中...</div>
        </div>
      </div>

      <!-- 通知詳細パネル -->
      <div id="notice-detail-panel" class="notice-detail-panel d-none position-absolute shadow-lg">
        <div class="notice-detail-header px-3 py-2 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-2">
          <button id="notice-detail-back" type="button"
            class="btn-icon border-0 bg-transparent p-0 text-gray-90 hover-highlight-30 rounded-1 px-1"
            title="戻る">
            <span class="nf nf-fa-chevron_left small"></span>
          </button>
          <span class="fw-bold small text-gray-90 flex-grow-1 text-truncate" id="notice-detail-title"></span>
          <button id="notice-toggle-read-btn" type="button"
            class="btn btn-custom-sm ms-auto flex-shrink-0"
            style="font-size:11px;padding:2px 8px;">
          </button>
        </div>
        <div class="notice-detail-meta px-3 pt-2 pb-1 small text-muted" id="notice-detail-date"></div>
        <div class="notice-detail-body px-3 pb-3" id="notice-detail-content"
          style="white-space:pre-wrap;font-size:13px;line-height:1.7;"></div>
      </div>
    </div>

    <div class="vr opacity-50 my-1"></div>
    <form method="POST" action="{{ route('logout') }}">
    @csrf
    <a href="{{ route('logout') }}"
      onclick="event.preventDefault(); localStorage.removeItem('sidebarState'); localStorage.removeItem('submenuStates'); this.closest('form').submit();"
      class="fw-bold text-decoration-none text-gray-90 py-1 px-2 rounded-1 hover-highlight-30 highlight-target-text-white">
      {{ __('ログアウト') }}
    </a>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const ROUTES = {
    list:       '{{ route("notices.api.list") }}',
    toggleRead: (id) => `/notices/api/${id}/toggle-read`,
  };
  const CSRF = document.querySelector('meta[name="csrf-token"]').content;

  const bellBtn       = document.getElementById('notice-bell-btn');
  const listPanel     = document.getElementById('notice-list-panel');
  const listBody      = document.getElementById('notice-list-body');
  const detailPanel   = document.getElementById('notice-detail-panel');
  const detailTitle   = document.getElementById('notice-detail-title');
  const detailDate    = document.getElementById('notice-detail-date');
  const detailContent = document.getElementById('notice-detail-content');
  const detailBack    = document.getElementById('notice-detail-back');
  const toggleReadBtn = document.getElementById('notice-toggle-read-btn');
  const badge         = document.getElementById('notice-unread-badge');

  let notices = [];
  let currentNoticeId = null;

  // ── バッジ更新 ──────────────────────────────
  function updateBadge(count) {
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
    }
  }

  // ── 通知一覧レンダリング ───────────────────
  function renderList() {
    if (notices.length === 0) {
      listBody.innerHTML = '<div class="text-muted small px-3 py-3 text-center">お知らせはありません</div>';
      return;
    }
    listBody.innerHTML = notices.map(n => `
      <div class="notice-list-item px-3 py-2 d-flex align-items-start gap-2 border-bottom border-secondary border-opacity-10 ${n.is_read ? '' : 'notice-unread'}"
           data-id="${n.id}" role="button" tabindex="0">
        <span class="notice-dot flex-shrink-0 mt-1 ${n.is_read ? 'opacity-0' : ''}">●</span>
        <div class="flex-grow-1 overflow-hidden">
          <div class="notice-item-title small fw-bold text-truncate">${escHtml(n.title)}</div>
          <div class="notice-item-date" style="font-size:11px;color:#999;">${n.created_at}</div>
        </div>
      </div>
    `).join('');

    listBody.querySelectorAll('.notice-list-item').forEach(el => {
      el.addEventListener('click', () => openDetail(parseInt(el.dataset.id)));
      el.addEventListener('keydown', e => { if (e.key === 'Enter') openDetail(parseInt(el.dataset.id)); });
    });
  }

  // ── 詳細パネルを開く ─────────────────────
  function openDetail(id) {
    const n = notices.find(x => x.id === id);
    if (!n) return;
    currentNoticeId = id;
    detailTitle.textContent   = n.title;
    detailDate.textContent    = n.created_at;
    detailContent.textContent = n.content;
    syncToggleBtn(n.is_read);
    listPanel.classList.add('d-none');
    detailPanel.classList.remove('d-none');
  }

  // ── 既読ボタン状態同期 ────────────────────
  function syncToggleBtn(isRead) {
    toggleReadBtn.textContent = isRead ? '未読にする' : '既読にする';
    toggleReadBtn.className = isRead
      ? 'btn btn-custom-gray-sm ms-auto flex-shrink-0'
      : 'btn btn-custom-sm ms-auto flex-shrink-0';
    toggleReadBtn.style.cssText = 'font-size:11px;padding:2px 8px;';
  }

  // ── 一覧データ取得 ────────────────────────
  async function fetchNotices() {
    try {
      const res = await fetch(ROUTES.list, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      notices = data.notices;
      updateBadge(data.unread_count);
      renderList();
    } catch {
      listBody.innerHTML = '<div class="text-danger small px-3 py-2">取得失敗</div>';
    }
  }

  // ── 既読トグル ────────────────────────────
  async function toggleRead() {
    if (!currentNoticeId) return;
    try {
      const res = await fetch(ROUTES.toggleRead(currentNoticeId), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN':      CSRF,
          'X-Requested-With':  'XMLHttpRequest',
          'Content-Type':      'application/json',
        },
      });
      const data = await res.json();
      const n = notices.find(x => x.id === currentNoticeId);
      if (n) n.is_read = data.is_read;
      updateBadge(data.unread_count);
      syncToggleBtn(data.is_read);
      renderList();
    } catch {/* silent */}
  }

  // ── パネル開閉 ────────────────────────────
  function openListPanel() {
    detailPanel.classList.add('d-none');
    listPanel.classList.remove('d-none');
    fetchNotices();
  }

  function closeAll() {
    listPanel.classList.add('d-none');
    detailPanel.classList.add('d-none');
    currentNoticeId = null;
  }

  // ── イベント ──────────────────────────────
  bellBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (!listPanel.classList.contains('d-none') || !detailPanel.classList.contains('d-none')) {
      closeAll();
    } else {
      openListPanel();
    }
  });

  detailBack.addEventListener('click', () => {
    detailPanel.classList.add('d-none');
    listPanel.classList.remove('d-none');
    currentNoticeId = null;
  });

  toggleReadBtn.addEventListener('click', toggleRead);

  document.addEventListener('click', (e) => {
    const widget = document.querySelector('.notice-widget');
    if (widget && !widget.contains(e.target)) {
      closeAll();
    }
  });

  // ── 初回バッジ取得（パネルを開かずに） ────
  fetch(ROUTES.list, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.json())
    .then(d => { notices = d.notices; updateBadge(d.unread_count); })
    .catch(() => {});

  // ── ユーティリティ ────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
})();
</script>
@endpush
