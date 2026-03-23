<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('schedules.index')"
  />

  <div class="container-fluid">
    <!-- 施術者セレクトボックス -->
    <div class="mb-3">
      <div>
        <label for="therapist-select" class="form-label fw-bold">施術者</label>
        <div class="vr ms-2 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
        <select id="therapist-select">
          @foreach($therapists as $therapist)
            <option value="{{ $therapist->id }}" {{ $selectedTherapistId == $therapist->id ? 'selected' : '' }}>
              ID-{{ str_pad($therapist->id, $therapistIdLength, '0', STR_PAD_LEFT) }}｜{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }}
            </option>
          @endforeach
          <option value="all" {{ $selectedTherapistId === 'all' ? 'selected' : '' }}>［ 全表示 ］</option>
        </select>
      </div>
    </div>

    <!-- スケジュール表コントロール -->
    <div class="row mb-2">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <!-- 左：スクロールボタン -->
          <div class="btn-group" role="group">
            <button type="button" id="prev-btn" class="btn btn-outline-dark border-2 px-3 py-1" style="font-size: 0.9rem">◀</button>
            <button type="button" id="current-btn" class="btn btn-outline-dark border-2 border-start-0 border-end-0 fw-semibold px-0 py-1" style="font-size: 0.9rem">［ 現在 ］</button>
            <button type="button" id="next-btn" class="btn btn-outline-dark border-2 px-3 py-1" style="font-size: 0.9rem">▶</button>
          </div>

          <!-- 中央：表示中の年月日 -->
          <div class="text-center d-flex">
            <div id="current-year" class="fs-5 fw-semibold"></div>
            <div class="vr ms-2 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
            <div id="current-month-day" class="fs-5 fw-semibold"></div>
          </div>

          <!-- 右：表示切り替えボタン -->
          <div class="btn-group" role="group">
            <button type="button" id="week-view-btn" class="btn btn-dark border-2 fw-medium px-2 py-1" style="font-size: 0.9rem">週表示</button>
            <button type="button" id="month-view-btn" class="btn btn-outline-dark border-2 fw-medium px-2 py-1" style="font-size: 0.9rem">月表示</button>
          </div>
        </div>
      </div>
    </div>

    
    <!-- スケジュール表 -->
    <div class="row">
      <div class="col-12">
        <div id="schedule-container" class="border rounded bg-white" style="overflow-x: auto; overflow-y: auto; position: relative;">
          <!-- 週表示 -->
          <div id="week-view" style="display: block;">
            <table class="table table-bordered mb-0" id="week-schedule-table">
              <thead class="table-light sticky-top">
                <tr id="week-header-row" style="font-size: 0.8rem">
                  <!-- テーブルヘッダーがJavaScriptで生成される -->
                </tr>
              </thead>
              <tbody id="week-schedule-body">
                <!-- 時間帯ごとの行がJavaScriptで生成される -->
              </tbody>
            </table>
          </div>

          <!-- 月表示 -->
          <div id="month-view" style="display: none;">
            <table class="table table-bordered mb-0" id="month-schedule-table" style="table-layout: fixed;">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="width: 50px;">週</th>
                  <th>日</th>
                  <th>月</th>
                  <th>火</th>
                  <th>水</th>
                  <th>木</th>
                  <th>金</th>
                  <th>土</th>
                </tr>
              </thead>
              <tbody id="month-schedule-body">
                <!-- 月のカレンダーがJavaScriptで生成される -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 施術詳細モーダル -->
  <div class="modal fade" id="event-detail-modal" tabindex="-1" aria-labelledby="event-detail-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="event-detail-modal-label">施術詳細</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2 d-flex align-items-start">
            <strong class="me-2">利用者氏名：</strong>
            <span id="detail-user-name"></span>
          </div>
          <div class="mb-2 d-flex align-items-start">
            <strong class="me-2">開始日時：</strong>
            <span id="detail-start-datetime"></span>
          </div>
          <div class="mb-2 d-flex align-items-start">
            <strong class="me-2">終了日時：</strong>
            <span id="detail-end-datetime"></span>
          </div>
          <div class="mb-2 d-flex align-items-start">
            <strong class="me-2">施術内容：</strong>
            <span id="detail-therapy-type"></span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="edit-record-btn">編集</button>
        </div>
      </div>
    </div>
  </div>

  <!-- 新規登録モーダル -->
  <div class="modal fade" id="new-event-modal" tabindex="-1" aria-labelledby="new-event-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="new-event-modal-label">新規登録</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3 d-flex align-items-start">
            <strong class="me-2">開始日時：</strong>
            <span id="new-start-datetime"></span>
          </div>
          <div class="mb-3">
            <label for="new-user-select" class="form-label fw-bold">利用者</label>
            <select class="form-select" id="new-user-select">
              <option value="">╌╌╌</option>
              @foreach($clinicUsers as $user)
                <option value="{{ $user->id }}">ID-{{ str_pad($user->id, $clinicUserIdLength, '0', STR_PAD_LEFT) }}｜{{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="new-therapist-select" class="form-label fw-bold">施術者</label>
            <select class="form-select" id="new-therapist-select">
              <option value="">╌╌╌</option>
              @foreach($therapists as $therapist)
                <option value="{{ $therapist->id }}">ID-{{ str_pad($therapist->id, $therapistIdLength, '0', STR_PAD_LEFT) }}｜{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="go-to-registration-btn">登録画面へ</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    @push('styles')
      <style>
        /* ensure modals sit above page content */
        .modal { z-index: 2000; }
        .modal-backdrop { z-index: 1990; }
        .modal .modal-content { background-color: #fff; }

        /* 週表示テーブルの時刻列を固定 */
        #week-schedule-table thead th:first-child,
        #week-schedule-table tbody td:first-child {
          position: sticky;
          left: 0;
          z-index: 20;
          background-color: #f8f9fa;
          background-clip: padding-box;
        }

        #week-schedule-table thead th:first-child {
          z-index: 21;
          background-color: #f8f9fa;
          background-clip: padding-box;
        }


      </style>
    @endpush
    <script>
      // PHP変数をJavaScriptに渡す
      window.scheduleConfig = {
        therapistId: '{{ $selectedTherapistId ?? "" }}',
        businessHoursStart: '{{ $businessHoursStart }}',
        businessHoursEnd: '{{ $businessHoursEnd }}',
        timeSlots: @json($timeSlots),
        closedDays: @json($closedDays),
        dataUrl: '{{ route("schedules.data") }}',
        recordsIndexUrl: '{{ route("records.index") }}',
        recordsEditUrlBase: '{{ url("records") }}',
        recordsStartYear: {{ $recordsStartYear }},
        recordsStartMonth: {{ $recordsStartMonth }},
        futureMonths: {{ $futureMonths }}
      };
    </script>
    <script src="{{ asset('js/schedules.js') }}"></script>
  @endpush
</x-app-layout>
