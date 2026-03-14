<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>医療保険情報履歴一覧表</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="pdf-insurances">
  <h1>医療保険情報履歴一覧表</h1>
  <p>利用者名: {{ $user->clinic_user_name }}</p>

  <table>
    <thead>
      <tr>
        <th style="width: 4%;">状態</th>
        <th style="width: 7%;">保険区分</th>
        <th style="width: 8%;">被保険者番号</th>
        <th style="width: 7%;">資格取得年月日</th>
        <th style="width: 7%;">認定年月日</th>
        <th style="width: 7%;">発行年月日</th>
        <th style="width: 5%;">一部負担金の割合</th>
        <th style="width: 7%;">有効期限</th>
        <th style="width: 7%;">世帯主氏名</th>
        <th style="width: 7%;">被保険者氏名</th>
        <th style="width: 4%;">医療助成対象</th>
        <th style="width: 7%;">公費負担者番号</th>
        <th style="width: 7%;">公費受給者番号</th>
        <th style="width: 8%;">保険者番号</th>
        <th style="width: 8%;">保険者名称</th>
      </tr>
    </thead>
    <tbody>
      @forelse($insurances as $index => $insurance)
      <tr>
        <td style="width: 4%;" class="@if($index === 0) status-latest @else status-updated @endif">
          {{ $index === 0 ? '最新' : '更新済み' }}
        </td>
        <td style="width: 7%;">
          {{ $insurance->insurer?->insurer_category ?? '保険' }}
        </td>
        <td style="width: 8%;">{{ $insurance->insured_number }}</td>
        <td style="width: 7%;">
          @if($insurance->license_acquisition_date)
            {{ $insurance->license_acquisition_date->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 7%;">
          @if($insurance->certification_date)
            {{ $insurance->certification_date->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 7%;">
          @if($insurance->issue_date)
            {{ $insurance->issue_date->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 5%; text-align: center;">
          {{ $insurance->copayment_rate }}
        </td>
        <td style="width: 7%;">
          @if($insurance->expiry_date)
            {{ $insurance->expiry_date->format('Y/n/j') }}
          @endif
        </td>
        <td style="width: 7%;">
          @if($insurance->relationship !== '本人')
            {{ $insurance->insured_name }}
          @endif
        </td>
        <td style="width: 7%;">
          @if($insurance->relationship === '本人')
            {{ $insurance->insured_name }}
          @endif
        </td>
        <td style="width: 4%; text-align: center;">
          @if($insurance->is_healthcare_subsidized)
            対象
          @else
            非対象
          @endif
        </td>
        <td style="width: 7%;">{{ $insurance->locality_code ?? $insurance->public_funds_payer_code }}</td>
        <td style="width: 7%;">{{ $insurance->recipient_code ?? $insurance->public_funds_recipient_code }}</td>
        <td style="width: 8%;">{{ $insurance->insurer?->insurer_number }}</td>
        <td style="width: 8%;">{{ $insurance->insurer?->insurer_name }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="15" style="text-align: center;">データがありません</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
