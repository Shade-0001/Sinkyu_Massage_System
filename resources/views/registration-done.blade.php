<x-app-layout>
  @if(isset($breadcrumb_name))
    <x-page-header
      :title="$page_header_title"
      :breadcrumbs="App\Support\Breadcrumbs::generate($breadcrumb_name)"
    />
  @else
    <h2>{{ $page_header_title }}</h2><br><br>
  @endif

  <p>{{ $message }}</p><br>

  <a href="{{ route($index_route, $index_id) }}">
  <button>◀ 利用者一覧に戻る</button>
  </a>

  @if($list_route)
  <a href="{{ route($list_route) }}">
    <button>一覧を見る</button>
  </a>
  @endif
</x-app-layout>
