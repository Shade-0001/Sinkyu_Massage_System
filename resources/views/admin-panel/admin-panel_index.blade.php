<x-app-layout>
  @php
    $page_header_title = 'システム管理';
  @endphp
  @section('title', $page_header_title)

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('admin-panel.index')"
  />


  <a href="{{ route('system-users.index') }}">システムユーザー</a><br>
  <a href="{{ route('notices.index') }}">お知らせ</a><br>
  <a href="{{ route('prints.coordinate-adjuster') }}">PDFレイアウト調整ツール</a><br>
</x-app-layout>
