<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header :title="$page_header_title" :breadcrumbs="App\Support\Breadcrumbs::generate('records.index')" />

  <!-- 利用者選択フォーム -->
  <form method="GET" action="{{ route('records.index') }}" id="filterForm">
    <div class="mb-3">
      <label for="clinic_user_id" class="fs-5 fw-medium">利用者：</label>
      <select name="clinic_user_id" id="clinic_user_id" onchange="document.getElementById('filterForm').submit();">
        <option value="">╌╌╌</option>
        @foreach ($clinicUsers as $user)
          <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
            ID-{{ str_pad($user->id, $clinicUserIdLength, '0', STR_PAD_LEFT) }}｜{{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}｜{{ $user->last_kana }}{{ "\u{2000}" }}{{ $user->first_kana }}
          </option>
        @endforeach
      </select>
      <button type="button" onclick="openUserSearchPopup()" class="btn-ex-main btn-ex-sm btn-ex-blue mx-2">利用者検索</button>
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
    <!-- 実績登録フォーム -->
    <form id="recordForm" method="POST" action="{{ route('records.store') }}">
      @csrf
      <input type="hidden" name="clinic_user_id" value="{{ $selectedUserId }}">

      <div class="d-flex gap-3 align-items-start">
        <!-- カレンダー -->
        <div class="text-center position-relative flex-shrink-0 user-select-none" style="width: 300px;">
          <!-- カレンダーヘッダー -->
          <div class="d-flex align-items-stretch justify-content-center mb-3">
            <button type="button" id="prev-month-btn" class="btn-ex-sub btn-ex-blue" style="--btn-br-tl: 16px; --btn-br-tr: 0px; --btn-br-br: 0px; --btn-br-bl: 16px; padding-left: 12px; padding-right: 12px;">
              <i class="nf nf-fa-caret_left fs-2"></i>
            </button>
            <div class="btn-ex-sub btn-ex-blue rounded-0" style="font-size: 28px; padding-top: 12px; padding-bottom: 12px;">
              <div id="calendar-title-display"></div>
              <select id="calendar-title" class="position-absolute top-0 start-50 translate-middle-x opacity-0" style="border: none; background: transparent; width: 100%; height: 100%;"></select>
            </div>
            <button type="button" id="next-month-btn" class="btn-ex-sub btn-ex-blue" style="--btn-br-tl: 0px; --btn-br-tr: 16px; --btn-br-br: 16px; --btn-br-bl: 0px; padding-left: 12px; padding-right: 12px;">
              <i class="nf nf-fa-caret_right fs-2"></i>
            </button>
          </div>
          <!-- カレンダーボディ -->
          <div class="calendar" id="calendar">
            <!-- 曜日ヘッダー -->
            <div class="calendar-day-header text-center py-1 fw-bold sunday">日</div>
            <div class="calendar-day-header text-center py-1 fw-bold">月</div>
            <div class="calendar-day-header text-center py-1 fw-bold">火</div>
            <div class="calendar-day-header text-center py-1 fw-bold">水</div>
            <div class="calendar-day-header text-center py-1 fw-bold">木</div>
            <div class="calendar-day-header text-center py-1 fw-bold">金</div>
            <div class="calendar-day-header text-center py-1 fw-bold saturday">土</div>
          </div>
          <button type="button" id="clear-selection-btn" class="btn-ex-main btn-ex-sm mt-3">選択解除</button>
        </div>


        <div class="vr border border-black border-1 mx-3"></div>


        <!-- 実績フィールド -->
        <div class="flex-grow-1 row g-3 gx-3 align-content-start align-items-start" id="record-fields" style="max-width: 1000px;">
          <!-- 施術種類 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">施術種類
                @error('therapy_type')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <div>
                  <label><input type="radio" name="therapy_type" value="1" id="therapy_type_acupuncture" {{ old('therapy_type', '1') == '1' ? 'checked' : '' }} data-tooltip="先に日付を選択してください">はり･きゅう</label>
                  <label class="ms-3"><input type="radio" name="therapy_type" value="2" id="therapy_type_massage" {{ old('therapy_type') == '2' ? 'checked' : '' }} data-tooltip="先に日付を選択してください">あんま･マッサージ</label>
                </div>
                <!-- 身体部位チェックボックス(あんま･マッサージ選択時のみ表示) -->
                <div id="bodyparts-container" class="d-none mt-2 text-wrap">
                  <label><input type="checkbox" name="bodyparts[]" value="1" {{ in_array('1', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    軀幹</label>
                  <label><input type="checkbox" name="bodyparts[]" value="2" {{ in_array('2', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    右上肢</label>
                  <label><input type="checkbox" name="bodyparts[]" value="3" {{ in_array('3', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    左上肢</label>
                  <label><input type="checkbox" name="bodyparts[]" value="4" {{ in_array('4', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    右下肢</label>
                  <label><input type="checkbox" name="bodyparts[]" value="5" {{ in_array('5', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    左下肢</label>
                </div>
              </div>
            </div>
          </div>

          <!-- 施術区分 + 往療距離 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">施術区分
                @error('therapy_category')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <div>
                  <label><input type="radio" name="therapy_category" value="1" id="therapy_category_visit" {{ old('therapy_category') == '1' ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 通院</label>
                  <label class="ms-3"><input type="radio" name="therapy_category" value="2" id="therapy_category_housecall" {{ old('therapy_category') == '2' ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 往療</label>
                </div>
                <!-- 往療距離(往療選択時のみ表示) -->
                <div id="housecall-distance-section" class="d-none mt-2">
                  <label class="d-block mb-1 fw-bold">往療距離</label>
                  <p class="my-1 small text-secondary">往療料が発生する場合は往療距離を入力</p>
                  <div id="housecall-distance-inputs"></div>
                  <div class="mt-2">
                    上記日付を全て <input type="number" id="bulk-distance" step="0.5" min="0" style="width: 80px;" data-tooltip="先に日付を選択してください"> km に
                    <button type="button" id="apply-bulk-distance" data-tooltip="先に日付を選択してください">変更</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 開始時刻 & 終了時刻 -->
          @php
            $bhStart = $businessHoursStart ? (int) explode(':', $businessHoursStart)[0] : 0;
            $bhEnd = $businessHoursEnd ? (int) explode(':', $businessHoursEnd)[0] : 23;
            $oldStartH = old('start_time') ? (int) explode(':', old('start_time'))[0] : $bhStart;
            $oldStartM = old('start_time') ? (int) explode(':', old('start_time'))[1] : 0;
            $oldEndH = old('end_time') ? (int) explode(':', old('end_time'))[0] : $bhStart;
            $oldEndM = old('end_time') ? (int) explode(':', old('end_time'))[1] : 0;
          @endphp

          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">開始時刻
                @error('start_time')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
                <div class="time-select-group d-inline-flex align-items-center gap-1" data-target="start_time">
                  <select class="time-select-hour" data-tooltip="先に日付を選択してください">
                    @for ($h = $bhStart; $h <= $bhEnd; $h++)
                      <option value="{{ $h }}" {{ $oldStartH === $h ? 'selected' : '' }}>
                        {{ $h }}</option>
                    @endfor
                  </select>
                  <span>:</span>
                  <select class="time-select-minute" data-tooltip="先に日付を選択してください">
                    @foreach ([0, 10, 20, 30, 40, 50] as $m)
                      <option value="{{ $m }}" {{ $oldStartM === $m ? 'selected' : '' }}>
                        {{ sprintf('%02d', $m) }}</option>
                    @endforeach
                  </select>
                </div>
                <input type="hidden" id="start_time" name="start_time" value="{{ old('start_time') }}">
              </div>
            </div>
          </div>

          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">終了時刻
                @error('end_time')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
                <div class="time-select-group d-inline-flex align-items-center gap-1" data-target="end_time">
                  <select class="time-select-hour" data-tooltip="先に日付を選択してください">
                    @for ($h = $bhStart; $h <= $bhEnd; $h++)
                      <option value="{{ $h }}" {{ $oldEndH === $h ? 'selected' : '' }}>
                        {{ $h }}</option>
                    @endfor
                  </select>
                  <span>:</span>
                  <select class="time-select-minute" data-tooltip="先に日付を選択してください">
                    @foreach ([0, 10, 20, 30, 40, 50] as $m)
                      <option value="{{ $m }}" {{ $oldEndM === $m ? 'selected' : '' }}>
                        {{ sprintf('%02d', $m) }}</option>
                    @endforeach
                  </select>
                </div>
                <input type="hidden" id="end_time" name="end_time" value="{{ old('end_time') }}">
              </div>
            </div>
          </div>

          <!-- 施術内容 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="therapy_content_id">施術内容
                @error('therapy_content_id')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <select id="therapy_content_id" name="therapy_content_id" data-tooltip="先に日付を選択してください">
                  <option value="">╌╌╌</option>
                  @foreach ($therapyContents as $content)
                    <option value="{{ $content->id }}" data-therapy-type="{{ $content->therapy_type }}" {{ old('therapy_content_id') == $content->id ? 'selected' : '' }}>
                      {{ $content->therapy_content }}</option>
                  @endforeach
                  @foreach ($selfFees as $selfFee)
                    <option value="self_{{ $selfFee->id }}" data-therapy-type="self" {{ old('therapy_content_id') == 'self_' . $selfFee->id ? 'selected' : '' }}>
                      {{ $selfFee->self_fee_name }}</option>
                  @endforeach
                </select>

                <!-- 複製チェックボックス(あんま･マッサージ選択時のみ表示) -->
                <div id="therapy-content-duplication" class="d-none mt-2 text-wrap">
                  <label><input type="checkbox" name="duplicate_massage" value="1" {{ old('duplicate_massage') ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    マッサージを同一内容で複製する</label><br>
                  <label><input type="checkbox" name="duplicate_warm_compress" value="1" {{ old('duplicate_warm_compress') ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    温庵法を同一内容で複製する</label><br>
                  <label><input type="checkbox" name="duplicate_warm_electric" value="1" {{ old('duplicate_warm_electric') ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    温庵法･電気光線器具を同一内容で複製する</label><br>
                  <label><input type="checkbox" name="duplicate_manual_correction" value="1" {{ old('duplicate_manual_correction') ? 'checked' : '' }} data-tooltip="先に日付を選択してください">
                    変形徒手矯正術を同一内容で複製する</label>
                </div>
              </div>
            </div>
          </div>

          <!-- 施術者 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="therapist_id">施術者
                @error('therapist_id')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <select id="therapist_id" name="therapist_id" data-tooltip="先に日付を選択してください">
                  <option value="">╌╌╌</option>
                  @foreach ($therapists as $therapist)
                    <option value="{{ $therapist->id }}" {{ old('therapist_id') == $therapist->id ? 'selected' : '' }}>
                      ID-{{ str_pad($therapist->id, $therapistIdLength, '0', STR_PAD_LEFT) }}｜{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }}｜{{ $therapist->last_name_kana }}{{ "\u{2000}" }}{{ $therapist->first_name_kana }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <!-- 保険区分 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">保険区分
                @error('insurance_category')
                  <span class="text-danger ms-2">{{ $message }}</span>
                @enderror
              </label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                @if ($insurances && $insurances->count() > 0)
                  <select name="insurance_category" data-tooltip="先に日付を選択してください">
                    <option value="">╌╌╌</option>
                    @foreach ($insurances as $insurance)
                      @php
                        $insurerNumberLength = strlen($insurance->insurer_number ?? '');
                        $insuranceType = '';
                        if ($insurerNumberLength == 6) {
                            $insuranceType = '国民健康保険';
                        } elseif ($insurerNumberLength == 8) {
                            $insuranceType = '組合保険';
                        } else {
                            $insuranceType = '保険';
                        }
                        $expiryDate = $insurance->expiry_date ? date('Y/m/d', strtotime($insurance->expiry_date)) : '未設定';
                        $isSelected = old('insurance_category') ? old('insurance_category') == $insurance->id : $latestInsuranceId == $insurance->id;
                      @endphp
                      <option value="{{ $insurance->id }}" {{ $isSelected ? 'selected' : '' }}>
                        {{ $insuranceType }}（期限：{{ $expiryDate }}）</option>
                    @endforeach
                  </select>
                @else
                  <p class="text-secondary">保険情報が登録されていません</p>
                @endif
              </div>
            </div>
          </div>

          <!-- 同意有効期限 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">同意有効期限</label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <div id="consent-expiry-display">
                  <span id="consent-expiry-acupuncture" class="d-none">
                    @if ($consentsAcupuncture && $consentsAcupuncture->consenting_end_date)
                      {{ date('Y/m/d', strtotime($consentsAcupuncture->consenting_end_date)) }}
                    @else
                      未登録
                    @endif
                  </span>
                  <span id="consent-expiry-massage" class="d-none">
                    @if ($consentsMassage && $consentsMassage->consenting_end_date)
                      {{ date('Y/m/d', strtotime($consentsMassage->consenting_end_date)) }}
                    @else
                      未登録
                    @endif
                  </span>
                </div>
                <input type="hidden" name="consent_expiry" id="consent_expiry">
              </div>
            </div>
          </div>

          <!-- 請求区分 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">請求区分</label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
                <p class="mb-0">{{ $hasRecentRecords ? '継続' : '新規' }}</p>
                <input type="hidden" name="bill_category_id" value="{{ $hasRecentRecords ? 2 : 1 }}">
              </div>
            </div>
          </div>

          <!-- 施術実日数 -->
          <div class="col-12 col-xl-6">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3">施術実日数</label>
              <div class="vr align-self-stretch"></div>
              <div class="text-nowrap bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1 d-flex align-items-center">
                <p id="therapy-days-display" class="mb-0">0日</p>
              </div>
            </div>
          </div>

          <!-- 摘要 -->
          <div class="col-12">
            <div class="rounded-1 d-flex align-items-start overflow-hidden">
              <label class="fw-semibold text-nowrap bg-gray-100 align-self-stretch d-flex align-items-center p-2 px-3" for="abstract">摘要</label>
              <div class="vr align-self-stretch"></div>
              <div class="bg-gray-96 align-self-stretch p-2 px-3 flex-grow-1">
                <textarea id="abstract" name="abstract" rows="3" class="w-100" data-tooltip="先に日付を選択してください">{{ old('abstract') }}</textarea>
              </div>
            </div>
          </div>

          <div class="col-12">
            <button type="submit" class="btn-ex-main btn-ex-green d-block ms-auto" data-tooltip="先に日付を選択してください">登録</button>
          </div>
        </div>
      </div>
    </form>

    <hr class="m-0 mt-5 mb-3">



    <!-- 実績データ一覧テーブル -->
    @if ($selectedUserId)
      <div>
        <p class="mb-3">{{ $selectedYear }}年 {{ sprintf('%02d', $selectedMonth) }}月 の実績データ</p>

        @if ($records->count() > 0)
          <div class="mb-3">
            <button type="button" class="btn-ex-main btn-ex-blue" onclick="openRecordAcupunctureBenefitModal()">印刷｜はり･きゅう支給申請書</button>
            <button type="button" class="btn-ex-main btn-ex-blue" onclick="openRecordMassageBenefitModal()">印刷｜あんま･マッサージ支給申請書</button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered fw-medium small">
              <thead>
                <tr>
                  <th class="align-middle text-center" style="min-width: 50px;">施術内容 / 施術者 / 時刻</th>
                  <th class="align-middle text-center" style="min-width: 50px;">登録日時 / 更新日時</th>
                  <th colspan="{{ date('t', strtotime("$selectedYear-$selectedMonth-01")) }}" class="text-center">
                    施術日（通院：○｜往療：◎）</th>
                  <th class="align-middle text-center" style="max-width: 170px;">操作</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $daysInMonth = date('t', strtotime("$selectedYear-$selectedMonth-01"));
                @endphp
                @foreach ($records as $record)
                  <tr>
                    <td rowspan="3" class="align-middle">
                      {{ $record->therapy_content ?? ($record->self_fee_name ?? '未設定') }}<br>
                      {{ $record->therapist_name ?? '未設定' }}<br>
                      {{ $record->start_time ? date('H:i', strtotime($record->start_time)) : '--:--' }} ~
                      {{ $record->end_time ? date('H:i', strtotime($record->end_time)) : '--:--' }}
                    </td>
                    <td rowspan="3" class="align-middle">
                      {{ date('Y/m/d H:i', strtotime($record->created_at)) }}<br>
                      {{ date('Y/m/d H:i', strtotime($record->updated_at)) }}
                    </td>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                      <td class="p-0 text-center" style="height: 1.2rem">{{ $day }}</td>
                    @endfor
                    <td rowspan="3" class="align-middle">
                      <div class="d-grid gap-1" style="grid-template-columns: 1fr 1fr;">
                        <a href="{{ route('records.edit', $record->id) }}"><button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm w-100">編集</button></a>
                        <a href="{{ route('records.duplicate.current', $record->id) }}"><button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm w-100">当月へ複製</button></a>
                        <form method="POST" action="{{ route('records.destroy', $record->id) }}" onsubmit="return confirm('この実績データを削除してもよろしいですか？');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn-ex-main btn-ex-red btn-ex-sm w-100">削除</button>
                        </form>
                        <a href="{{ route('records.duplicate.next', $record->id) }}"><button type="button" class="btn-ex-main btn-ex-blue btn-ex-sm w-100">翌月へ複製</button></a>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                      @php
                        $date = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                        $dayOfWeek = date('w', strtotime($date));
                        $dayClass = '';
                        if ($dayOfWeek == 0) {
                            $dayClass = 'text-danger'; // 日曜日
                        } elseif ($dayOfWeek == 6) {
                            $dayClass = 'text-primary'; // 土曜日
                        }
                        $dayNames = ['日', '月', '火', '水', '木', '金', '土'];
                      @endphp
                      <td class="p-1 text-center {{ $dayClass }}" style="height: 1.2rem">
                        {{ $dayNames[$dayOfWeek] }}</td>
                    @endfor
                  </tr>
                  <tr>
                    @for ($day = 1; $day <= $daysInMonth; $day++)
                      @php
                        $currentDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                        $hasRecord = in_array($currentDate, $record->dates);
                        $mark = '';
                        if ($hasRecord) {
                            // 通院なら○、往療なら◎
                            $mark = $record->therapy_category == 1 ? '○' : '◎';
                        }
                      @endphp
                      <td class="p-0 text-center align-middle">{{ $mark }}</td>
                    @endfor
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            <form method="POST" action="{{ route('records.bulk.duplicate.next') }}" id="bulkDuplicateForm" onsubmit="return confirmBulkDuplicate();">
              @csrf
              <input type="hidden" name="clinic_user_id" value="{{ $selectedUserId }}">
              <input type="hidden" name="year" value="{{ $selectedYear }}">
              <input type="hidden" name="month" value="{{ $selectedMonth }}">
              <button type="submit" class="btn-ex-main btn-ex-blue">当月の全実績データを翌月へ複製</button>
            </form>
          </div>
        @else
          <div class="p-4 text-center fs-5 text-secondary">
            該当データなし
          </div>
        @endif
      </div>
    @endif
  @endif

  <!-- はり・きゅう支給申請書モーダル -->
  <div class="modal fade" id="recordAcupunctureBenefitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">はり･きゅう支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="recordAcupunctureBenefitForm" method="POST">
            @csrf
            <input type="hidden" name="clinic_user_ids[]" id="recordAcuBenefitUserId">
            <input type="hidden" name="service_year_month" id="recordAcuBenefitYearMonth">

            <!-- 出力年月日 -->
            <div class="mb-3">
              <label class="form-label">出力年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="submission_date" id="recordAcuBenefitSubmissionDate" value="{{ now()->format('Y-m-d') }}" required>
            </div>

            <!-- 施術報告交付料金 × 回数 -->
            <div class="mb-3">
              <label class="form-label">施術報告交付料金　　　回数</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" class="form-control" name="report_fee_unit" id="recordAcuBenefitReportFeeUnit" min="0" value="0" style="width: 100px;" onfocus="if(this.value==='0')this.value=''" onblur="if(this.value==='')this.value='0'">
                <span>円</span>
                <span class="mx-2">×</span>
                <input type="number" class="form-control" name="report_fee_count" id="recordAcuBenefitReportFeeCount" min="0" value="0" style="width: 80px;" onfocus="if(this.value==='0')this.value=''" onblur="if(this.value==='')this.value='0'">
                <span>回</span>
              </div>
            </div>

            <!-- 前回年月 -->
            <div class="mb-3">
              <label class="form-label">前回年月</label>
              <select class="form-select" name="previous_year_month" id="recordAcuBenefitPreviousYearMonth">
                <option value="">選択してください</option>
                @php
                  for ($i = 0; $i < 24; $i++) {
                      $date = now()->copy()->subMonths($i);
                      $value = $date->format('Y-m');
                      $m = (int) $date->format('n');
                      $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                      echo "<option value=\"{$value}\">{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 被保険者名署名欄を空白 -->
            <div class="mb-3">
              <label>
                <input type="checkbox" name="blank_insured_name" value="1" id="recordAcuBenefitBlankInsuredName">
                被保険者名署名欄を空白にする
              </label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitRecordAcupunctureBenefit()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  <!-- あんま・マッサージ支給申請書モーダル -->
  <div class="modal fade" id="recordMassageBenefitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">あんま･マッサージ支給申請書 出力設定</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="recordMassageBenefitForm" method="POST">
            @csrf
            <input type="hidden" name="clinic_user_ids[]" id="recordMsgBenefitUserId">
            <input type="hidden" name="service_year_month" id="recordMsgBenefitYearMonth">

            <!-- 出力年月日 -->
            <div class="mb-3">
              <label class="form-label">出力年月日 <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="submission_date" id="recordMsgBenefitSubmissionDate" required>
            </div>

            <!-- 施術報告交付料金 × 回数 -->
            <div class="mb-3">
              <label class="form-label">施術報告交付料金 × 回数</label>
              <div class="d-flex align-items-center gap-2">
                <input type="number" class="form-control" name="report_fee_unit" id="recordMsgBenefitReportFeeUnit" min="0" value="0" style="width: 100px;" onfocus="if(this.value==='0')this.value=''" onblur="if(this.value==='')this.value='0'">
                <span>円 ×</span>
                <input type="number" class="form-control" name="report_fee_count" id="recordMsgBenefitReportFeeCount" min="0" value="0" style="width: 80px;" onfocus="if(this.value==='0')this.value=''" onblur="if(this.value==='')this.value='0'">
                <span>回</span>
              </div>
            </div>

            <!-- 前回年月 -->
            <div class="mb-3">
              <label class="form-label">前回年月</label>
              <select class="form-select" name="previous_year_month" id="recordMsgBenefitPreviousYearMonth">
                <option value="">選択してください</option>
                @php
                  for ($i = 0; $i < 24; $i++) {
                      $date = now()->copy()->subMonths($i);
                      $value = $date->format('Y-m');
                      $m = (int) $date->format('n');
                      $display = $date->format('Y年') . ($m < 10 ? "\u{00A0}\u{00A0}" : '') . $m . '月';
                      echo "<option value=\"{$value}\">{$display}</option>";
                  }
                @endphp
              </select>
            </div>

            <!-- 被保険者名署名欄を空白 -->
            <div class="mb-3">
              <label>
                <input type="checkbox" name="blank_insured_name" value="1" id="recordMsgBenefitBlankInsuredName">
                被保険者名署名欄を空白にする
              </label>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
          <button type="button" class="btn btn-primary" onclick="submitRecordMassageBenefit()">印刷</button>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      // PHP変数をJavaScriptに渡す
      window.recordsConfig = {
        closedDays: @json($closedDays),
        selectedUserId: @json($selectedUserId),
        oldInput: @json(session('_old_input', [])),
        errors: @json($errors->any()),
        initialYear: @json($selectedYear),
        initialMonth: @json($selectedMonth),
        userSearchUrl: '{{ route('user.search') }}'
      };

      // 一括複製の確認ダイアログ
      function confirmBulkDuplicate() {
        const year = document.querySelector('input[name="year"]').value;
        const month = document.querySelector('input[name="month"]').value;
        const nextMonth = parseInt(month) === 12 ? 1 : parseInt(month) + 1;
        const nextYear = parseInt(month) === 12 ? parseInt(year) + 1 : year;

        return confirm(`${year}年${month}月の全実績データを${nextYear}年${nextMonth}月へ複製してもよろしいですか？`);
      }
    </script>
    <script src="{{ asset('js/utility.js') }}"></script>
    <script src="{{ asset('js/records.js') }}"></script>
  @endpush
</x-app-layout>
