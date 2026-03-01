<!-- resources/views/registration-review.blade.php -->


<x-app-layout>
  @if(isset($breadcrumb_name))
    <x-page-header
      :title="$page_header_title"
      :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumb_name)"
    />
  @endif

  {{-- <h5>{{ $registration_message }}</h5> --}}
  <h5 class="mb-4">以下の内容で登録を行います。</h5>

  <div>
  @foreach($labels as $key => $label)
    {{-- データに存在するキーのみ表示 --}}
    @if(array_key_exists($key, $data))
    <div class="mb-3">
    <div class="fw-semibold">{{ $label }}</div>
    <div>
      @if(isset($data[$key]) && $data[$key] !== null && $data[$key] !== '')
      @if($key === 'gender_id')
        {{ $data[$key] == 1 ? '男性' : ($data[$key] == 2 ? '女性' : '') }}
      @elseif($key === 'is_redeemed' || $key === 'reimbursement_target' || $key === 'is_healthcare_subsidized')
        {{ $data[$key] ? '対象' : '非対象' }}
      @elseif($key === 'is_housecall_required')
        {{ $data[$key] == 1 ? '必要とする' : ($data[$key] == 0 ? '必要としない' : '') }}
      @elseif($key === 'symptom2_joint_disorder' || $key === 'symptom2_other' || $key === 'symptom3_other' || $key === 'treatment_type2_corrective_hand')
        {{ $data[$key] ? 'あり' : 'なし' }}
      @elseif(is_array($data[$key]))
        {{ implode('、', $data[$key]) }}
      @elseif(in_array($key, ['birthday', 'qualification_date', 'certification_date', 'issue_date', 'expiration_date', 'license_acquisition_date', 'expiry_date', 'consenting_date', 'consenting_start_date', 'consenting_end_date', 'benefit_period_start_date', 'benefit_period_end_date', 'first_care_date', 'reconsenting_expiry', 'onset_and_injury_date']))
        @php
          $dateValue = $data[$key];
          if (is_object($dateValue) && method_exists($dateValue, 'format')) {
            echo $dateValue->format('Y年n月j日');
          } elseif (is_string($dateValue) && $dateValue !== '') {
            echo date('Y年n月j日', strtotime($dateValue));
          } else {
            echo $dateValue;
          }
        @endphp
      @else
        {{ $data[$key] }}
      @endif
      @else
      &nbsp;
      @endif
    </div>
    </div>
    @endif
  @endforeach
  </div>

  <br>

  @if(isset($back_insurance_id))
  <a href="{{ route($back_route, ['id' => $back_id, 'insurance_id' => $back_insurance_id]) }}">
    <button class="me-3">◀ 戻る</button>
  </a>
  @elseif(isset($back_plan_id))
  <a href="{{ route($back_route, ['id' => $back_id, 'plan_id' => $back_plan_id]) }}">
    <button class="me-3">◀ 戻る</button>
  </a>
  @elseif(isset($back_history_id))
  <a href="{{ route($back_route, ['id' => $back_id, 'history_id' => $back_history_id]) }}">
    <button class="me-3">◀ 戻る</button>
  </a>
  @elseif(isset($back_id))
  <a href="{{ route($back_route, ['id' => $back_id]) }}">
    <button class="me-3">◀ 戻る</button>
  </a>
  @else
  <form action="{{ route($back_route) }}" method="GET" class="d-inline-block">
    <button type="submit" class="me-3">◀ 戻る</button>
  </form>
  @endif

  @if(isset($back_insurance_id))
  <form action="{{ route($store_route, ['id' => $back_id, 'insurance_id' => $back_insurance_id]) }}" method="POST" class="d-inline-block">
  @elseif(isset($back_plan_id))
  <form action="{{ route($store_route, ['id' => $back_id, 'plan_id' => $back_plan_id]) }}" method="POST" class="d-inline-block">
  @elseif(isset($back_history_id))
  <form action="{{ route($store_route, ['id' => $back_id, 'history_id' => $back_history_id]) }}" method="POST" class="d-inline-block">
  @elseif(isset($back_id))
  <form action="{{ route($store_route, ['id' => $back_id]) }}" method="POST" class="d-inline-block">
  @else
  <form action="{{ route($store_route) }}" method="POST" class="d-inline-block">
  @endif
  @csrf
  <button type="submit" class="me-3">登録</button>
  </form>
</x-app-layout>