<x-app-layout>
  @php
    $page_header_title = 'マスター登録';
  @endphp
  @section('title', $page_header_title)

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.index')"
  />
  
  
  <div class="row g-3 m-3">
    <div class="col-12">
      <a href="{{ route('clinic-users.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-user fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">利用者（{{ $clinicUserCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('doctors.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-user_doctor fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">医師（{{ $doctorCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('therapists.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-user_nurse fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">施術者（{{ $therapistCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('caremanagers.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-account_tie fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">ケアマネ（{{ $careManagerCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('clinic-info.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-building fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">自社情報</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.documents.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-file_document fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">文面編集</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.treatment-fees.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-yen fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">施術料金</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.self-fees.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-yen fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">自費施術料金</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.document-association.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-oct-link fs-6 fw-bold m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-3">登録済み標準文書の確認･関連付け</div>
      </a>
    </div>
  </div>
</x-app-layout>
