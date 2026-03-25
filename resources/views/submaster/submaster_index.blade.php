<x-app-layout>
  @section('title', $page_header_title)
  <x-page-header
    :title="$page_header_title"
    :breadcrumbs="App\Support\Breadcrumbs::generate('submaster.index')"
  />

  <br>

  ・<a href="{{ route('submaster.medical-institutions') }}">医療機関名［{{ $counts['medical_institutions'] }}件］</a><br>
  ・<a href="{{ route('submaster.service-providers') }}">サービス事業者名［{{ $counts['service_providers'] }}件］</a><br>
  ・<a href="{{ route('submaster.conditions') }}">発病負傷経過（あんま・マッサージ）［{{ $counts['conditions'] }}件］</a><br>
  ・<a href="{{ route('submaster.illnesses-massage') }}">傷病名（あんま・マッサージ）［{{ $counts['illnesses_massage'] }}件］</a>
</x-app-layout>
