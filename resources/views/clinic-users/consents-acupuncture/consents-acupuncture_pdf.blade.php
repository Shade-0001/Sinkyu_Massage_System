<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>同意医師履歴一覧表（はり・きゅう）</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="pdf-consents-acupuncture">
  <h1>同意医師履歴一覧表（はり・きゅう）</h1>
  <p>利用者名: {{ $user->clinic_user_name }}</p>

  <table>
    <thead>
      <tr>
        <th style="width: 6%;">状態</th>
        <th style="width: 15%;">同意医師名</th>
        <th style="width: 10%;">同意日</th>
        <th style="width: 10%;">同意開始日</th>
        <th style="width: 10%;">同意終了日</th>
        <th style="width: 10%;">給付期間開始日</th>
        <th style="width: 10%;">給付期間終了日</th>
        <th style="width: 10%;">初療日</th>
        <th style="width: 10%;">再同意期限</th>
        <th style="width: 9%;">登録日</th>
      </tr>
    </thead>
    <tbody>
      @forelse($histories as $index => $history)
      <tr>
        <td style="width: 6%;" class="@if($index === 0) status-latest @else status-updated @endif">
          {{ $index === 0 ? '最新' : '履歴' }}
        </td>
        <td style="width: 15%;">
          @if($history->consentingDoctor)
            {{ $history->consentingDoctor->last_name }}\u{2000}{{ $history->consentingDoctor->first_name }}
          @else
            未設定
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->consenting_date)
            {{ \Carbon\Carbon::parse($history->consenting_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->consenting_start_date)
            {{ \Carbon\Carbon::parse($history->consenting_start_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->consenting_end_date)
            {{ \Carbon\Carbon::parse($history->consenting_end_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->benefit_period_start_date)
            {{ \Carbon\Carbon::parse($history->benefit_period_start_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->benefit_period_end_date)
            {{ \Carbon\Carbon::parse($history->benefit_period_end_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->first_care_date)
            {{ \Carbon\Carbon::parse($history->first_care_date)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 10%;">
          @if($history->reconsenting_expiry)
            {{ \Carbon\Carbon::parse($history->reconsenting_expiry)->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 9%;">
          {{ \Carbon\Carbon::parse($history->created_at)->format('Y/n/j') }}
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="10" style="text-align: center;">データがありません</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
