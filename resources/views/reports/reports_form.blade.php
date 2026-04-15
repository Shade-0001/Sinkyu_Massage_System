<x-app-layout>
  @section('title', $page_header_title)
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

    <div class="d-flex flex-column gap-4 container ms-0">

      {{-- 複製先年月 / 年月表示 --}}
      <div class="mb-2">
        @if($mode === 'duplicate')
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
                    <option value="{{ $value }}" {{ $isSelected ? 'selected' : '' }}>{{ $y }} 年 {{ sprintf('%02d', $m) }}</option>
                  @endfor
                @endfor
              </select>
            <input type="hidden" name="year" id="year" value="{{ old('year', $year) }}">
            <input type="hidden" name="month" id="month" value="{{ old('month', $month) }}">
          </div>
        @else
          <h5 class="fw-bold mb-3">{{ $year }}年 {{ sprintf('%02d', $month) }}月の報告書データ</h5>
          <input type="hidden" name="year" value="{{ $year }}">
          <input type="hidden" name="month" value="{{ $month }}">
        @endif
      </div>

      {{-- 主観症状 --}}
      <div>
        <label class="form-label-tab" for="subjective_symptom_and_wish">主観症状</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <textarea id="subjective_symptom_and_wish" name="subjective_symptom_and_wish" class="w-100" rows="5" maxlength="1000">{{ old('subjective_symptom_and_wish', $report->subjective_symptom_and_wish ?? '') }}</textarea>
        </div>
      </div>

      {{-- 客観症状 --}}
      <div>
        <label class="form-label-tab" for="objective_symptom">客観症状</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <textarea id="objective_symptom" name="objective_symptom" class="w-100" rows="5" maxlength="1000">{{ old('objective_symptom', $report->objective_symptom ?? '') }}</textarea>
        </div>
      </div>

      {{-- 施術内容 --}}
      <div>
        <label class="form-label-tab" for="therapy_content">施術内容</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <textarea id="therapy_content" name="therapy_content" class="w-100" rows="5" maxlength="1000">{{ old('therapy_content', $report->therapy_content ?? '') }}</textarea>
        </div>
      </div>

      {{-- 治療計画 --}}
      <div>
        <label class="form-label-tab" for="therapy_plan">治療計画</label>
        <div class="form-field px-3 py-2">
          <div class="form-field-top"></div>
          <textarea id="therapy_plan" name="therapy_plan" class="w-100" rows="5" maxlength="1000">{{ old('therapy_plan', $report->therapy_plan ?? '') }}</textarea>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2 justify-content-end">
        <a href="{{ route('reports.index', ['clinic_user_id' => $clinicUserId, 'scroll_year' => $year, 'scroll_month' => $month]) }}" class="btn-ex-main btn-ex-gray"><i class="nf nf-fa-caret_left me-1" style="transform: scale(1.2)"></i>戻る</a>
        <button type="submit" class="btn-ex-main btn-ex-green">登録</button>
      </div>

    </div>
  </form>

  @push('scripts')
  <script src="{{ asset('js/utility.js') }}"></script>
  <script src="{{ asset('js/reports.js') }}"></script>
  @endpush
</x-app-layout>
