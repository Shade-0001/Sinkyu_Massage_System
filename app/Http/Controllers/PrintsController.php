<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 印刷メニューコントローラー
 */
class PrintsController extends Controller
{
  /**
   * PDFタイプ設定のキャッシュ
   */
  protected $pdfTypesConfig = null;

  /**
   * PDFタイプ設定を取得
   *
   * @return array
   */
  protected function getPdfTypesConfig(): array
  {
    if ($this->pdfTypesConfig === null) {
      $configPath = storage_path('app/config/pdf_types.json');
      if (file_exists($configPath)) {
        $json = file_get_contents($configPath);
        $this->pdfTypesConfig = json_decode($json, true) ?? [];
      } else {
        $this->pdfTypesConfig = [];
      }
    }
    return $this->pdfTypesConfig;
  }

  /**
   * 指定されたPDFタイプの設定を取得
   *
   * @param string $pdfType
   * @return array|null
   */
  protected function getPdfTypeConfig(string $pdfType): ?array
  {
    $config = $this->getPdfTypesConfig();
    return $config[$pdfType] ?? null;
  }

  /**
   * PDFタイプから座標ファイルパスを取得
   *
   * @param string $pdfType
   * @return string
   */
  protected function getCoordinatesPath(string $pdfType): string
  {
    $config = $this->getPdfTypeConfig($pdfType);
    $fileName = $config['coordinatesFile'] ?? 'acupuncture_benefit_coordinates.json';
    return storage_path('app/config/' . $fileName);
  }

  /**
   * PDFタイプからサービスクラスのインスタンスを取得
   *
   * @param string $pdfType
   * @return object
   */
  protected function getPdfService(string $pdfType): object
  {
    $config = $this->getPdfTypeConfig($pdfType);
    $serviceClass = $config['serviceClass'] ?? 'AcupunctureBenefitPdfService';
    $fullClassName = "\\App\\Services\\Print\\{$serviceClass}";
    $service = new $fullClassName();

    // 座標ファイルパスとテンプレートファイルパスを設定
    if (method_exists($service, 'setCoordinatesPath') && isset($config['coordinatesFile'])) {
      $coordinatesPath = storage_path('app/config/' . $config['coordinatesFile']);
      $service->setCoordinatesPath($coordinatesPath);
    }

    if (method_exists($service, 'setTemplatePath') && isset($config['templateFile'])) {
      $templatePath = storage_path('app/templates/acupuncture_and_massage/' . $config['templateFile']);
      $service->setTemplatePath($templatePath);
    }

    return $service;
  }

  /**
   * 印刷メニューを表示
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('last_kana')
      ->get();

    // PDFタイプ一覧をビューに渡す
    $pdfTypes = $this->getPdfTypesConfig();

    return view('prints.prints_index', compact('clinicUsers', 'pdfTypes'));
  }

  /**
   * はり・きゅう療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\AcupunctureBenefitPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function acupunctureBenefit(Request $request, \App\Services\Print\AcupunctureBenefitPdfService $service, string $filename)
  {
    return $this->generatePdf($request, $service, 'はり・きゅう');
  }

  /**
   * あんま・マッサージ療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\MassageBenefitPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function massageBenefit(Request $request, \App\Services\Print\MassageBenefitPdfService $service, string $filename)
  {
    return $this->generatePdf($request, $service, 'あんま・マッサージ');
  }

  /**
   * 施術料金領収書PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\TreatmentReceiptPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function treatmentReceipt(Request $request, \App\Services\Print\TreatmentReceiptPdfService $service, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date' => 'required|date',
        'receipt_type' => 'required|in:acupuncture,massage',
        'include_report_fee' => 'sometimes|boolean',
        'remarks' => 'nullable|string',
      ]);

      $receiptType = $validated['receipt_type'];
      $typeName = $receiptType === 'acupuncture' ? '施術料金領収書（はり・きゅう）' : '施術料金領収書（あんま・マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'submission_date' => $validated['submission_date'],
        'receipt_type' => $receiptType,
        'include_report_fee' => $validated['include_report_fee'] ?? false,
      ]);

      // サービスに施術タイプを設定
      $service->setReceiptType($receiptType);

      // 施術報告書交付料フラグを設定
      if (!empty($validated['include_report_fee'])) {
        $service->setIncludeReportFee(true);
      }

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['service_year_month'],
        $validated['submission_date'],
        $validated['remarks'] ?? ''
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("施術料金領収書PDF生成エラー", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }

  /**
   * 医療助成費支給申請書PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function medicalAssistance(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'assistance_type' => 'required|in:acupuncture,massage',
        'signature_option' => 'required|in:user_signature_blank,user_address_signature_blank',
        'submission_month' => 'required|date_format:Y-m',
      ]);

      $assistanceType = $validated['assistance_type'];
      $typeName = $assistanceType === 'acupuncture' ? '医療助成費支給申請書（はり･きゅう）' : '医療助成費支給申請書（あんま･マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'assistance_type' => $assistanceType,
        'signature_option' => $validated['signature_option'],
        'submission_month' => $validated['submission_month'],
      ]);

      // サービスクラスを取得
      $serviceClass = $assistanceType === 'acupuncture'
        ? \App\Services\Print\AcupunctureBenefitPdfService::class
        : \App\Services\Print\MassageBenefitPdfService::class;

      $service = new $serviceClass();

      // 署名オプションを設定
      if (method_exists($service, 'setSignatureOption')) {
        $service->setSignatureOption($validated['signature_option']);
      }

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['service_year_month'],
        $validated['submission_month'] . '-01'
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("医療助成費支給申請書PDF生成エラー", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }

  /**
   * 施術料金一覧表（保険扱い）PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\TreatmentFeeListPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function treatmentFeeList(Request $request, \App\Services\Print\TreatmentFeeListPdfService $service, string $filename)
  {
    try {
      $validated = $request->validate([
        'service_year_month' => 'required|date_format:Y-m',
        'receipt_type' => 'required|in:acupuncture,massage',
      ]);

      $receiptType = $validated['receipt_type'];
      $typeName = $receiptType === 'acupuncture' ? '施術料金一覧表（保険扱い）はり・きゅう' : '施術料金一覧表（保険扱い）あんま・マッサージ';

      \Log::info("{$typeName}PDF生成開始", [
        'service_year_month' => $validated['service_year_month'],
        'receipt_type' => $receiptType,
      ]);

      // サービスに施術タイプを設定
      $service->setReceiptType($receiptType);

      $pdfBinary = $service->generate($validated['service_year_month']);

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("施術料金一覧表（保険扱い）PDF生成エラー", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }

  /**
   * PDF生成の共通処理
   *
   * @param Request $request
   * @param object $service
   * @param string $typeName
   * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
   */
  protected function generatePdf(Request $request, object $service, string $typeName)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date' => 'required|date',
      ]);

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'submission_date' => $validated['submission_date'],
      ]);

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['service_year_month'],
        $validated['submission_date']
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("{$typeName}PDF生成エラー", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ], 500);
    }
  }

  /**
   * PDFレイアウト調整ツール画面を表示
   *
   * @param Request $request
   * @return \Illuminate\View\View
   */
  public function coordinateAdjuster(Request $request)
  {
    $pdfType = $request->query('pdf_type', 'therapy_benefit_acupuncture');
    $selectedClinicUserId = $request->query('clinic_user_id', null);

    // PDFタイプ設定を取得
    $pdfTypes = $this->getPdfTypesConfig();
    $pdfTypeConfig = $this->getPdfTypeConfig($pdfType);
    $pdfTypeName = $pdfTypeConfig['name'] ?? 'はり・きゅう療養費支給申請書';

    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('last_kana')
      ->get();

    $masterData = [
      'genders' => DB::table('gender')->select('id', 'gender')->get(),
      'relationships' => DB::table('relationships_with_clinic_user')->select('id', 'relationship')->get(),
      'insurance_types_1' => DB::table('insurance_types_1')->select('id', 'insurance_type_1')->get(),
      'insurance_types_3' => DB::table('insurance_types_3')->select('id', 'insurance_type_3')->get(),
    ];

    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    return view('prints.coordinate_adjuster', compact(
      'clinicUsers',
      'pdfType',
      'pdfTypeName',
      'pdfTypes',
      'masterData',
      'treatmentFees',
      'selectedClinicUserId'
    ));
  }

  /**
   * 現在の座標設定を取得
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCoordinates(Request $request)
  {
    $pdfType = $request->query('pdf_type', 'therapy_benefit_acupuncture');
    $configPath = $this->getCoordinatesPath($pdfType);

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $coordinates = json_decode($json, true);

      // デバッグ：x=0,y=0のフィールドをチェック
      $zeroFields = [];
      foreach ($coordinates as $key => $value) {
        if (isset($value['x']) && isset($value['y']) && $value['x'] === 0 && $value['y'] === 0) {
          $zeroFields[] = $key;
        }
      }
      if (!empty($zeroFields)) {
        \Log::warning('getCoordinates: x=0,y=0のフィールドが存在', ['count' => count($zeroFields), 'fields' => $zeroFields]);
      }

      return response()->json([
        'success' => true,
        'coordinates' => $coordinates,
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => '設定ファイルが見つかりません',
    ], 404);
  }

  /**
   * 座標設定を保存
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function saveCoordinates(Request $request)
  {
    try {
      $coordinates = $request->input('coordinates');
      $pdfType = $request->input('pdf_type', 'therapy_benefit_acupuncture');
      $configPath = $this->getCoordinatesPath($pdfType);

      // x=0, y=0のフィールドを除外
      $filteredCoordinates = [];
      $removedCount = 0;
      foreach ($coordinates as $key => $value) {
        if (isset($value['x']) && isset($value['y']) && $value['x'] === 0 && $value['y'] === 0) {
          $removedCount++;
          continue;
        }
        $filteredCoordinates[$key] = $value;
      }

      if ($removedCount > 0) {
        \Log::info("saveCoordinates: x=0,y=0フィールドを除外", ['removed_count' => $removedCount]);
      }

      file_put_contents($configPath, json_encode($filteredCoordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      return response()->json([
        'success' => true,
        'message' => '座標設定を保存しました',
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => '保存に失敗しました: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * プレビューPDFを生成
   *
   * @param Request $request
   * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
   */
  public function previewPdf(Request $request)
  {
    try {
      \Log::info('PreviewPdf invoked', ['ip' => $request->ip(), 'has_coordinates' => $request->has('coordinates')]);

      $pdfType = $request->input('pdf_type', 'therapy_benefit_acupuncture');
      $configPath = $this->getCoordinatesPath($pdfType);

      $coordinates = $request->input('coordinates');
      $originalCoordinates = file_get_contents($configPath);

      // x=0, y=0のフィールドを除外
      $filteredCoordinates = [];
      $removedCount = 0;
      foreach ($coordinates as $k => $v) {
        if (isset($v['x']) && isset($v['y']) && $v['x'] === 0 && $v['y'] === 0) {
          $removedCount++;
          continue;
        }
        $filteredCoordinates[$k] = $v;
      }

      if ($removedCount > 0) {
        \Log::info("PreviewPdf: x=0,y=0フィールドを除外", ['removed_count' => $removedCount]);
      }

      $coordinates = $filteredCoordinates;

      // デバッグ：isSelectedが含まれているかチェック
      $hasIsSelected = false;
      foreach ($coordinates as $k => $v) {
        if (isset($v['isSelected'])) {
          \Log::warning('PreviewPdf: isSelected detected', ['key' => $k, 'isSelected' => $v['isSelected']]);
          $hasIsSelected = true;
        }
      }
      if (!$hasIsSelected) {
        \Log::info('PreviewPdf: No isSelected flags detected');
      }

      // 一時保存
      file_put_contents($configPath, json_encode($coordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

      $showSampleData = $request->input('show_sample_data', false);

      // letterSpacingデバッグログ
      try {
        foreach ($coordinates as $k => $v) {
          if (isset($v['letterSpacing']) && (float)$v['letterSpacing'] !== 0.0) {
            \Log::info('Preview coordinates letterSpacing', ['key' => $k, 'letterSpacing' => $v['letterSpacing']]);
          }
        }
      } catch (\Throwable $e) {
        \Log::warning('Preview letterSpacing logging failed', ['message' => $e->getMessage()]);
      }

      $clinicUserId = $request->input('clinic_user_id');

      if ($clinicUserId) {
        $clinicUsers = [(int)$clinicUserId];
      } else {
        $clinicUsers = DB::table('clinic_users')->limit(1)->pluck('id')->toArray();
      }

      if (empty($clinicUsers)) {
        file_put_contents($configPath, $originalCoordinates);
        return response()->json([
          'error' => '利用者データが存在しません',
        ], 404);
      }

      // PDFタイプに応じてサービスを取得
      $service = $this->getPdfService($pdfType);

      // サンプルデータ表示モードを設定
      if ($showSampleData && method_exists($service, 'setSampleDataMode')) {
        $service->setSampleDataMode(true);

        $customSampleData = $request->input('custom_sample_data');
        \Log::info('カスタムサンプルデータ受信チェック', [
          'exists' => !empty($customSampleData),
          'fee_hari_unit' => $customSampleData['fee_hari_unit'] ?? 'なし',
          'fee_kyu_unit' => $customSampleData['fee_kyu_unit'] ?? 'なし',
        ]);
        if ($customSampleData && method_exists($service, 'setCustomSampleData')) {
          $service->setCustomSampleData($customSampleData);
        }
      }

      $pdfBinary = $service->generate(
        $clinicUsers,
        date('Y-m'),
        date('Y-m-d'),
        ''
      );

      // 元の設定に戻す
      file_put_contents($configPath, $originalCoordinates);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('プレビューPDF生成エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);

      return response()->json([
        'error' => 'プレビュー生成に失敗しました',
        'message' => $e->getMessage(),
      ], 500);
    }
  }
}
