{{-- resources/views/master/documents/templates/generic_doc.blade.php --}}
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <style>
    @page {
      margin: 40px;
    }

    body {
      line-height: {{ $document->line_height ?? 7 }}mm;
      font-size: {{ $document->font_size ?? 12 }}pt;
      margin: 0;
      padding: 0;
    }

    .title {
      font-size: 26pt;
      text-align: center;
      margin-bottom: 30px;
      letter-spacing: 0.5em;
    }

    .date {
      text-align: right;
      margin-bottom: 20px;
    }

    .content-area {
      margin: 40px 0;
      min-height: 200px;
      white-space: pre-wrap;
      word-wrap: break-word;
    }

    .clinic-info {
      position: absolute;
      bottom: 40px;
      right: 40px;
      text-align: right;
    }
  </style>
</head>
<body>
  <div class="title">{{ $document->document_category }}</div>

  <div class="date">
    {{ date('Y年 m月d日', strtotime($document->created_at)) }}
  </div>

  <div class="content-area">{{ $document->content ?? '' }}</div>

  <div class="clinic-info">
    〒{{ $clinicInfo->postal_code ?? '郵便番号' }}{{ $clinicInfo->address_1 ?? '住所' }}{{ $clinicInfo->address_2 ?? '' }}{{ $clinicInfo->address_3 ?? '' }}<br>
    TEL：{{ $clinicInfo->phone ?? '電話番号' }}<br>
    {{ $clinicInfo->clinic_name ?? '事業所名' }}<br>
    {{ $clinicInfo->owner_name ?? '代表者名' }}
  </div>
</body>
</html>
