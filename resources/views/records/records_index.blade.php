<x-app-layout>
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('records.index')"
  />

  <!-- 利用者選択フォーム -->
  <form method="GET" action="{{ route('records.index') }}" id="filterForm">
    <div class="mb-3">
      <label for="clinic_user_id">利用者氏名：</label>
      <select name="clinic_user_id" id="clinic_user_id" onchange="document.getElementById('filterForm').submit();">
        <option value="">╌╌╌</option>
        @foreach($clinicUsers as $user)
          <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
            {{ $user->last_name }}{{ "\u{2000}" }}{{ $user->first_name }}（{{ $user->last_kana }}{{ "\u{2000}" }}{{ $user->first_kana }}）
          </option>
        @endforeach
      </select>
      <button type="button" onclick="openUserSearchPopup()" class="btn-custom btn-custom-sm btn-custom-blue mx-2">利用者検索</button>
    </div>
  </form>
  <br>

  @if(session('success'))
  <div class="alert alert-success">
    {{ session('success') }}
  </div>
  @endif

  @if($errors->any())
  <div class="alert alert-danger">
    <ul>
    @foreach($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
    </ul>
  </div>
  @endif

  @if(!$selectedUserId)
    <div class="p-4 text-center fs-5 text-secondary">
      利用者を選択してください
    </div>
  @else
  <form id="recordForm" method="POST" action="{{ route('records.store') }}">
    @csrf
    <input type="hidden" name="clinic_user_id" value="{{ $selectedUserId }}">

    <div class="d-flex gap-3 align-items-start">
      <!-- カレンダー -->
      <div class="text-center position-relative" style="width: fit-content; min-width: 15rem;">
        <div class="d-flex align-items-stretch justify-content-center mb-3">
          <button type="button" id="prev-month-btn" class="btn-custom btn-custom-sub btn-custom-blue" style="--btn-br-tl: 16px; --btn-br-tr: 0px; --btn-br-br: 0px; --btn-br-bl: 16px;"><i class="nf nf-fa-angle_left fs-4"></i></button>
          <div class="btn-custom btn-custom-sub btn-custom-blue rounded-0 fs-4">
            <div id="calendar-title-display" class="px-3"></div>
            <select id="calendar-title" class="position-absolute top-0 start-50 translate-middle-x opacity-0" style="font-size: 1.5rem; padding: 0.2em 0em; border: none; background: transparent; width: 100%; height: 100%;"></select>
          </div>
          <button type="button" id="next-month-btn" class="btn-custom btn-custom-sub btn-custom-blue" style="--btn-br-tl: 0px; --btn-br-tr: 16px; --btn-br-br: 16px; --btn-br-bl: 0px;"><i class="nf nf-fa-angle_right fs-4"></i></button>
        </div>
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
        <button type="button" id="clear-selection-btn" class="btn-custom btn-custom-sm mt-3">選択解除</button>
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
            <label><input type="radio" name="therapy_type" value="1" id="therapy_type_acupuncture" {{ old('therapy_type', '1') == '1' ? 'checked' : '' }} data-tooltip="先に日付を選択してください">はり･きゅう</label>
            <label class="ms-3"><input type="radio" name="therapy_type" value="2" id="therapy_type_massage" {{ old('therapy_type') == '2' ? 'checked' : '' }} data-tooltip="先に日付を選択してください">あんま･マッサージ</label>
          </div>
        </div>
        <div class="mb-3">
          <!-- 身体部位チェックボックス(あんま･マッサージ選択時のみ表示) -->
          <div id="bodyparts-section" class="d-none">
            <label class="fw-semibold">　　部位</label>
            <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
            <label><input type="checkbox" name="bodyparts[]" value="1" {{ in_array('1', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 軀幹</label>
            <label><input type="checkbox" name="bodyparts[]" value="2" {{ in_array('2', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 右上肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="3" {{ in_array('3', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 左上肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="4" {{ in_array('4', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 右下肢</label>
            <label><input type="checkbox" name="bodyparts[]" value="5" {{ in_array('5', old('bodyparts', [])) ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 左下肢</label>
            </div>
        </div>

        <!-- 施術区分 -->
        <div class="mb-3">
          <label class="fw-semibold">施術区分</label>
          @error('therapy_category')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <label><input type="radio" name="therapy_category" value="1" id="therapy_category_visit" {{ old('therapy_category') == '1' ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 通院</label>
          <label class="ms-3"><input type="radio" name="therapy_category" value="2" id="therapy_category_housecall" {{ old('therapy_category') == '2' ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 往療</label>
        </div>

        <!-- 往療距離(往療選択時のみ表示) -->
        <div id="housecall-distance-section" class="d-none mb-3">
          <label class="d-block mb-1 fw-bold">往療距離</label>
          <p class="my-1 small text-secondary">往療料が発生する場合は往療距離を入力(往療料無しなら0を入力)</p>
          <div id="housecall-distance-inputs"></div>
          <div class="mt-2">
            上記日付を全て <input type="number" id="bulk-distance" step="0.5" min="0" style="width: 80px;" data-tooltip="先に日付を選択してください"> km に
            <button type="button" id="apply-bulk-distance" data-tooltip="先に日付を選択してください">変更</button>
          </div>
        </div>

        <!-- 開始時刻 & 終了時刻 -->
        <div class="mb-3">
          <label class="fw-semibold">開始時刻</label>
          @error('start_time')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div class="time-picker-wrapper" id="start-time-picker" data-tooltip="先に日付を選択してください"></div>
          <input type="hidden" id="start_time" name="start_time" value="{{ old('start_time') }}">
        </div>

        <div class="mb-3">
          <label class="fw-semibold">終了時刻</label>
          @error('end_time')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <div class="time-picker-wrapper" id="end-time-picker" data-tooltip="先に日付を選択してください"></div>
          <input type="hidden" id="end_time" name="end_time" value="{{ old('end_time') }}">
        </div>

        <!-- 施術内容 -->
        <div class="mb-3">
          <label class="fw-semibold" for="therapy_content_id">施術内容</label>
          @error('therapy_content_id')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <select id="therapy_content_id" name="therapy_content_id" data-tooltip="先に日付を選択してください">
            <option value="">╌╌╌</option>
            @foreach($therapyContents as $content)
              <option value="{{ $content->id }}" data-therapy-type="{{ $content->therapy_type }}" {{ old('therapy_content_id') == $content->id ? 'selected' : '' }}>{{ $content->therapy_content }}</option>
            @endforeach
            @foreach($selfFees as $selfFee)
              <option value="self_{{ $selfFee->id }}" data-therapy-type="self" {{ old('therapy_content_id') == 'self_'.$selfFee->id ? 'selected' : '' }}>{{ $selfFee->self_fee_name }}</option>
            @endforeach
          </select>

          <!-- 複製チェックボックス(あんま･マッサージ選択時のみ表示) -->
          <div id="therapy-content-duplication" class="d-none mt-2 ms-3">
            <label><input type="checkbox" name="duplicate_massage" value="1" {{ old('duplicate_massage') ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> マッサージを同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_warm_compress" value="1" {{ old('duplicate_warm_compress') ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 温庵法を同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_warm_electric" value="1" {{ old('duplicate_warm_electric') ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 温庵法･電気光線器具を同一内容で複製する</label><br>
            <label><input type="checkbox" name="duplicate_manual_correction" value="1" {{ old('duplicate_manual_correction') ? 'checked' : '' }} data-tooltip="先に日付を選択してください"> 変形徒手矯正術を同一内容で複製する</label>
          </div>
        </div>

        <!-- 施術者 -->
        <div class="mb-3">
          <label class="fw-semibold" for="therapist_id">施術者</label>
          @error('therapist_id')
            <span class="text-danger ms-2">{{ $message }}</span>
          @enderror
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <select id="therapist_id" name="therapist_id" data-tooltip="先に日付を選択してください">
            <option value="">╌╌╌</option>
            @foreach($therapists as $therapist)
              <option value="{{ $therapist->id }}" {{ old('therapist_id') == $therapist->id ? 'selected' : '' }}>{{ $therapist->last_name }}{{ "\u{2000}" }}{{ $therapist->first_name }} @if($therapist->last_name_kana)({{ $therapist->last_name_kana }}{{ "\u{2000}" }}{{ $therapist->first_name_kana }})@endif</option>
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
            <select name="insurance_category" data-tooltip="先に日付を選択してください">
              <option value="">╌╌╌</option>
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
                  $expiryDate = $insurance->expiry_date ? date('Y/m/d', strtotime($insurance->expiry_date)) : '未設定';
                  $isSelected = old('insurance_category') ? old('insurance_category') == $insurance->id : $latestInsuranceId == $insurance->id;
                @endphp
                <option value="{{ $insurance->id }}" {{ $isSelected ? 'selected' : '' }}>{{ $insuranceType }}（期限：{{ $expiryDate }}）</option>
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
            <span id="consent-expiry-acupuncture" class="d-none">
              @if($consentsAcupuncture && $consentsAcupuncture->consenting_end_date)
                {{ date('Y/m/d', strtotime($consentsAcupuncture->consenting_end_date)) }}
              @else
                未登録
              @endif
            </span>
            <span id="consent-expiry-massage" class="d-none">
              @if($consentsMassage && $consentsMassage->consenting_end_date)
                {{ date('Y/m/d', strtotime($consentsMassage->consenting_end_date)) }}
              @else
                未登録
              @endif
            </span>
          </div>
          <input type="hidden" name="consent_expiry" id="consent_expiry">
        </div>

        <!-- 請求区分 -->
        <div class="mb-3 d-flex">
          <label class="d-block mb-1 fw-bold">請求区分</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <p>{{ $hasRecentRecords ? '継続' : '新規' }}</p>
          <input type="hidden" name="bill_category_id" value="{{ $hasRecentRecords ? 2 : 1 }}">
        </div>

        <!-- 施術実日数 -->
        <div class="mb-3 d-flex">
          <label class="d-block mb-1 fw-bold">施術実日数</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <p id="therapy-days-display">0日</p>
        </div>

        <!-- 摘要 -->
        <div class="mb-3">
          <label for="abstract" class="d-block mb-1 fw-bold">摘要</label>
          <textarea id="abstract" name="abstract" rows="3" class="w-100" data-tooltip="先に日付を選択してください">{{ old('abstract') }}</textarea>
        </div>

        <button type="submit" data-tooltip="先に日付を選択してください">登録</button>
      </div>
    </div>
  </form>

  <hr class="m-0 mt-5 mb-3">



  <!-- 実績データ一覧テーブル -->
  @if($selectedUserId)
    <div>
      <p class="mb-3">{{ $selectedYear }}年 {{ sprintf('%02d', $selectedMonth) }}月 の実績データ</p>

      @if($records->count() > 0)
        <div class="mb-3">
          <button type="button" onclick="openRecordAcupunctureBenefitModal()">はり･きゅう支給申請書印刷</button>
          <button type="button" onclick="openRecordMassageBenefitModal()">あんま･マッサージ支給申請書印刷</button>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered fw-medium" style="font-size: 0.7rem;">
            <thead>
              <tr>
                <th class="align-middle text-center" style="min-width: 90px;">[ 編集 ]</th>
                <th class="align-middle text-center" style="min-width: 50px;">施術内容 / 施術者 / 時刻</th>
                <th class="align-middle text-center" style="min-width: 50px;">登録日時 / 更新日時</th>
                <th colspan="{{ date('t', strtotime("$selectedYear-$selectedMonth-01")) }}" class="text-center">施術日（通院：○｜往療：◎）</th>
              </tr>
            </thead>
            <tbody>
              @php
                $daysInMonth = date('t', strtotime("$selectedYear-$selectedMonth-01"));
              @endphp
              @foreach($records as $record)
                <tr>
                  <td rowspan="3" class="align-middle">
                    <a href="{{ route('records.edit', $record->id) }}"><button type="button">編集</button></a><br>
                    <a href="{{ route('records.duplicate.current', $record->id) }}"><button type="button">当月へ複製</button></a><br>
                    <a href="{{ route('records.duplicate.next', $record->id) }}"><button type="button">翌月へ複製</button></a><br>
                    <form method="POST" action="{{ route('records.destroy', $record->id) }}" style="display:inline;" onsubmit="return confirm('この実績データを削除してもよろしいですか？');">
                      @csrf
                      @method('DELETE')
                      <button type="submit">削除</button>
                    </form>
                  </td>
                  <td rowspan="3" class="align-middle">
                    {{ $record->therapy_content ?? $record->self_fee_name ?? '未設定' }}<br>
                    {{ $record->therapist_name ?? '未設定' }}<br>
                    {{ $record->start_time ? date('H:i', strtotime($record->start_time)) : '--:--' }} ~ {{ $record->end_time ? date('H:i', strtotime($record->end_time)) : '--:--' }}
                  </td>
                  <td rowspan="3" class="align-middle">
                    {{ date('Y/m/d H:i', strtotime($record->created_at)) }}<br>
                    {{ date('Y/m/d H:i', strtotime($record->updated_at)) }}
                  </td>
                  @for($day = 1; $day <= $daysInMonth; $day++)
                    <td class="p-0 text-center" style="height: 1.2rem">{{ $day }}</td>
                  @endfor
                </tr>
                <tr>
                  @for($day = 1; $day <= $daysInMonth; $day++)
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
                    <td class="p-1 text-center {{ $dayClass }}" style="height: 1.2rem">{{ $dayNames[$dayOfWeek] }}</td>
                  @endfor
                </tr>
                <tr>
                  @for($day = 1; $day <= $daysInMonth; $day++)
                    @php
                      $currentDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                      $hasRecord = in_array($currentDate, $record->dates);
                      $mark = '';
                      if ($hasRecord) {
                        // 通院なら○、往療なら◎
                        $mark = $record->therapy_category == 1 ? '○' : '◎';
                      }
                    @endphp
                    <td class="p-0 text-center">{{ $mark }}</td>
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
            <button type="submit">当月の全実績データを翌月へ複製</button>
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
                    $m = (int)$date->format('n');
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
                    $m = (int)$date->format('n');
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
      userSearchUrl: '{{ route("user.search") }}'
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
