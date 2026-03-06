<x-app-layout>
  @php
    // モードに応じたパンくずリスト定義名を決定
    if ($mode === 'create') {
      $breadcrumbName = 'reports.create';
    } elseif ($mode === 'edit') {
      $breadcrumbName = 'reports.edit';
    } else { // duplicate
      $breadcrumbName = 'reports.duplicate';
    }
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumbName)"
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

  @if($mode === 'create')
    <form method="POST" action="{{ route('reports.store') }}">
  @elseif($mode === 'edit')
    <form method="POST" action="{{ route('reports.update', $report->id) }}">
      @method('PUT')
  @elseif($mode === 'duplicate')
    <form method="POST" action="{{ route('reports.duplicate.store') }}">
  @endif
    @csrf
    <input type="hidden" name="clinic_user_id" value="{{ $clinicUserId }}">

    <div class="mb-4">
      @if($mode === 'duplicate')
        <!-- 複製モードでは年月を変更可能 -->
        <div class="d-flex gap-3 align-items-center">
          <label class="fw-bold">複製先年月</label>
          <div class="vr ms-1 me-2" style="height: 1.4rem; position: relative; top: 0.3rem;"></div>
          <select id="duplicate-date-select" required>
              @php
                $currentDate = new DateTime();
                $maxDate = (clone $currentDate)->modify('+6 months');
                $maxYear = (int)$maxDate->format('Y');
                $maxMonth = (int)$maxDate->format('m');
              @endphp
              @for($y = $maxYear; $y >= 2000; $y--)
                @php
                  $startMonth = ($y == $maxYear) ? $maxMonth : 12;
                  $endMonth = ($y == 2000) ? 1 : 1;
                @endphp
                @for($m = $startMonth; $m >= $endMonth; $m--)
                  @php
                    $value = sprintf('%04d-%02d', $y, $m);
                    $isSelected = old('year', $year) == $y && old('month', $month) == $m;
                  @endphp
                  <option value="{{ $value }}" {{ $isSelected ? 'selected' : '' }}>{{ $y }} 年 {{ sprintf('%02d', $m) }}</option>
                @endfor
              @endfor
            </select>
          <input type="hidden" name="year" id="year" value="{{ old('year', $year) }}">
          <input type="hidden" name="month" id="month" value="{{ old('month', $month) }}">
        </div>
      @else
        <!-- 新規登録・編集モードでは年月を固定表示 -->
        <h5 class="fw-bold mb-3">{{ $year }}年 {{ sprintf('%02d', $month) }}月の報告書データ</h5>
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
      @endif
    </div>

    <div class="mb-3">
      <label for="subjective_symptom_and_wish" class="form-label fw-bold">主観症状</label><br>
      <textarea id="subjective_symptom_and_wish" name="subjective_symptom_and_wish" class="w-100" rows="5" maxlength="1000">{{ old('subjective_symptom_and_wish', $report->subjective_symptom_and_wish ?? '') }}</textarea>
    </div>

    <div class="mb-3">
      <label for="objective_symptom" class="form-label fw-bold">客観症状</label><br>
      <textarea id="objective_symptom" name="objective_symptom"  class="w-100" rows="5" maxlength="1000">{{ old('objective_symptom', $report->objective_symptom ?? '') }}</textarea>
    </div>

    <div class="mb-3">
      <label for="therapy_content" class="form-label fw-bold">施術内容</label><br>
      <textarea id="therapy_content" name="therapy_content" class="w-100" rows="5" maxlength="1000">{{ old('therapy_content', $report->therapy_content ?? '') }}</textarea>
    </div>

    <div class="mb-3">
      <label for="therapy_plan" class="form-label fw-bold">治療計画</label><br>
      <textarea id="therapy_plan" name="therapy_plan" class="w-100" rows="5" maxlength="1000">{{ old('therapy_plan', $report->therapy_plan ?? '') }}</textarea>
    </div>

    <div class="d-flex gap-2 mt-4">
      <button type="button" onclick="window.location.href='{{ route('reports.index', ['clinic_user_id' => $clinicUserId, 'scroll_year' => $year, 'scroll_month' => $month]) }}'">キャンセル</button>
      <button type="submit">登録</button>
    </div>
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/reports.js') }}"></script>
  @endpush
</x-app-layout>
