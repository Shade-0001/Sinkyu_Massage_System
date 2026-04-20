<?php
//-- app/Http/Controllers/DocumentController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
  /**
   * カテゴリ一覧を取得（既存DBデータ + 固定選択肢）
   */
  private function getCategories()
  {
    // DBから既存カテゴリを取得
    $dbCategories = DB::table('documents')
      ->select('document_category')
      ->distinct()
      ->whereNotNull('document_category')
      ->orderBy('document_category')
      ->pluck('document_category')
      ->toArray();

    // 固定選択肢
    $fixedCategories = ['依頼状', '挨拶状', '御礼状', '資料'];

    // マージして重複削除、ソート
    $allCategories = array_unique(array_merge($dbCategories, $fixedCategories));
    sort($allCategories);

    return $allCategories;
  }

  /**
   * 文面のインデックスページを表示
   */
  public function index()
  {
    $items = DB::table('documents')->orderBy('id')->get();
    return view('master.documents.documents_index', [
      'items' => $items,
      'page_header_title' => '文面編集'
    ]);
  }

  /**
   * 新規登録フォームを表示
   */
  public function create()
  {
    return view('master.documents.documents_registration', [
      'mode' => 'create',
      'page_header_title' => '文面新規登録',
      'categories' => $this->getCategories()
    ]);
  }


  /**
   * 編集フォームを表示
   */
  public function edit($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      return redirect()->route('master.documents.index')->with('error', '文面が見つからない。');
    }

    return view('master.documents.documents_registration', [
      'mode' => 'edit',
      'page_header_title' => '文面編集',
      'document' => $document,
      'categories' => $this->getCategories()
    ]);
  }

  /**
   * 文面を更新
   */
  public function update(Request $request, $id)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'nullable',
        'string',
        'max:255',
        function ($attribute, $value, $fail) use ($id) {
          if (!$value) return;
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->where('id', '!=', $id)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|numeric',
      'show_patient_info' => 'nullable|boolean',
      'patient_name' => 'nullable|string|max:100',
      'patient_illness' => 'nullable|string|max:100',
    ]);

    $showPatientInfo = $request->boolean('show_patient_info');

    DB::table('documents')
      ->where('id', $id)
      ->update([
        'document_category' => $request->document_category,
        'document_name'     => $request->document_name,
        'content'           => $request->content,
        'font_size'         => $request->font_size ?? 12,
        'line_height'       => $request->line_height ?? 7,
        'show_patient_info' => $showPatientInfo,
        'patient_name'      => $showPatientInfo ? $request->patient_name : null,
        'patient_illness'   => $showPatientInfo ? $request->patient_illness : null,
        'updated_at'        => now(),
      ]);

    return redirect()->route('master.documents.index')->with('success', 'データを更新しました。');
  }

  /**
   * 文面を新規登録
   */
  public function store(Request $request)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'nullable',
        'string',
        'max:255',
        function ($attribute, $value, $fail) {
          if (!$value) return;
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|numeric',
      'show_patient_info' => 'nullable|boolean',
      'patient_name' => 'nullable|string|max:100',
      'patient_illness' => 'nullable|string|max:100',
    ]);

    $showPatientInfo = $request->boolean('show_patient_info');

    DB::table('documents')->insert([
      'document_category'  => $request->document_category,
      'document_name'      => $request->document_name,
      'content'            => $request->content,
      'font_size'          => $request->font_size ?? 12,
      'line_height'        => $request->line_height ?? 7,
      'show_patient_info'  => $showPatientInfo,
      'patient_name'       => $showPatientInfo ? $request->patient_name : null,
      'patient_illness'    => $showPatientInfo ? $request->patient_illness : null,
      'created_at'         => now(),
      'updated_at'         => now(),
    ]);

    return redirect()->route('master.documents.index')->with('success', 'データを登録しました。');
  }

  /**
   * 文面を削除
   */
  public function destroy($id)
  {
    DB::table('documents')->where('id', $id)->delete();
    return redirect()->route('master.documents.index')->with('success', 'データを削除しました。');
  }

  /**
   * 文面複製画面表示
   */
  public function duplicate($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      return redirect()->route('master.documents.index')->with('error', '文面が見つからない。');
    }

    // カテゴリ一覧を取得
    $categories = DB::table('documents')
      ->select('document_category')
      ->distinct()
      ->whereNotNull('document_category')
      ->orderBy('document_category')
      ->pluck('document_category');

    return view('master.documents.documents_registration', [
      'mode' => 'duplicate',
      'page_header_title' => '文面複製',
      'document' => $document,
      'categories' => $categories
    ]);
  }

  /**
   * 文面複製登録処理
   */
  public function duplicateStore(Request $request)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'nullable',
        'string',
        'max:255',
        function ($attribute, $value, $fail) {
          if (!$value) return;
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|numeric',
      'show_patient_info' => 'nullable|boolean',
      'patient_name' => 'nullable|string|max:100',
      'patient_illness' => 'nullable|string|max:100',
    ]);

    $showPatientInfo = $request->boolean('show_patient_info');

    DB::table('documents')->insert([
      'document_category' => $request->document_category,
      'document_name'     => $request->document_name,
      'content'           => $request->content,
      'font_size'         => $request->font_size ?? 12,
      'line_height'       => $request->line_height ?? 7,
      'show_patient_info' => $showPatientInfo,
      'patient_name'      => $showPatientInfo ? $request->patient_name : null,
      'patient_illness'   => $showPatientInfo ? $request->patient_illness : null,
      'created_at'        => now(),
      'updated_at'        => now(),
    ]);

    return redirect()->route('master.documents.index')->with('success', 'データを複製しました。');
  }

  /**
   * 固定文書ID → PDFサービス設定マッピング
   * DocumentAssociationController の fixedDocuments 定義と対応
   */
  private function getFixedDocumentPdfConfig(): array
  {
    return [
      // 依頼状
      1 => ['service' => \App\Services\Print\ConsentRequestLetterSampleAcupuncturePdfService::class,    'type' => 'consent_request_sample_acupuncture',    'default_title' => '御依頼書'],
      2 => ['service' => \App\Services\Print\ConsentRequestLetterSampleMassagePdfService::class,        'type' => 'consent_request_sample_massage',        'default_title' => '御依頼書'],
      3 => ['service' => \App\Services\Print\ConsentRequestLetterDesignatedAcupuncturePdfService::class,'type' => 'consent_request_designated_acupuncture', 'default_title' => '御依頼書'],
      4 => ['service' => \App\Services\Print\ConsentRequestLetterDesignatedMassagePdfService::class,    'type' => 'consent_request_designated_massage',    'default_title' => '御依頼書'],
      // 御礼状
      5 => ['service' => \App\Services\Print\ThankYouLetterDoctorPdfService::class,   'type' => 'thank_you_doctor',   'thank_you_option' => 'consent', 'default_title' => '御礼状'],
      6 => ['service' => \App\Services\Print\ThankYouLetterDoctorPdfService::class,   'type' => 'thank_you_doctor',   'thank_you_option' => 'general', 'default_title' => '御礼状'],
      7 => ['service' => \App\Services\Print\ThankYouLetterReferrerPdfService::class, 'type' => 'thank_you_referrer',                                  'default_title' => '御礼状'],
      // 挨拶状
      8  => ['service' => \App\Services\Print\ReportGreetingPdfService::class, 'type' => 'report_greeting', 'greeting_type' => 'doctor',      'default_title' => '挨拶状'],
      9  => ['service' => \App\Services\Print\ReportGreetingPdfService::class, 'type' => 'report_greeting', 'greeting_type' => 'caremanager',  'default_title' => '挨拶状'],
      10 => ['service' => \App\Services\Print\ReportGreetingPdfService::class, 'type' => 'report_greeting', 'greeting_type' => 'user',         'default_title' => '挨拶状'],
    ];
  }

  /**
   * 文面のプレビューを表示
   * document_association で関連付けられたPDFサービスをサンプルモードで呼び出す
   */
  public function preview($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      abort(404, '文面が見つかりません');
    }

    // document_association から、この文書（document_id_2）に対応する固定文書ID（document_id_1）を逆引き
    $association = DB::table('document_association')
      ->where('document_id_2', $id)
      ->first();

    $fixedDocumentId = $association ? $association->document_id_1 : null;
    $pdfConfig       = $fixedDocumentId ? ($this->getFixedDocumentPdfConfig()[$fixedDocumentId] ?? null) : null;

    if ($pdfConfig) {
      return $this->previewWithPdfService($document, $pdfConfig);
    }

    // 対応PDFサービスがない場合はフォールバック
    return $this->previewFallback($document);
  }

  /**
   * 対応PDFサービスのサンプルモードでプレビュー生成
   */
  private function previewWithPdfService($document, array $config): \Illuminate\Http\Response
  {
    $serviceClass = $config['service'];
    $service = new $serviceClass();

    // プレビュー対象文書の本文を直接注入（document_association経由の取得を上書き）
    if (!empty($document->content)) {
      $service->setOverrideDocumentContent($document->content);
    }

    // タイトルをセット（DocumentController の default_title を優先）
    if (method_exists($service, 'setCustomTitleText')) {
      $service->setCustomTitleText($config['default_title'] ?? $document->document_category ?? '');
    }

    // 各サービス固有の設定
    if (isset($config['greeting_type']) && method_exists($service, 'setGreetingType')) {
      $service->setGreetingType($config['greeting_type']);
    }
    if (isset($config['thank_you_option']) && method_exists($service, 'setThankYouOption')) {
      $service->setThankYouOption($config['thank_you_option']);
    }

    // テンプレートファイルを設定
    $templatePath = storage_path('app/templates/汎用文書.pdf');
    if (file_exists($templatePath) && method_exists($service, 'setTemplatePath')) {
      $service->setTemplatePath($templatePath);
    }

    $today     = now()->format('Y-m-d');
    $yearMonth = now()->format('Y-m');
    $type      = $config['type'];

    // 実データが必要なサービス向けに最初の1件を取得
    $clinicUsers    = DB::table('clinic_users')->limit(1)->pluck('id')->toArray();
    $doctorIds      = DB::table('doctors')->limit(1)->pluck('id')->toArray();
    $caremanagerIds = DB::table('caremanagers')->limit(1)->pluck('id')->toArray();

    if (empty($clinicUsers)) {
      $clinicUsers = [0];
    }

    if (in_array($type, ['consent_request_sample_acupuncture', 'consent_request_sample_massage'])) {
      $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today);
    } elseif (in_array($type, ['consent_request_designated_acupuncture', 'consent_request_designated_massage'])) {
      $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', $doctorIds);
    } elseif ($type === 'thank_you_doctor') {
      $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', $doctorIds);
    } elseif ($type === 'thank_you_referrer') {
      $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', $caremanagerIds);
    } elseif ($type === 'report_greeting') {
      $greetingType = $config['greeting_type'];
      if ($greetingType === 'doctor') {
        $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', $doctorIds, []);
      } elseif ($greetingType === 'caremanager') {
        $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', [], $caremanagerIds);
      } else {
        $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today, '', [], []);
      }
    } else {
      $pdfBinary = $service->generate($clinicUsers, $yearMonth, $today);
    }

    return response($pdfBinary, 200, [
      'Content-Type'        => 'application/pdf',
      'Content-Disposition' => 'inline',
    ]);
  }

  /**
   * フォールバック：TCPDFによるシンプルなHTMLレンダリング
   */
  private function previewFallback($document): \Illuminate\Http\Response
  {
    $service = new \App\Services\Print\GenericDocumentPdfService();

    if (!empty($document->content)) {
      $service->setOverrideDocumentContent($document->content);
    }

    if (method_exists($service, 'setCustomTitleText')) {
      $titleText = !empty($document->document_name) ? $document->document_name : ($document->document_category ?? '');
      $service->setCustomTitleText($titleText);
    }

    $templatePath = storage_path('app/templates/汎用文書.pdf');
    if (file_exists($templatePath) && method_exists($service, 'setTemplatePath')) {
      $service->setTemplatePath($templatePath);
    }

    $service->setShowPatientInfo((bool)($document->show_patient_info ?? false));
    if ($document->show_patient_info) {
      $service->setPatientName($document->patient_name ?? '');
      $service->setPatientIllness($document->patient_illness ?? '');
    }

    if (isset($document->font_size) && $document->font_size !== null) {
      $service->setFontSize((float)$document->font_size);
    }
    if (isset($document->line_height) && $document->line_height !== null) {
      $service->setLineHeight((float)$document->line_height);
    }

    $today = now()->format('Y-m-d');
    $pdfBinary = $service->generate([], now()->format('Y-m'), $today);

    return response($pdfBinary, 200, [
      'Content-Type'        => 'application/pdf',
      'Content-Disposition' => 'inline',
    ]);
  }

  /**
   * 文面名称の重複チェック（Ajax用）
   */
  public function checkDuplicateName(Request $request)
  {
    $name = $request->input('document_name');
    $excludeId = $request->input('exclude_id');

    if (empty($name)) {
      return response()->json(['exists' => false]);
    }

    $query = DB::table('documents')->where('document_name', $name);

    // 編集時は自分自身のIDを除外
    if ($excludeId) {
      $query->where('id', '!=', $excludeId);
    }

    $exists = $query->exists();

    return response()->json(['exists' => $exists]);
  }
}
