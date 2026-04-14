<x-app-layout>
  @php
    $page_header_title = 'マスター登録';
  @endphp
  @section('title', $page_header_title)

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.index')"
  />
  
  
  <div class="row g-3 m-3" style="max-width: 30%;">
    <div class="col-12">
      <a href="{{ route('clinic-users.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-user"></i>
        </div>
        <div>利用者（{{ $clinicUserCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('doctors.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-stethoscope"></i>
        </div>
        <div>医師（{{ $doctorCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('therapists.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-md-hand_heart"></i>
        </div>
        <div>施術者（{{ $therapistCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('caremanagers.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-id_card"></i>
        </div>
        <div>ケアマネ（{{ $careManagerCount }}件）</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('clinic-info.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-building"></i>
        </div>
        <div>自社情報</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.documents.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-file_text"></i>
        </div>
        <div>文面編集</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.treatment-fees.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-yen"></i>
        </div>
        <div>施術料金</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.self-fees.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-credit_card"></i>
        </div>
        <div>自費施術料金</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('master.document-association.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full btn-ex-text-wrap">
        <div class="btn-ex-skin btn-ex-white btn-ex-rounded-full">
          <i class="nf nf-fa-link"></i>
        </div>
        <div>登録済み標準文書の確認･関連付け</div>
      </a>
    </div>
  </div>
</x-app-layout>
