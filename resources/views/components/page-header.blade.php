@props(['title', 'breadcrumbs' => []])

<div class="page-header mb-4">
  <h4 class="mb-2 fs-4 fw-semibold">
    {{ $title }}
  </h4>

  @if(count($breadcrumbs) > 0)
  <nav aria-label="breadcrumb">
    <ol class="list-unstyled p-0 m-0 d-flex flex-wrap gap-0 small text-muted">
      @foreach($breadcrumbs as $index => $breadcrumb)
        <li class="d-flex align-items-center gap-2">
          @if(isset($breadcrumb['url']))
            <a href="{{ $breadcrumb['url'] }}" class="fw-medium text-decoration-none d-flex align-items-center gap-1">
              @if(isset($breadcrumb['icon']))<i class="{{ $breadcrumb['icon'] }}"></i>@endif
              {{ $breadcrumb['label'] }}
            </a>
          @else
            <span class="text-muted d-flex align-items-center gap-1">
              @if(isset($breadcrumb['icon']))<i class="{{ $breadcrumb['icon'] }}"></i>@endif
              {{ $breadcrumb['label'] }}
            </span>
          @endif

          @if($index < count($breadcrumbs) - 1)
            <span class="text-secondary">〉</span>
          @endif
        </li>
      @endforeach
    </ol>
  </nav>
  @endif

  <hr class="border border-1 border-secondary opacity-50">
</div>
