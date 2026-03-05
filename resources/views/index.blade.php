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
    <li><a href="{{ route('reports.index') }}"><i class="nf nf-md-chat_processing_outline"></i> 報告書データ</a></li>
    <li><a href="{{ route('schedules.index') }}"><i class="nf nf-md-calendar_month_outline"></i> スケジュール</a></li>
    <li><a href="{{ route('master.index') }}"><i class="nf nf-md-database_plus_outline"></i> マスター登録</a></li>
    <li><a href="{{ route('prints.index') }}"><i class="nf nf-md-printer_outline"></i> 印刷メニュー</a></li>
    <li><a href="{{ route('therapy-periods.index') }}"><i class="nf nf-fa-list"></i> 要加療期間リスト</a></li>
    <li><a href="{{ route('deposits.index') }}"><i class="nf nf-fa-yen"></i> 入金管理</a></li>
  </ul>
</x-app-layout>
