<!-- resources/views/clinic-users/plans/plans_pdf.blade.php -->

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>計画書履歴一覧表</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="pdf-plans">
  <h1>計画書履歴一覧表</h1>
  <div class="user-info">
    <strong>利用者名:</strong> {{ $user->clinic_user_name }}
  </div>

  @forelse($planInfos as $index => $plan)
    <div class="plan-record">
      <div class="plan-header">
        <span class="@if($index === 0) status-latest @else status-updated @endif">
          {{ $index === 0 ? '【最新】' : '【更新済み】' }}
        </span>
        評価日: {{ $plan->assessment_date?->format('Y年m月d日') ?? '未設定' }}
        @if($plan->assessor)
          / 評価者: {{ $plan->assessor }}
        @endif
      </div>

      <!-- 基本情報 -->
      <table>
        <tr>
          <th class="label-cell">評価日</th>
          <td class="value-cell">{{ $plan->assessment_date?->format('Y年m月d日') ?? '' }}</td>
          <th class="label-cell">評価者</th>
          <td class="value-cell">{{ $plan->assessor ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">聴衆者</th>
          <td class="value-cell" colspan="3">{{ $plan->audience ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">本人・家族同意日</th>
          <td class="value-cell" colspan="3">{{ $plan->user_and_family_consent_date?->format('Y年m月d日') ?? '' }}</td>
        </tr>
      </table>

      <!-- ADL評価 -->
      <table class="adl-table">
        <thead>
          <tr>
            <th class="section-title" colspan="3">ADL（日常生活動作）評価</th>
          </tr>
          <tr>
            <th>項目</th>
            <th>介助レベル</th>
            <th>備考</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th class="label-cell">食事</th>
            <td class="level">{{ $plan->eatingAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->eating_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">起居･移動</th>
            <td class="level">{{ $plan->movingAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->moving_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">整容</th>
            <td class="level">{{ $plan->personalGroomingAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->personal_grooming_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">トイレ</th>
            <td class="level">{{ $plan->usingToiletAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->using_toilet_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">入浴</th>
            <td class="level">{{ $plan->bathingAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->bathing_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">平地歩行</th>
            <td class="level">{{ $plan->walkingAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->walking_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">階段昇降</th>
            <td class="level">{{ $plan->usingStairsAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->using_stairs_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">更衣</th>
            <td class="level">{{ $plan->changingClothesAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->changing_clothes_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">排便</th>
            <td class="level">{{ $plan->defecationAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->defecation_assistance_note ?? '' }}</td>
          </tr>
          <tr>
            <th class="label-cell">排尿</th>
            <td class="level">{{ $plan->urinationAssistanceLevel?->assistance_level ?? '' }}</td>
            <td class="note textarea-cell">{{ $plan->urination_assistance_note ?? '' }}</td>
          </tr>
        </tbody>
      </table>

      <!-- その他の情報 -->
      <table>
        <tr>
          <th class="label-cell">コミュニケーション</th>
          <td class="value-cell textarea-cell">{{ $plan->communication_note ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">ご本人・ご家族の希望</th>
          <td class="value-cell textarea-cell">{{ $plan->wish_of_user_and_familiy ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">治療目標</th>
          <td class="value-cell textarea-cell">{{ $plan->care_purpose ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">リハビリテーションプログラム</th>
          <td class="value-cell textarea-cell">{{ $plan->rehabilitation_program ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">自宅でのリハビリテーション</th>
          <td class="value-cell textarea-cell">{{ $plan->home_rehabilitation ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">前回計画書作成時からの改善･変化</th>
          <td class="value-cell textarea-cell">{{ $plan->change_since_previous_planning ?? '' }}</td>
        </tr>
        <tr>
          <th class="label-cell">障害・注意事項</th>
          <td class="value-cell textarea-cell">{{ $plan->note ?? '' }}</td>
        </tr>
      </table>
    </div>

    @if(!$loop->last)
      <div style="page-break-after: always;"></div>
    @endif
  @empty
    <p style="text-align: center; padding: 20px;">計画書が登録されていません。</p>
  @endforelse
</body>
</html>
