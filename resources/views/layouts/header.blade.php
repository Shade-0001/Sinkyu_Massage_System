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
        class="notice-bell-btn d-flex align-items-center gap-1 px-2 py-1 hover-highlight-30 rounded-1 position-relative border-0 bg-transparent text-gray-90"
        aria-label="お知らせ">
        <span class="position-relative">
          <span class="nf nf-fa-bell fs-5"></span>
          <!-- 未読バッジ -->
          <span id="notice-unread-badge"
            class="position-absolute d-none badge rounded-pill"
            style="top:-4px;left:-4px;min-width:18px;font-size:10px;padding:2px 4px;background:#EDBE00;color:#fff;">
            0
          </span>
        </span>
        <span id="notice-chevron" class="nf nf-md-chevron_down submenu-arrow" style="font-size:12px;"></span>
      </button>

      <!-- パネルラッパー（リスト＋詳細を横並びで収める） -->
      <div id="notice-panels" class="notice-panels position-absolute" style="display:none;">

        <!-- 通知詳細パネル（リストの左側） -->
        <div id="notice-detail-panel"
          class="notice-detail-panel flex-column overflow-hidden rounded-start-2 border border-secondary border-opacity-25 shadow-lg bg-gray-26" style="display:none;">
          <div class="flex-shrink-0 px-3 py-2 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-2 bg-gray-20">
            <span class="fw-bold small text-gray-90 flex-grow-1 text-truncate" id="notice-detail-title"></span>
            <button id="notice-toggle-read-btn" type="button"
              class="btn btn-custom-sm ms-auto flex-shrink-0"
              style="font-size:11px;padding:2px 8px;">
            </button>
          </div>
          <div class="flex-shrink-0 px-3 pt-2 pb-1 small text-secondary" id="notice-detail-date"></div>
          <div class="notice-detail-body flex-fill overflow-y-auto px-3 pb-3 text-gray-80"
            style="white-space:pre-wrap;font-size:13px;line-height:1.7;" id="notice-detail-content"></div>
        </div>

        <!-- 通知リストパネル -->
        <div id="notice-list-panel"
          class="notice-list-panel d-flex flex-column overflow-hidden rounded-end-2 border border-secondary border-opacity-25 shadow-lg bg-gray-26">
          <div class="flex-shrink-0 px-3 py-2 border-bottom border-secondary border-opacity-25 fw-bold small text-gray-90 bg-gray-20">
            お知らせ
          </div>
          <div id="notice-list-body" class="notice-list-body flex-fill overflow-y-auto">
            <div class="text-muted small px-3 py-3 text-center">読み込み中...</div>
          </div>
        </div>

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
  const panels        = document.getElementById('notice-panels');
  const listBody      = document.getElementById('notice-list-body');
  const detailPanel   = document.getElementById('notice-detail-panel');
  const detailTitle   = document.getElementById('notice-detail-title');
  const detailDate    = document.getElementById('notice-detail-date');
  const detailContent = document.getElementById('notice-detail-content');
  const toggleReadBtn = document.getElementById('notice-toggle-read-btn');
  const badge         = document.getElementById('notice-unread-badge');
  const chevron       = document.getElementById('notice-chevron');

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
      <div class="notice-list-item px-3 py-2 d-flex align-items-start gap-2 border-bottom border-secondary border-opacity-10 ${n.is_read ? '' : 'notice-unread'} ${currentNoticeId === n.id ? 'notice-active' : ''}"
           data-id="${n.id}" role="button" tabindex="0">
        <span class="notice-dot flex-shrink-0 mt-1 ${n.is_read ? 'opacity-0' : ''}">●</span>
        <div class="flex-grow-1 overflow-hidden">
          <div class="small fw-bold text-truncate text-gray-90">${escHtml(n.title)}</div>
          <div class="text-secondary" style="font-size:11px;">${n.created_at}</div>
        </div>
      </div>
    `).join('');

    listBody.querySelectorAll('.notice-list-item').forEach(el => {
      el.addEventListener('click', (e) => { e.stopPropagation(); openDetail(parseInt(el.dataset.id)); });
      el.addEventListener('keydown', e => { if (e.key === 'Enter') openDetail(parseInt(el.dataset.id)); });
    });
  }

  // ── 詳細パネルを開く（即時既読化） ──────────
  async function openDetail(id) {
    const n = notices.find(x => x.id === id);
    if (!n) return;
    currentNoticeId = id;
    detailTitle.textContent   = n.title;
    detailDate.textContent    = n.created_at;
    detailContent.textContent = n.content;
    syncToggleBtn(n.is_read);
    detailPanel.style.display = 'flex';
    renderList();

    // 未読なら即時既読化
    if (!n.is_read) {
      try {
        const res = await fetch(ROUTES.toggleRead(id), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN':     CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type':     'application/json',
          },
        });
        const data = await res.json();
        n.is_read = data.is_read;
        updateBadge(data.unread_count);
        syncToggleBtn(data.is_read);
        renderList();
      } catch {/* silent */}
    }
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

  // ── 既読トグル（ボタン押下） ──────────────
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
    panels.style.display = 'flex';
    chevron.classList.add('rotated');
    fetchNotices();
  }

  function closeAll() {
    panels.style.display = 'none';
    detailPanel.style.display = 'none';
    chevron.classList.remove('rotated');
    currentNoticeId = null;
  }

  // ── イベント ──────────────────────────────
  bellBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (panels.style.display !== 'none' && panels.style.display !== '') {
      closeAll();
    } else {
      openListPanel();
    }
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
