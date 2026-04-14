<x-app-layout>
  @php
    $page_header_title = 'マスター登録';
  @endphp
  @section('title', $page_header_title)

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.index')"
  />
  
  
  <div class="row g-2">
    <div class="col-12"><a href="{{ route('clinic-users.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">利用者</a></div>
    <div class="col-12"><a href="{{ route('doctors.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">医師</a></div>
    <div class="col-12"><a href="{{ route('therapists.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">施術者</a></div>
    <div class="col-12"><a href="{{ route('caremanagers.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">ケアマネ</a></div>
    <div class="col-12"><a href="{{ route('clinic-info.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">自社情報</a></div>
    <div class="col-12"><a href="{{ route('master.documents.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">文面編集</a></div>
    <div class="col-12"><a href="{{ route('master.treatment-fees.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">施術料金</a></div>
    <div class="col-12"><a href="{{ route('master.self-fees.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">自費施術料金</a></div>
    <div class="col-12"><a href="{{ route('master.document-association.index') }}" class="btn-ex-main btn-ex-blue w-100 text-nowrap">登録済み標準文書の確認･関連付け</a></div>
  </div>
</x-app-layout>
