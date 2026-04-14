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
    <div class="col-12"><a href="{{ route('clinic-users.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-users"></i> <span class="vr"></span> 利用者（{{ $clinicUserCount }}件）</a></div>
    <div class="col-12"><a href="{{ route('doctors.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-stethoscope"></i> <span class="vr"></span> 医師（{{ $doctorCount }}件）</a></div>
    <div class="col-12"><a href="{{ route('therapists.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-md-hand_heart"></i> <span class="vr"></span> 施術者（{{ $therapistCount }}件）</a></div>
    <div class="col-12"><a href="{{ route('caremanagers.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-id_card"></i> <span class="vr"></span> ケアマネ（{{ $careManagerCount }}件）</a></div>
    <div class="col-12"><a href="{{ route('clinic-info.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-building"></i> <span class="vr"></span> 自社情報</a></div>
    <div class="col-12"><a href="{{ route('master.documents.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-file_text"></i> <span class="vr"></span> 文面編集</a></div>
    <div class="col-12"><a href="{{ route('master.treatment-fees.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-yen"></i> <span class="vr"></span> 施術料金</a></div>
    <div class="col-12"><a href="{{ route('master.self-fees.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap btn-ex-rounded-full"><i class="nf nf-fa-credit_card"></i> <span class="vr"></span> 自費施術料金</a></div>
    <div class="col-12"><a href="{{ route('master.document-association.index') }}" class="btn-ex-main btn-ex-blue w-100 btn-ex-rounded-full btn-ex-text-wrap"><i class="nf nf-fa-link"></i> <span class="vr"></span> 登録済み標準文書の確認･関連付け</a></div>
  </div>
</x-app-layout>
