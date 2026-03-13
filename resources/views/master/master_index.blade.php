<x-app-layout>
  @php
    $page_header_title = 'マスター登録';
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('master.index')"
  />

  <br>

  ・<a href="{{ route('clinic-users.index') }}">利用者</a><br>
  ・<a href="{{ route('doctors.index') }}">医師</a><br>
  ・<a href="{{ route('therapists.index') }}">施術者</a><br>
  ・<a href="{{ route('caremanagers.index') }}">ケアマネ</a><br>
  ・<a href="{{ route('clinic-info.index') }}">自社情報</a><br>
  ・<a href="{{ route('master.documents.index') }}">文面編集</a><br>
  ・<a href="{{ route('master.treatment-fees.index') }}">施術料金</a><br>
  ・<a href="{{ route('master.self-fees.index') }}">自費施術料金</a><br>
  ・<a href="{{ route('master.document-association.index') }}">登録済み標準文書の確認･関連付け</a>

  <br><br>
  <button type="submit" class="btn-custom btn-custom-blue w-25">ボタン</button>
</x-app-layout>
