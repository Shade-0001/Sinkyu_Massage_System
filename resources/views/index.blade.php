<!-- resources/views/index.blade.php -->


<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header
    :title="$page_header_title"
  />

  <br>

  <ul class="list-unstyled fs-2 ms-3">
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('records.index') }}"><i class="nf nf-fa-file_text_o"></i> 実績データ</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('reports.index') }}"><i class="nf nf-md-message_reply_text_outline"></i> 報告書データ</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('schedules.index') }}"><i class="nf nf-md-calendar_month_outline"></i> スケジュール</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('master.index') }}"><i class="nf nf-fa-edit"></i> マスター登録</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('prints.index') }}"><i class="nf nf-md-printer_outline"></i> 印刷メニュー</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('therapy-periods.index') }}"><i class="nf nf-fa-list"></i> 要加療期間リスト</a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('deposits.index') }}"><i class="nf nf-fa-yen"></i> 入金管理</a></li>
  </ul>
</x-app-layout>
