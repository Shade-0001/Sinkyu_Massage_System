<!-- resources/views/index.blade.php -->


<x-app-layout>
  @php
    $page_header_title = 'ホーム';
  @endphp

  <x-page-header
    :title="$page_header_title"
  />

  <br>

  <ul class="list-unstyled fs-1 ms-3">
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('records.index') }}"><i class="nf nf-fa-file_text_o" style="background-color:#e74c3c; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">実績データ</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('reports.index') }}"><i class="nf nf-md-message_reply_text_outline" style="background-color:#e67e22; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">報告書データ</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('schedules.index') }}"><i class="nf nf-md-calendar_month_outline" style="background-color:#f1c40f; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">スケジュール</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('master.index') }}"><i class="nf nf-fa-edit" style="background-color:#2ecc71; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">マスター登録</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('prints.index') }}"><i class="nf nf-md-printer_outline" style="background-color:#3498db; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">印刷メニュー</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('therapy-periods.index') }}"><i class="nf nf-fa-list" style="background-color:#1a6bb5; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">要加療期間リスト</span></a></li>
    <li class="mb-2"><a class="text-decoration-none" href="{{ route('deposits.index') }}"><i class="nf nf-fa-yen" style="background-color:#9b59b6; color:#fff; border-radius:0.2em; padding:0.1em 0.2em;"></i> <span class="text-outline-black">入金管理</span></a></li>
  </ul>
</x-app-layout>
