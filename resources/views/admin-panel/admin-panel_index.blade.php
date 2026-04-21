<x-app-layout>
  @php
    $page_header_title = 'システム管理';
  @endphp
  @section('title', $page_header_title)

  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('admin-panel.index')"
  />


  <div class="row g-3 m-3">
    <div class="col-12">
      <a href="{{ route('system-users.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-user fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-4">ユーザーアカウント</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('notices.index') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-fa-bell fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-4">お知らせ</div>
      </a>
    </div>
    <div class="col-12">
      <a href="{{ route('admin-panel.coordinate-adjuster') }}" class="btn-ex-main btn-ex-blue btn-ex-rounded-full">
        <div class="btn-ex-skin btn-ex-white btn-ex-xs btn-ex-rounded-full aspect-square m-n05 p-3">
          <i class="nf nf-md-view_dashboard fs-6 m-n2"></i>
        </div>
        <div class="fs-5-5 ms-4 me-4">PDFレイアウト調整ツール</div>
      </a>
    </div>
  </div>
</x-app-layout>
