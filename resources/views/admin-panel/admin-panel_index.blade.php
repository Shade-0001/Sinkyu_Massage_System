<x-app-layout>
  @php
    $page_header_title = '管理画面';
  @endphp

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('admin-panel.index')"
  />


  <a href="#">システム管理ユーザー</a><br>
  <a href="{{ route('notices.index') }}">お知らせ</a><br>
  <a href="{{ route('prints.coordinate-adjuster') }}">PDFレイアウト調整ツール</a><br>
</x-app-layout>
