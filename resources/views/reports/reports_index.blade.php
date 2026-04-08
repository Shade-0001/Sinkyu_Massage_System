<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header :title="$page_header_title" :breadcrumbs="App\Support\Breadcrumbs::generate('reports.index')" />

  <!-- 利用者選択フォーム -->
  <form method="GET" action="{{ route('reports.index') }}" id="filterForm">
    <div class="mb-3">
      <label for="clinic_user_id"></label>
      <select name="clinic_user_id" id="clinic_user_id" onchange="document.getElementById('filterForm').submit();">
        <option value="">╌╌╌</option>
        @foreach ($clinicUsers as $user)
          <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
            ID-{{ str_pad($user->id, $clinicUserIdLength, '0', STR_PAD_LEFT) }}｜{{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}｜{{ $user->last_kana }}{{ "\u{2000}" }}{{ $user->first_kana }}
          </option>
        @endforeach
      </select>
      <button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm ms-3" onclick="openUserSearchPopup()">利用者検索</button>
    </div>
  </form>
  <br>

  @if (session('success'))
    <div class="alert alert-success">
      {{ session('success') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (!$selectedUserId)
    <div class="p-4 text-center fs-5 text-secondary">
      利用者を選択してください
    </div>
  @else
    <!-- 報告書データ一覧表示エリア -->
    <div id="reports-list-area" style="overflow-y: auto; overflow-x: hidden; border: 1px solid #dee2e6;">
      @php
        // 現在年月に最も近いデータがある年月を特定
        $currentYearMonthVal = $currentYear * 100 + $currentMonth;
        $closestYearMonthVal = null;
        $closestDiff = PHP_INT_MAX;
        foreach ($reportsByYear as $yr => $yrData) {
            foreach ($yrData['months'] as $item) {
                if ($item['report']) {
                    $ymVal = $item['year'] * 100 + $item['month'];
                    $diff = $currentYearMonthVal - $ymVal; // 正=過去, 負=未来
                    $absDiff = abs($diff);
                    // 過去側優先（同距離なら過去を選ぶ）
                    if ($absDiff < $closestDiff || ($absDiff === $closestDiff && $diff >= 0)) {
                        $closestDiff = $absDiff;
                        $closestYearMonthVal = $ymVal;
                    }
                }
            }
        }
        $closestYear = $closestYearMonthVal ? intdiv($closestYearMonthVal, 100) : null;
        $closestMonth = $closestYearMonthVal ? $closestYearMonthVal % 100 : null;
      @endphp

      @foreach ($reportsByYear as $year => $yearData)
        @php
          $hasReports = $yearData['has_reports'];
          $months = $yearData['months'];
          $collapseId = 'year-' . $year;
          $isYearExpanded = $year == $closestYear;
        @endphp

        @if (!$loop->first)
          <div style="margin: 5rem 0 0 0;"></div>
        @endif

        <div class="bg-gray-96 rounded-1 p-2 me-2 year-block" style="position: relative;">
          <!-- 年ヘッダー（折り畳み・展開ボタン） -->
          <div class="year-header mb-2 d-flex align-items-center">
            <button class="btn-ex-sub btn-ex-blue btn-ex-xl btn-ex-sub-toggle-invert fs-2 gap-2 me-2" type="button" data-toggle-year="{{ $collapseId }}" aria-expanded="{{ $isYearExpanded ? 'true' : 'false' }}">
              <span class="align-self-center lh-1 pt-05 pb-1">{{ $year }}</span>
              <span class="year-toggle-arrow {{ $isYearExpanded ? 'rotated' : '' }} d-inline-flex align-items-center align-self-center">
                <i class="nf nf-md-chevron_down fs-5 ps-2"></i>
              </span>
            </button>
            <hr class="year-top-hr" style="flex-grow: 1; border: none; border-top: 4px solid #000; margin: 0rem;">
          </div>

          <!-- 月別データ（scaleY展開） -->
          <div class="year-content {{ $isYearExpanded ? 'expanded' : '' }}" id="{{ $collapseId }}" data-year="{{ $year }}">
            @foreach ($months as $item)
            @php
              $yearMonth = sprintf('%04d-%02d', $item['year'], $item['month']);
              $monthCollapseId = "month-{$year}-{$item['month']}";
              $isMonthExpanded = $item['year'] == $closestYear && $item['month'] == $closestMonth;
            @endphp
            <div class="report-month-section ms-4" data-year-month="{{ $yearMonth }}">
              @if ($item['report'])
                <!-- 報告書データあり -->
                <div class="btn-ex-sub btn-ex-blue btn-ex-lg btn-ex-sub-toggle-invert mb-1" role="button" data-toggle-month="{{ $monthCollapseId }}" aria-expanded="{{ $isMonthExpanded ? 'true' : 'false' }}">
                  {{ $item['year'] }}年{{ "\u{2000}" }}{{ $item['month'] }}月
                  <span class="year-toggle-arrow {{ $isMonthExpanded ? 'rotated' : '' }}" style="display: inline-flex; align-items: center; align-self: center;">
                    <i class="nf nf-md-chevron_down ps-2"></i>
                  </span>
                </div>
                <div class="month-content {{ $isMonthExpanded ? 'expanded' : '' }}" id="{{ $monthCollapseId }}" style="overflow-x: hidden;">
                  <table class="table table-bordered mb-0" style="font-size: 0.9rem; table-layout: fixed; width: 100%;">
                    <colgroup>
                      <col style="width: 5rem;">
                      <col>
                    </colgroup>
                    <tbody>
                      <tr>
                        <th class="align-middle text-center bg-light">主観症状</th>
                        <td class="align-middle report-text-cell"><div style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $item['report']->subjective_symptom_and_wish ?? '' }}</div></td>
                      </tr>
                      <tr>
                        <th class="align-middle text-center bg-light">客観症状</th>
                        <td class="align-middle report-text-cell"><div style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $item['report']->objective_symptom ?? '' }}</div></td>
                      </tr>
                      <tr>
                        <th class="align-middle text-center bg-light">施術内容</th>
                        <td class="align-middle report-text-cell"><div style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $item['report']->therapy_content ?? '' }}</div></td>
                      </tr>
                      <tr>
                        <th class="align-middle text-center bg-light">治療計画</th>
                        <td class="align-middle report-text-cell"><div style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $item['report']->therapy_plan ?? '' }}</div></td>
                      </tr>
                      <tr>
                        <th class="align-middle text-center bg-light" style="width: 5rem;">操作</th>
                        <td class="align-middle" style="white-space: nowrap;">
                          <a href="{{ route('reports.edit', $item['report']->id) }}"><button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm">編集</button></a>
                          <a href="{{ route('reports.duplicate', $item['report']->id) }}"><button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm">複製</button></a>
                          <button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm" onclick="openReportPrintModal('{{ $selectedUserId }}', '{{ $yearMonth }}')">印刷</button>
                          <form method="POST" action="{{ route('reports.destroy', $item['report']->id) }}" style="display:inline;" onsubmit="return confirm('この報告書データを削除してもよろしいですか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-ex-main btn-ex-red btn-ex-sm">削除</button>
                          </form>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              @else
                <!-- 報告書データなし -->
                <div class="d-flex align-items-center">
                  <div class="fw-medium fs-5 mb-0 opacity-75">{{ $item['year'] }}年{{ "\u{2000}" }}{{ $item['month'] }}月</div>
                  <div class="vr ms-3 me-5" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
                  <span class="text-secondary me-3">該当データなし</span>
                  <a href="{{ route('reports.create', ['clinic_user_id' => $selectedUserId, 'year' => $item['year'], 'month' => $item['month']]) }}">
                    <button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm">新規登録</button>
                  </a>
                </div>
              @endif
            </div>
            @if (!$loop->last)
              <hr class="border-secondary border-2 ms-4">
            @else
              <div class="mb-3"></div>
            @endif
          @endforeach
          </div>
          <!-- hr2：格納時はhr1と同位置・同幅、展開時に下部へ移動後に左端を広げる -->
          <hr class="year-bottom-hr" style="position: absolute; border: none; border-top: 4px solid #000; margin: 0; display: none;">
        </div>
      @endforeach
    </div>
  @endif

  @push('scripts')
    <script src="{{ asset('js/utility.js') }}"></script>
    <script>
      // PHP変数をJavaScriptに渡す
      window.reportsConfig = {
        selectedUserId: @json($selectedUserId),
        scrollToYearMonth: @json($scrollToYearMonth),
        userSearchUrl: '{{ route('user.search') }}'
      };

      // ── hr2 ヘルパー ──────────────────────────────
      function getHr1Metrics(block) {
        const topHr = block.querySelector('.year-top-hr');
        return { top: topHr.offsetTop, left: topHr.offsetLeft, width: topHr.offsetWidth };
      }

      function blockExpandedTop(block) {
        const pb = parseFloat(getComputedStyle(block).paddingBottom) || 0;
        const bottomHr = block.querySelector('.year-bottom-hr');
        const hrH = bottomHr ? bottomHr.offsetHeight : 0;
        return block.clientHeight - pb - hrH;
      }

      function setHr2ToHr1(block) {
        const bottomHr = block.querySelector('.year-bottom-hr');
        const m = getHr1Metrics(block);
        bottomHr.style.transition = 'none';
        bottomHr.style.top = m.top + 'px';
        bottomHr.style.left = m.left + 'px';
        bottomHr.style.width = m.width + 'px';
      }

      function setHr2Expanded(block) {
        const bottomHr = block.querySelector('.year-bottom-hr');
        const cs = getComputedStyle(block);
        const pl = parseFloat(cs.paddingLeft) || 0;
        const pr = parseFloat(cs.paddingRight) || 0;
        const hrWidth = block.clientWidth - pl - pr;
        const hrLeft = (block.clientWidth - hrWidth) / 2;
        bottomHr.style.transition = 'none';
        bottomHr.style.top = blockExpandedTop(block) + 'px';
        bottomHr.style.left = hrLeft + 'px';
        bottomHr.style.width = hrWidth + 'px';
      }

      // rAFループでblock.clientHeightの変化に追従してhr2のtopを更新
      const hr2Loops = new Map();
      function startHr2Tracking(block) {
        const id = block.id || (block._hr2id = block._hr2id || Math.random());
        if (hr2Loops.has(id)) cancelAnimationFrame(hr2Loops.get(id));
        const bottomHr = block.querySelector('.year-bottom-hr');
        let last = -1;
        let stable = 0;
        function loop() {
          const h = block.clientHeight;
          bottomHr.style.top = blockExpandedTop(block) + 'px';
          if (h === last) { stable++; if (stable >= 3) { hr2Loops.delete(id); return; } }
          else stable = 0;
          last = h;
          hr2Loops.set(id, requestAnimationFrame(loop));
        }
        hr2Loops.set(id, requestAnimationFrame(loop));
      }

      // contentを展開（height 0→scrollHeight + scaleY 0→1）
      function expandContent(content) {
        content.style.height = content.scrollHeight + 'px';
        content.style.transform = 'scaleY(1)';
        content.style.opacity = '1';
        content.addEventListener('transitionend', function onEnd(e) {
          if (e.target !== content || e.propertyName !== 'height') return;
          content.removeEventListener('transitionend', onEnd);
          content.style.height = 'auto';
        });
      }

      // contentを格納（height scrollHeight→0 + scaleY 1→0）
      function collapseContent(content) {
        content.style.height = content.scrollHeight + 'px';
        requestAnimationFrame(() => {
          content.style.height = '0';
          content.style.transform = 'scaleY(0)';
          content.style.opacity = '0';
        });
      }

      // ── 年展開格納 ────────────────────────────────
      function toggleYear(btn) {
        const targetId = btn.dataset.toggleYear;
        const content = document.getElementById(targetId);
        const block = btn.closest('.year-block');
        const arrow = btn.querySelector('.year-toggle-arrow');
        const bottomHr = block.querySelector('.year-bottom-hr');
        const isExpanded = content.classList.contains('expanded');

        if (isExpanded) {
          content.classList.remove('expanded');
          btn.setAttribute('aria-expanded', 'false');
          if (arrow) arrow.classList.remove('rotated');
          collapseContent(content);
          // hr2：top/left/widthを同時にhr1位置へtransition（rAFループ不要）
          const m = getHr1Metrics(block);
          bottomHr.style.transition = 'top 0.3s ease, left 0.3s ease, width 0.3s ease';
          bottomHr.style.top = m.top + 'px';
          bottomHr.style.left = m.left + 'px';
          bottomHr.style.width = m.width + 'px';
          content.addEventListener('transitionend', function onEnd(e) {
            if (e.target !== content || e.propertyName !== 'height') return;
            content.removeEventListener('transitionend', onEnd);
            bottomHr.style.transition = 'none';
            bottomHr.style.display = 'none';
          });
        } else {
          content.classList.add('expanded');
          btn.setAttribute('aria-expanded', 'true');
          if (arrow) arrow.classList.add('rotated');
          setHr2ToHr1(block);
          bottomHr.style.display = 'block';
          expandContent(content);
          // hr2：幅を全幅に広げながらtopも追従
          const cs = getComputedStyle(block);
          const pl = parseFloat(cs.paddingLeft) || 0;
          const pr = parseFloat(cs.paddingRight) || 0;
          const hrWidth = block.clientWidth - pl - pr;
          const hrLeft = (block.clientWidth - hrWidth) / 2;
          requestAnimationFrame(() => {
            bottomHr.style.transition = 'left 0.3s ease, width 0.3s ease';
            bottomHr.style.left = hrLeft + 'px';
            bottomHr.style.width = hrWidth + 'px';
            startHr2Tracking(block);
          });
        }
      }

      // ── 月展開格納 ────────────────────────────────
      function toggleMonth(btn) {
        const targetId = btn.dataset.toggleMonth;
        const content = document.getElementById(targetId);
        const block = btn.closest('.year-block');
        const arrow = btn.querySelector('.year-toggle-arrow');
        const isExpanded = content.classList.contains('expanded');

        content.classList.toggle('expanded', !isExpanded);
        btn.setAttribute('aria-expanded', String(!isExpanded));
        if (arrow) arrow.classList.toggle('rotated', !isExpanded);
        if (isExpanded) collapseContent(content);
        else expandContent(content);
        // 月展開格納時はtopのみrAF追従
        startHr2Tracking(block);
      }

      document.addEventListener('DOMContentLoaded', function() {
        // 初期状態のheight・hr2セット
        document.querySelectorAll('.year-block').forEach(function(block) {
          const yearContent = block.querySelector('.year-content');
          const bottomHr = block.querySelector('.year-bottom-hr');
          if (!bottomHr) return;
          if (yearContent && yearContent.classList.contains('expanded')) {
            yearContent.style.height = 'auto';
            yearContent.style.transform = 'scaleY(1)';
            yearContent.style.opacity = '1';
            setHr2ToHr1(block);
            bottomHr.style.display = 'block';
            requestAnimationFrame(() => {
              const cs = getComputedStyle(block);
              const pl = parseFloat(cs.paddingLeft) || 0;
              const pr = parseFloat(cs.paddingRight) || 0;
              const hrWidth = block.clientWidth - pl - pr;
              const hrLeft = (block.clientWidth - hrWidth) / 2;
              bottomHr.style.transition = 'none';
              bottomHr.style.top = blockExpandedTop(block) + 'px';
              bottomHr.style.left = hrLeft + 'px';
              bottomHr.style.width = hrWidth + 'px';
            });
          } else {
            setHr2ToHr1(block);
          }
        });

        // 初期状態で展開済みの月コンテンツも height をセット
        document.querySelectorAll('.month-content.expanded').forEach(function(content) {
          content.style.height = 'auto';
          content.style.transform = 'scaleY(1)';
          content.style.opacity = '1';
        });

        // 年ボタンのクリックイベント
        document.querySelectorAll('[data-toggle-year]').forEach(function(btn) {
          btn.addEventListener('click', () => toggleYear(btn));
        });

        // 月ボタンのクリックイベント
        document.querySelectorAll('[data-toggle-month]').forEach(function(btn) {
          btn.addEventListener('click', () => toggleMonth(btn));
        });
      });

      // -webkit-line-clamp: 2 によるCSS省略のため、JS処理は不要
      function adjustReportTextCells() {}

      // デバウンス関数（リサイズイベントの頻度を制限）
      function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
          const later = () => {
            clearTimeout(timeout);
            func(...args);
          };
          clearTimeout(timeout);
          timeout = setTimeout(later, wait);
        };
      }

      // ページ読み込み時に指定された年月へ自動スクロール
      document.addEventListener('DOMContentLoaded', function() {
        // テキストセルの調整
        adjustReportTextCells();

        if (window.reportsConfig.scrollToYearMonth) {
          const targetSection = document.querySelector(`[data-year-month="${window.reportsConfig.scrollToYearMonth}"]`);
          if (targetSection) {
            // ターゲットセクションが属する年を展開
            const yearContent = targetSection.closest('.year-content');
            if (yearContent && !yearContent.classList.contains('expanded')) {
              const btn = document.querySelector(`[data-toggle-year="${yearContent.id}"]`);
              if (btn) toggleYear(btn);
            }

            // スクロール処理（展開アニメーション完了後に実行）
            setTimeout(() => {
              const container = document.getElementById('reports-list-area');
              if (container) {
                const containerRect = container.getBoundingClientRect();
                const targetRect = targetSection.getBoundingClientRect();
                const scrollOffset = targetRect.top - containerRect.top + container.scrollTop;
                container.scrollTo({ top: scrollOffset, behavior: 'smooth' });
              }
            }, 350);
          }
        }
      });

      // ウィンドウリサイズ時にもテキストセルを再調整（デバウンス適用：150ms）
      window.addEventListener('resize', debounce(adjustReportTextCells, 150));

      // 利用者検索ポップアップを開く
      function openUserSearchPopup() {
        const url = window.reportsConfig.userSearchUrl;
        const popup = window.open(url, 'UserSearch', 'width=800,height=600,scrollbars=yes');
        if (popup) {
          popup.focus();
        }
      }

      // 報告書PDF印刷
      function openReportPrintModal(userId, yearMonth) {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const today = `${yyyy}-${mm}-${dd}`;
        const filename = `報告書_${yyyy}-${mm}-${dd}_${hours}-${minutes}-${seconds}.pdf`;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/prints/report/${encodeURIComponent(filename)}`;
        form.target = '_blank';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const addHidden = (name, value) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = name;
          input.value = value;
          form.appendChild(input);
        };

        addHidden('_token', csrfToken);
        addHidden('clinic_user_id', userId);
        addHidden('service_year_month', yearMonth);
        addHidden('submission_date', today);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
      }

      // 利用者検索ポップアップからの選択を受け取る
      window.selectUser = function(userId) {
        document.getElementById('clinic_user_id').value = userId;
        document.getElementById('filterForm').submit();
      };
    </script>
  @endpush
</x-app-layout>
