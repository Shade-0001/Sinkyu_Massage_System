<!-- resources/views/index.blade.php -->


<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header
    :title="$page_header_title"
  />

  <br>

  <ul>
    <li><a href="{{ route('records.index') }}"><i class="nf nf-md-file_document"></i> 実績データ</a></li>
    <li><a href="{{ route('reports.index') }}">報告書データ</a></li>
    <li><a href="{{ route('schedules.index') }}">スケジュール</a></li>
    <li><a href="{{ route('master.index') }}">マスター登録</a></li>
    <li><a href="{{ route('prints.index') }}">印刷メニュー</a></li>
    <li><a href="{{ route('therapy-periods.index') }}">要加療期間リスト</a></li>
    <li><a href="{{ route('deposits.index') }}">入金管理</a></li>
  </ul>
</x-app-layout>
