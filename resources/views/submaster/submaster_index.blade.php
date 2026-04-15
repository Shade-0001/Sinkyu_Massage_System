<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('submaster.index')"
  />

  <div class="row g-3 m-3">
    <div class="col-12">
      <a href="{{ route('submaster.medical-institutions') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-hospital fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">医療機関名（{{ $counts['medical_institutions'] }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('submaster.service-providers') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-building fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">サービス事業者名（{{ $counts['service_providers'] }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('submaster.conditions') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-clipboard_pulse fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">発病負傷経過（あんま・マッサージ）（{{ $counts['conditions'] }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('submaster.illnesses-massage') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-clipboard_plus fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">傷病名（あんま・マッサージ）（{{ $counts['illnesses_massage'] }}件）</div>
      </a>
    </div>
  </div>
</x-app-layout>
