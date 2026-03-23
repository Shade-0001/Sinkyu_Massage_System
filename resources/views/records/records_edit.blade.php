<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('records.edit', $record->id)"
  />

  @if($errors->any())
  <div class="alert alert-danger">
    <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
    </ul>
  </div>
  @endif

  <form id="recordEditForm" method="POST" action="{{ route('records.update', $record->id) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="clinic_user_id" value="{{ $record->clinic_user_id }}">
    <input type="hidden" name="original_dates" value="{{ json_encode($originalDates) }}">
    @if($from)
    <input type="hidden" name="from" value="{{ $from }}">
    @endif

    <div class="d-flex gap-3 align-items-start">
      <!-- カレンダー -->
      <div class="text-center position-relative" style="width: 15rem;">
        <div id="calendar-title-display" class="fs-4 fw-bold py-1 d-inline-block" style="cursor: default; min-width: 200px;"></div>
        <select id="calendar-title" class="position-absolute top-0 start-50 translate-middle-x opacity-0" style="cursor: pointer; font-size: 1.5rem; padding: 0.2em 0em; border: none; background: transparent;"></select>
        <div class="calendar" id="calendar">
          <!-- 曜日ヘッダー -->
          <div class="calendar-day-header text-center p-1 fw-bold sunday">日</div>
          <div class="calendar-day-header text-center p-1 fw-bold">月</div>
          <div class="calendar-day-header text-center p-1 fw-bold">火</div>
          <div class="calendar-day-header text-center p-1 fw-bold">水</div>
          <div class="calendar-day-header text-center p-1 fw-bold">木</div>
          <div class="calendar-day-header text-center p-1 fw-bold">金</div>
          <div class="calendar-day-header text-center p-1 fw-bold saturday">土</div>
        </div>
        <button type="button" id="clear-selection-btn" class="mt-2">選択解除</button>
      </div>

      <div class="vr border border-black border-1 mx-3"></div>

      <!-- 実績フィールド -->
      <div class="flex-grow-1" id="record-fields">
        <!-- 施術種類 -->
        <div class="d-flex">
          <label class="fw-semibold">施術種類</label>
          @error('therapy_type')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-2 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div>
            <label><input type="radio" name="therapy_type" value="1" id="therapy_type_acupuncture" {{ old('therapy_type', $record->therapy_type) == '1' ? 'checked' : '' }}>はり･きゅう</label>
            <label class="ms-3"><input type="radio" name="therapy_type" value="2" id="therapy_type_massage" {{ old('therapy_type', $record->therapy_type) == '2' ? 'checked' : '' }}>あんま･マッサージ</label>
          </div>
        </div>
        <div class="mb-3">
          <!-- 身体部位チェックボックス(あんま･マッサージ選択時のみ表示) -->
          <div id="bodyparts-section" class="{{ $record->therapy_type == 2 ? '' : 'd-none' }}">
            <label class="fw-semibold">　　部位</label>
            <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
            <label><input type="checkbox" name="bodyparts[]" value="1" {{ in_array('1', old('bodyparts', $selectedBodyparts)) ? 'checked' : '' }}> 躯幹</label>
            <label><input type="checkbox" name="bodyparts[]" value="2" {{ in_array('2', old('bodyparts', $selectedBodyparts)) ? 'checked' : '' }}> 右上肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="3" {{ in_array('3', old('bodyparts', $selectedBodyparts)) ? 'checked' : '' }}> 左上肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="4" {{ in_array('4', old('bodyparts', $selectedBodyparts)) ? 'checked' : '' }}> 右下肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="5" {{ in_array('5', old('bodyparts', $selectedBodyparts)) ? 'checked' : '' }}> 左下肢</label>
          </div>
        </div>

        <!-- 施術区分 -->
        <div class="mb-3">
          <label class="fw-semibold">施術区分</label>
          @error('therapy_category')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <label><input type="radio" name="therapy_category" value="1" id="therapy_category_visit" {{ old('therapy_category', $record->therapy_category) == '1' ? 'checked' : '' }}> 通院</label>
          <label class="ms-3"><input type="radio" name="therapy_category" value="2" id="therapy_category_housecall" {{ old('therapy_category', $record->therapy_category) == '2' ? 'checked' : '' }}> 往療</label>
        </div>

        <!-- 往療距離(往療選択時のみ表示) -->
        <div id="housecall-distance-section" class="{{ $record->therapy_category == 2 ? '' : 'd-none' }} mb-3">
          <label class="d-block mb-1 fw-bold">往療距離</label>
          <p class="my-1 small text-secondary">往療料が発生する場合は往療距離を入力(往療料無しなら0を入力)</p>
          <div id="housecall-distance-inputs"></div>
          <div class="mt-2">
            上記日付を全て <input type="number" id="bulk-distance" step="0.5" min="0" style="width: 80px;"> km に
            <button type="button" id="apply-bulk-distance">変更</button>
          </div>
        </div>

        <!-- 開始時刻 & 終了時刻 -->
        @php
          $bhStart = $businessHoursStart ? (int)explode(':', $businessHoursStart)[0] : 0;
          $bhEnd   = $businessHoursEnd   ? (int)explode(':', $businessHoursEnd)[0]   : 23;
          $startTimeVal = old('start_time', $record->start_time ? date('H:i', strtotime($record->start_time)) : '');
          $startH = $startTimeVal ? (int)explode(':', $startTimeVal)[0] : $bhStart;
          $startM = $startTimeVal ? (int)explode(':', $startTimeVal)[1] : 0;
          $endTimeVal = old('end_time', $record->end_time ? date('H:i', strtotime($record->end_time)) : '');
          $endH = $endTimeVal ? (int)explode(':', $endTimeVal)[0] : $bhStart;
          $endM = $endTimeVal ? (int)explode(':', $endTimeVal)[1] : 0;
        @endphp
        <div class="mb-3">
          <label class="fw-semibold">開始時刻</label>
          @error('start_time')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div class="time-select-group d-inline-flex align-items-center gap-1" data-target="start_time">
            <select class="time-select-hour">
              @for($h = $bhStart; $h <= $bhEnd; $h++)
                <option value="{{ $h }}" {{ $startH === $h ? 'selected' : '' }}>{{ $h }}</option>
              @endfor
            </select>
            <span>:</span>
            <select class="time-select-minute">
              @foreach([0, 10, 20, 30, 40, 50] as $m)
                <option value="{{ $m }}" {{ $startM === $m ? 'selected' : '' }}>{{ sprintf('%02d', $m) }}</option>
              @endforeach
            </select>
          </div>
          <input type="hidden" id="start_time" name="start_time" value="{{ $startTimeVal }}">
        </div>

        <div class="mb-3">
          <label class="fw-semibold">終了時刻</label>
          @error('end_time')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div class="time-select-group d-inline-flex align-items-center gap-1" data-target="end_time">
            <select class="time-select-hour">
              @for($h = $bhStart; $h <= $bhEnd; $h++)
                <option value="{{ $h }}" {{ $endH === $h ? 'selected' : '' }}>{{ $h }}</option>
              @endfor
            </select>
            <span>:</span>
            <select class="time-select-minute">
              @foreach([0, 10, 20, 30, 40, 50] as $m)
                <option value="{{ $m }}" {{ $endM === $m ? 'selected' : '' }}>{{ sprintf('%02d', $m) }}</option>
              @endforeach
            </select>
          </div>
          <input type="hidden" id="end_time" name="end_time" value="{{ $endTimeVal }}">
        </div>

        <!-- 施術内容 -->
        <div class="mb-3">
          <label class="fw-semibold" for="therapy_content_id">施術内容</label>
          @error('therapy_content_id')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <select id="therapy_content_id" name="therapy_content_id">
            <option value="">選択してください</option>
            @foreach($therapyContents as $content)
              <option value="{{ $content->id }}" data-therapy-type="{{ $content->therapy_type }}" {{ old('therapy_content_id', $record->therapy_content_id) == $content->id ? 'selected' : '' }}>{{ $content->therapy_content }}</option>
            @endforeach
          </select>

          <!-- 複製チェックボックス(あんま･マッサージ選択時のみ表示) -->
          <div id="therapy-content-duplication" class="{{ $record->therapy_type == 2 ? '' : 'd-none' }} mt-2 ms-3">
            <label><input type="checkbox" name="duplicate_massage" value="1" {{ old('duplicate_massage') ? 'checked' : '' }}> マッサージを同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_warm_compress" value="1" {{ old('duplicate_warm_compress') ? 'checked' : '' }}> 温庵法を同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_warm_electric" value="1" {{ old('duplicate_warm_electric') ? 'checked' : '' }}> 温庵法･電気光線器具を同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_manual_correction" value="1" {{ old('duplicate_manual_correction') ? 'checked' : '' }}> 変形徒手矯正術を同一内容で複製する</label>
          </div>
        </div>

        <!-- 施術者 -->
        <div class="mb-3">
          <label class="fw-semibold" for="therapist_id">施術者</label>
          @error('therapist_id')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <select id="therapist_id" name="therapist_id">
            <option value="">選択してください</option>
            @foreach($therapists as $therapist)
              <option value="{{ $therapist->id }}" {{ old('therapist_id', $record->therapist_id) == $therapist->id ? 'selected' : '' }}>{{ $therapist->id }}｜{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }}｜{{ $therapist->last_name_kana }}{{ "\u{2000}" }}{{ $therapist->first_name_kana }}</option>
            @endforeach
          </select>
        </div>

        <!-- 保険区分 -->
        <div class="mb-3">
          <label class="fw-semibold">保険区分</label>
          @error('insurance_category')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          @if($insurances && $insurances->count() > 0)
            <select name="insurance_category">
              <option value="">選択してください</option>
              @foreach($insurances as $insurance)
                @php
                  $insurerNumberLength = strlen($insurance->insurer_number ?? '');
                  $insuranceType = '';
                  if($insurerNumberLength == 6) {
                    $insuranceType = '国民健康保険';
                  } elseif($insurerNumberLength == 8) {
                    $insuranceType = '組合保険';
                  } else {
                    $insuranceType = '保険';
                  }
                  $expiryDate = $insurance->expiry_date ? date('Y/n/j', strtotime($insurance->expiry_date)) : '未設定';
                @endphp
                <option value="{{ $insurance->id }}" {{ old('insurance_category', $record->insurance_category) == $insurance->id ? 'selected' : '' }}>{{ $insuranceType }}(期限:{{ $expiryDate }})</option>
              @endforeach
            </select>
          @else
            <p class="text-secondary">保険情報が登録されていません</p>
          @endif
        </div>

        <!-- 同意有効期限 -->
        <div class="mb-3 d-flex">
          <label class="mb-1 fw-bold">同意有効期限</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div id="consent-expiry-display">
            <span id="consent-expiry-acupuncture" class="{{ $record->therapy_type == 1 ? '' : 'd-none' }}">
              @if($consentsAcupuncture && $consentsAcupuncture->consenting_end_date)
                {{ date('Y/n/j', strtotime($consentsAcupuncture->consenting_end_date)) }}
              @else
                未登録
              @endif
            </span>
            <span id="consent-expiry-massage" class="{{ $record->therapy_type == 2 ? '' : 'd-none' }}">
              @if($consentsMassage && $consentsMassage->consenting_end_date)
                {{ date('Y/n/j', strtotime($consentsMassage->consenting_end_date)) }}
              @else
                未登録
              @endif
            </span>
          </div>
          <input type="hidden" name="consent_expiry" id="consent_expiry" value="{{ old('consent_expiry', $record->consent_expiry) }}">
        </div>

        <!-- 請求区分 -->
        <div class="mb-3 d-flex">
          <label class="d-block mb-1 fw-bold">請求区分</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <p>{{ $hasRecentRecords ? '継続' : '新規' }}</p>
          <input type="hidden" name="bill_category_id" value="{{ old('bill_category_id', $record->bill_category_id) }}">
        </div>

        <!-- 施術実日数 -->
        <div class="mb-3 d-flex">
          <label class="d-block mb-1 fw-bold">施術実日数</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <p id="therapy-days-display">{{ count($originalDates) }}日</p>
        </div>

        <!-- 摘要 -->
        <div class="mb-3">
          <label for="abstract" class="d-block mb-1 fw-bold">摘要</label>
          <textarea id="abstract" name="abstract" rows="3" class="w-100">{{ old('abstract', $record->abstract) }}</textarea>
        </div>

        <button type="submit">登録</button>
      </div>
    </div>
  </form>

  @push('scripts')
  <script>
    // PHP変数をJavaScriptに渡す
    window.recordsConfig = {
      closedDays: @json($closedDays),
      selectedUserId: @json($record->clinic_user_id),
      oldInput: @json(session('_old_input', [])),
      errors: @json($errors->any()),
      originalDates: @json($originalDates),
      originalDistances: @json($originalDistances),
      userSearchUrl: ''
    };

    // 編集モードの場合、初期表示する年月を設定
    if (window.recordsConfig.originalDates.length > 0) {
      const firstDate = new Date(window.recordsConfig.originalDates[0]);
      window.recordsConfig.initialYear = firstDate.getFullYear();
      window.recordsConfig.initialMonth = firstDate.getMonth() + 1;
    } else {
      window.recordsConfig.initialYear = new Date().getFullYear();
      window.recordsConfig.initialMonth = new Date().getMonth() + 1;
    }
  </script>
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/records.js') }}"></script>
  @endpush
</x-app-layout>

