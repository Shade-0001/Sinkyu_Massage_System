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
   * 総括表用：施術タイプ別にデータが存在する年月セットを返す
   *
   * @return array ['acupuncture' => ['2026-02', ...], 'massage' => ['2026-02', ...]]
   */
  protected function getSummaryTableDataMonths(): array
  {
    $acupunctureIds = [11, 12, 13, 14, 15, 16];
    $massageIds     = [18, 19, 20, 21];

    $acupunctureMonths = DB::table('records')
      ->whereIn('therapy_content_id', $acupunctureIds)
      ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
      ->distinct()
      ->pluck('ym')
      ->toArray();

    $massageMonths = DB::table('records')
      ->whereIn('therapy_content_id', $massageIds)
      ->selectRaw("DATE_FORMAT(date, '%Y-%m') as ym")
      ->distinct()
      ->pluck('ym')
      ->toArray();

    return [
      'acupuncture' => $acupunctureMonths,
      'massage'     => $massageMonths,
    ];
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

    if (method_exists($service, 'setTemplatePath') && isset($config['templateFile']) && !empty($config['templateFile'])) {
      $templateDir = $config['templateDir'] ?? 'acupuncture_and_massage';
      $templatePath = storage_path('app/templates/' . $templateDir . '/' . $config['templateFile']);
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

    $doctors = DB::table('doctors')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('last_name_kana')
      ->get();

    $caremanagers = DB::table('caremanagers')
      ->select('id', 'last_name', 'first_name', 'last_name_kana', 'first_name_kana')
      ->orderBy('last_name_kana')
      ->get();

    // PDFタイプ一覧をビューに渡す
    $pdfTypes = $this->getPdfTypesConfig();

    // 総括表用：施術タイプ別にデータが存在する年月セットを取得
    $summaryTableDataMonths = $this->getSummaryTableDataMonths();

    return view('prints.prints_index', compact('clinicUsers', 'doctors', 'caremanagers', 'pdfTypes', 'summaryTableDataMonths'));
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
        'signature_option' => 'nullable|in:user_signature_blank,user_address_signature_blank',
        'submission_month' => 'required|date_format:Y-m',
      ]);

      $assistanceType = $validated['assistance_type'];
      $typeName = $assistanceType === 'acupuncture' ? '医療助成費支給申請書（はり･きゅう）' : '医療助成費支給申請書（あんま･マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'assistance_type' => $assistanceType,
        'signature_option' => $validated['signature_option'] ?? null,
        'submission_month' => $validated['submission_month'],
      ]);

      // 医療助成費専用のサービスクラスを取得
      $serviceClass = $assistanceType === 'acupuncture'
        ? \App\Services\Print\MedicalAssistanceAcupuncturePdfService::class
        : \App\Services\Print\MedicalAssistanceMassagePdfService::class;

      $service = new $serviceClass();

      // 署名オプションを設定
      if (method_exists($service, 'setSignatureOption')) {
        $service->setSignatureOption($validated['signature_option'] ?? null);
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
   * 後期高齢者医療療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function lateElderlyMedical(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'assistance_type' => 'required|in:acupuncture,massage',
        'signature_option' => 'nullable|in:user_signature_blank,user_address_signature_blank',
        'submission_month' => 'required|date_format:Y-m',
      ]);

      $assistanceType = $validated['assistance_type'];
      $typeName = $assistanceType === 'acupuncture' ? '後期高齢者医療療養費支給申請書（はり･きゅう）' : '後期高齢者医療療養費支給申請書（あんま･マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'assistance_type' => $assistanceType,
        'signature_option' => $validated['signature_option'] ?? null,
        'submission_month' => $validated['submission_month'],
      ]);

      // 後期高齢者医療専用のサービスクラスを取得
      $serviceClass = $assistanceType === 'acupuncture'
        ? \App\Services\Print\LateElderlyMedicalAcupuncturePdfService::class
        : \App\Services\Print\LateElderlyMedicalMassagePdfService::class;

      $service = new $serviceClass();

      // 署名オプションを設定
      if (method_exists($service, 'setSignatureOption')) {
        $service->setSignatureOption($validated['signature_option'] ?? null);
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
      \Log::error("後期高齢者医療療養費支給申請書PDF生成エラー", [
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
   * 施術料金一覧表（自費）PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\SelfFeeListPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function selfFeeList(Request $request, \App\Services\Print\SelfFeeListPdfService $service, string $filename)
  {
    try {
      $validated = $request->validate([
        'service_year_month' => 'required|date_format:Y-m',
      ]);

      \Log::info("施術料金一覧表（自費）PDF生成開始", [
        'service_year_month' => $validated['service_year_month'],
      ]);

      $pdfBinary = $service->generate($validated['service_year_month']);

      \Log::info("施術料金一覧表（自費）PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("施術料金一覧表（自費）PDF生成エラー", [
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
   * 入金管理票（保険）PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function insurancePayment(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'service_year_month' => 'required|date_format:Y-m',
      ]);

      \Log::info("入金管理票（保険）PDF生成開始", [
        'service_year_month' => $validated['service_year_month'],
      ]);

      $service = new \App\Services\Print\InsurancePaymentPdfService();
      $pdfBinary = $service->generate($validated['service_year_month']);

      \Log::info("入金管理票（保険）PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("入金管理票（保険）PDF生成エラー", [
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
   * 同意書依頼状（サンプル版）PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function consentRequestSample(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'consent_request_type' => 'required|in:acupuncture,massage',
        'submission_month' => 'required|date_format:Y-m',
      ]);

      $consentRequestType = $validated['consent_request_type'];
      $typeName = $consentRequestType === 'acupuncture' ? '同意書依頼状（サンプル版）はり･きゅう' : '同意書依頼状（サンプル版）あんま･マッサージ';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'consent_request_type' => $consentRequestType,
        'submission_month' => $validated['submission_month'],
      ]);

      $serviceClass = $consentRequestType === 'acupuncture'
        ? \App\Services\Print\ConsentRequestLetterSampleAcupuncturePdfService::class
        : \App\Services\Print\ConsentRequestLetterSampleMassagePdfService::class;

      $service = new $serviceClass();

      // タイトルを設定（リクエストから優先、なければデフォルト）
      $customTitleText = $request->input('custom_title_text');
      if (!$customTitleText) {
        $customTitleText = $consentRequestType === 'acupuncture'
          ? '同意書依頼（サンプル版）はり・きゅう'
          : '同意書依頼（サンプル版）あんま・マッサージ';
      }
      $service->setCustomTitleText($customTitleText);

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['submission_month'],
        $validated['submission_month'] . '-01'
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("同意書依頼状（サンプル版）PDF生成エラー", [
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
   * 同意書依頼状（医師指定）PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function consentRequestDesignated(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'doctor_ids' => 'required|array',
        'doctor_ids.*' => 'exists:doctors,id',
        'consent_request_type' => 'required|in:acupuncture,massage',
        'submission_month' => 'required|date_format:Y-m',
      ]);

      $consentRequestType = $validated['consent_request_type'];
      $typeName = $consentRequestType === 'acupuncture' ? '同意書依頼状（医師指定）はり･きゅう' : '同意書依頼状（医師指定）あんま･マッサージ';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'doctor_ids' => $validated['doctor_ids'],
        'consent_request_type' => $consentRequestType,
        'submission_month' => $validated['submission_month'],
      ]);

      $serviceClass = $consentRequestType === 'acupuncture'
        ? \App\Services\Print\ConsentRequestLetterDesignatedAcupuncturePdfService::class
        : \App\Services\Print\ConsentRequestLetterDesignatedMassagePdfService::class;

      $service = new $serviceClass();

      // タイトルを設定（リクエストから優先、なければデフォルト）
      $customTitleText = $request->input('custom_title_text');
      if (!$customTitleText) {
        $customTitleText = $consentRequestType === 'acupuncture'
          ? '同意書依頼（医師指定）はり・きゅう'
          : '同意書依頼（医師指定）あんま・マッサージ';
      }
      $service->setCustomTitleText($customTitleText);

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['submission_month'],
        $validated['submission_month'] . '-01',
        '',
        $validated['doctor_ids']
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("同意書依頼状（医師指定）PDF生成エラー", [
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

    // pdf_typeが指定されていない場合は警告ログ
    if (!$request->has('pdf_type')) {
      \Log::warning('coordinateAdjuster: pdf_typeが指定されていないため、デフォルト値を使用', [
        'default_pdf_type' => $pdfType,
        'url' => $request->fullUrl()
      ]);
    }

    // PDFタイプ設定を取得
    $pdfTypes = $this->getPdfTypesConfig();

    // 座標調整ツールには表示不要なPDFタイプを除外
    $excludedTypes = ['treatment_fee_list'];
    $pdfTypes = array_filter($pdfTypes, function($key) use ($excludedTypes) {
      return !in_array($key, $excludedTypes);
    }, ARRAY_FILTER_USE_KEY);

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
      'illnesses_massage' => DB::table('illnesses_massage')->select('id', 'illness_name')->get(),
    ];

    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    // 年月セレクトボックス用データ生成（2020年1月～未来3ヶ月、降順）
    $yearMonths = [];
    $currentYearMonth = date('Y-m');
    $endDate = strtotime('+3 months');
    $startDate = strtotime('2020-01-01');

    // 未来3ヶ月から2020年1月まで降順で生成
    for ($date = $endDate; $date >= $startDate; $date = strtotime('-1 month', $date)) {
      $ym = date('Y-m', $date);
      $year = date('Y', $date);
      $month = date('n', $date);
      $yearMonths[] = [
        'value' => $ym,
        'label' => "{$year}年 {$month}月"
      ];
    }

    return view('prints.coordinate_adjuster', compact(
      'clinicUsers',
      'pdfType',
      'pdfTypeName',
      'pdfTypes',
      'masterData',
      'treatmentFees',
      'selectedClinicUserId',
      'yearMonths',
      'currentYearMonth'
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

      // 空配列・null の場合は空オブジェクト相当として扱う
      if (empty($coordinates)) {
        $coordinates = new \stdClass();
      }

      // デバッグ：x=0,y=0のフィールドをチェック
      $zeroFields = [];
      foreach ((array)$coordinates as $key => $value) {
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
      $pdfType = $request->input('pdf_type');

      // pdf_typeが指定されていない場合はエラー
      if (empty($pdfType)) {
        \Log::error('saveCoordinates: pdf_typeが指定されていません', [
          'request' => $request->all()
        ]);
        return response()->json([
          'success' => false,
          'message' => 'PDFタイプが指定されていません',
        ], 400);
      }

      $configPath = $this->getCoordinatesPath($pdfType);

      // 保存先ファイルパスをログに記録
      \Log::info('saveCoordinates: 座標保存開始', [
        'pdf_type' => $pdfType,
        'config_path' => $configPath,
        'coordinates_count' => count($coordinates)
      ]);

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

      // birthday_year座標が保存された場合、birthday_full_dateを自動同期
      if (isset($filteredCoordinates['birthday_year'])) {
        $filteredCoordinates['birthday_full_date'] = [
          'x' => $filteredCoordinates['birthday_year']['x'],
          'y' => $filteredCoordinates['birthday_year']['y'],
          'fontSize' => $filteredCoordinates['birthday_year']['fontSize'] ?? 10,
          'letterSpacing' => $filteredCoordinates['birthday_year']['letterSpacing'] ?? 0,
          'textAlign' => $filteredCoordinates['birthday_year']['textAlign'] ?? 'center',
          'type' => 'text'
        ];
        \Log::info('saveCoordinates: birthday_full_dateをbirthday_yearと自動同期', [
          'x' => $filteredCoordinates['birthday_full_date']['x'],
          'y' => $filteredCoordinates['birthday_full_date']['y'],
          'fontSize' => $filteredCoordinates['birthday_full_date']['fontSize']
        ]);
      }

      // consent_record_date_year座標が保存された場合、consent_date_fullを自動同期
      if (isset($filteredCoordinates['consent_record_date_year'])) {
        $filteredCoordinates['consent_date_full'] = [
          'x' => $filteredCoordinates['consent_record_date_year']['x'],
          'y' => $filteredCoordinates['consent_record_date_year']['y'],
          'fontSize' => $filteredCoordinates['consent_record_date_year']['fontSize'] ?? 10,
          'letterSpacing' => $filteredCoordinates['consent_record_date_year']['letterSpacing'] ?? 0,
          'textAlign' => $filteredCoordinates['consent_record_date_year']['textAlign'] ?? 'left',
          'type' => 'text',
          'field' => 'consent_date_full',
          'label' => '同意年月日'
        ];
        \Log::info('saveCoordinates: consent_date_fullをconsent_record_date_yearと自動同期', [
          'x' => $filteredCoordinates['consent_date_full']['x'],
          'y' => $filteredCoordinates['consent_date_full']['y'],
          'fontSize' => $filteredCoordinates['consent_date_full']['fontSize']
        ]);
      }

      // 空配列の場合は JSON_FORCE_OBJECT で {} として保存（[] だとJS側で配列扱いされ文字列キーが消える）
      $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
      if (empty($filteredCoordinates)) {
        $jsonFlags |= JSON_FORCE_OBJECT;
      }
      file_put_contents($configPath, json_encode($filteredCoordinates, $jsonFlags));

      \Log::info('saveCoordinates: 座標保存完了', [
        'pdf_type' => $pdfType,
        'saved_count' => count($filteredCoordinates)
      ]);

      return response()->json([
        'success' => true,
        'message' => '座標設定を保存しました',
      ]);
    } catch (\Exception $e) {
      \Log::error('saveCoordinates: 保存エラー', [
        'error' => $e->getMessage(),
        'pdf_type' => $request->input('pdf_type'),
        'trace' => $e->getTraceAsString()
      ]);
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

      $pdfType = $request->input('pdf_type');

      // pdf_typeが指定されていない場合はエラー
      if (empty($pdfType)) {
        \Log::error('previewPdf: pdf_typeが指定されていません', [
          'request' => $request->all()
        ]);
        return response()->json([
          'success' => false,
          'message' => 'PDFタイプが指定されていません',
        ], 400);
      }

      $configPath = $this->getCoordinatesPath($pdfType);

      \Log::info('previewPdf: PDF生成開始', [
        'pdf_type' => $pdfType,
        'config_path' => $configPath
      ]);

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

      // birthday_year座標がある場合、birthday_full_dateを自動同期
      if (isset($filteredCoordinates['birthday_year'])) {
        $filteredCoordinates['birthday_full_date'] = [
          'x' => $filteredCoordinates['birthday_year']['x'],
          'y' => $filteredCoordinates['birthday_year']['y'],
          'fontSize' => $filteredCoordinates['birthday_year']['fontSize'] ?? 10,
          'letterSpacing' => $filteredCoordinates['birthday_year']['letterSpacing'] ?? 0,
          'textAlign' => $filteredCoordinates['birthday_year']['textAlign'] ?? 'center',
          'type' => 'text'
        ];
      }

      // consent_record_date_year座標がある場合、consent_date_fullを自動同期
      if (isset($filteredCoordinates['consent_record_date_year'])) {
        $filteredCoordinates['consent_date_full'] = [
          'x' => $filteredCoordinates['consent_record_date_year']['x'],
          'y' => $filteredCoordinates['consent_record_date_year']['y'],
          'fontSize' => $filteredCoordinates['consent_record_date_year']['fontSize'] ?? 10,
          'letterSpacing' => $filteredCoordinates['consent_record_date_year']['letterSpacing'] ?? 0,
          'textAlign' => $filteredCoordinates['consent_record_date_year']['textAlign'] ?? 'left',
          'type' => 'text',
          'field' => 'consent_date_full',
          'label' => '同意年月日'
        ];
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
      $yearMonth = $request->input('year_month', date('Y-m'));

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
          'last_name' => $customSampleData['last_name'] ?? 'なし',
          'first_name' => $customSampleData['first_name'] ?? 'なし',
          'last_kana' => $customSampleData['last_kana'] ?? 'なし',
          'first_kana' => $customSampleData['first_kana'] ?? 'なし',
          'insurance_symbol_code' => $customSampleData['insurance_symbol_code'] ?? 'なし',
          'insurance_symbol_number' => $customSampleData['insurance_symbol_number'] ?? 'なし',
        ]);
        if ($customSampleData && method_exists($service, 'setCustomSampleData')) {
          $service->setCustomSampleData($customSampleData);
        }
      }

      // custom_title_textは常に設定（サンプルモード・ノーマルモード共通）
      // カスタムタイトルテキストを設定
      $customTitleText = $request->input('custom_title_text');
      \Log::info('[PrintsController] custom_title_text受信', [
        'pdf_type' => $pdfType,
        'custom_title_text' => $customTitleText,
        'has_method' => method_exists($service, 'setCustomTitleText'),
      ]);
      if ($customTitleText && method_exists($service, 'setCustomTitleText')) {
        $service->setCustomTitleText($customTitleText);
        \Log::info('[PrintsController] setCustomTitleText実行完了', ['custom_title_text' => $customTitleText]);
      }

      // 総括表の場合はデフォルト施術タイプを設定（プレビュー時ははり・きゅう）
      if ($pdfType === 'summary_table' && method_exists($service, 'setTherapyType')) {
        $service->setTherapyType('acupuncture');
      }

      // 医師指定版の場合はダミー医師IDを渡す
      if (in_array($pdfType, ['consent_request_letter_designated_acupuncture', 'consent_request_letter_designated_massage'])) {
        // 最初の医師IDを取得（なければ空配列）
        $doctorIds = DB::table('doctors')->limit(1)->pluck('id')->toArray();
        $pdfBinary = $service->generate(
          $clinicUsers,
          $yearMonth,
          $yearMonth . '-01',
          '',
          $doctorIds
        );
      } else {
        $pdfBinary = $service->generate(
          $clinicUsers,
          $yearMonth,
          $yearMonth . '-01',
          ''
        );
      }

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

  /**
   * 利用者の施術日一覧を取得
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getTreatmentDays(Request $request)
  {
    try {
      $clinicUserId = $request->query('clinic_user_id');
      $serviceYearMonth = $request->query('service_year_month', date('Y-m'));
      $pdfType = $request->query('pdf_type', 'medical_assistance_acupuncture');

      if (!$clinicUserId) {
        return response()->json([
          'success' => false,
          'message' => '利用者IDが指定されていません',
          'treatment_days' => [],
        ]);
      }

      // PDFタイプに応じて対象の施術内容IDを決定
      $therapyContentIds = [];
      if ($pdfType === 'medical_assistance_acupuncture' || $pdfType === 'elderly_therapy_benefit_acupuncture') {
        // はり･きゅう: therapy_content_id 11-16
        $therapyContentIds = [11, 12, 13, 14, 15, 16];
      } elseif ($pdfType === 'medical_assistance_massage' || $pdfType === 'elderly_therapy_benefit_massage') {
        // あんま･マッサージ: therapy_content_id 1-10
        $therapyContentIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
      }

      if (empty($therapyContentIds)) {
        return response()->json([
          'success' => false,
          'message' => '対応していないPDFタイプです',
          'treatment_days' => [],
        ]);
      }

      // 指定月の施術記録から日付を取得
      $records = \DB::table('records')
        ->where('clinic_user_id', $clinicUserId)
        ->whereYear('date', '=', date('Y', strtotime($serviceYearMonth)))
        ->whereMonth('date', '=', date('m', strtotime($serviceYearMonth)))
        ->whereIn('therapy_content_id', $therapyContentIds)
        ->orderBy('date')
        ->pluck('date');

      // 日付から日（1-31）を抽出してユニークな配列にする
      $treatmentDays = $records
        ->map(function($date) {
          return (int)date('d', strtotime($date));
        })
        ->unique()
        ->sort()
        ->values()
        ->toArray();

      return response()->json([
        'success' => true,
        'treatment_days' => $treatmentDays,
      ]);
    } catch (\Exception $e) {
      \Log::error('施術日取得エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);

      return response()->json([
        'success' => false,
        'message' => '施術日の取得に失敗しました',
        'treatment_days' => [],
      ], 500);
    }
  }

  /**
   * 同意書（はり・きゅう / あんま・マッサージ）PDF出力
   *
   * @param Request $request
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function consentForm(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'consent_form_type' => 'required|in:acupuncture,massage',
        'consent_category' => 'required|in:new,renewal',
        'consent_form_option' => 'nullable|in:doctor_info_blank',
        'submission_date' => 'required|date',
      ]);

      $consentFormType = $validated['consent_form_type'];
      $typeName = $consentFormType === 'acupuncture' ? '同意書（はり・きゅう）' : '同意書（あんま・マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'consent_form_type' => $consentFormType,
        'consent_category' => $validated['consent_category'],
        'consent_form_option' => $validated['consent_form_option'] ?? null,
        'submission_date' => $validated['submission_date'],
      ]);

      $serviceClass = $consentFormType === 'acupuncture'
        ? \App\Services\Print\ConsentAcupuncturePdfService::class
        : \App\Services\Print\ConsentMassagePdfService::class;

      $service = new $serviceClass();

      // 同意区分を日本語に変換
      $consentCategoryText = $validated['consent_category'] === 'new' ? '新規同意' : '再同意';

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        '', // service_year_month (不要)
        $validated['submission_date'],
        $consentCategoryText
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("同意書PDF生成エラー", [
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
   * 総括表PDF出力
   *
   * @param Request $request
   * @param string  $filename
   * @return \Illuminate\Http\Response
   */
  public function summaryTable(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date'    => 'required|date',
        'summary_type'       => 'required|in:acupuncture,massage',
      ]);

      $summaryType = $validated['summary_type'];
      $typeName = $summaryType === 'acupuncture' ? '総括表（はり・きゅう）' : '総括表（あんま・マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'service_year_month' => $validated['service_year_month'],
        'submission_date'    => $validated['submission_date'],
        'summary_type'       => $summaryType,
      ]);

      $service = new \App\Services\Print\SummaryTablePdfService();
      $service->setTherapyType($summaryType);

      // 座標ファイル・テンプレートをpdf_types.jsonから設定
      $config = $this->getPdfTypeConfig('summary_table');
      if ($config) {
        if (!empty($config['coordinatesFile'])) {
          $service->setCoordinatesPath(storage_path('app/config/' . $config['coordinatesFile']));
        }
        if (!empty($config['templateFile'])) {
          $templateDir = $config['templateDir'] ?? 'acupuncture_and_massage';
          $service->setTemplatePath(storage_path('app/templates/' . $templateDir . '/' . $config['templateFile']));
        }
      }

      $pdfBinary = $service->generate(
        [],
        $validated['service_year_month'],
        $validated['submission_date']
      );

      \Log::info("{$typeName}PDF生成完了", ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error("総括表PDF生成エラー", [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
      ]);

      return response()->json([
        'error'   => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
      ], 500);
    }
  }

  /**
   * 初回体験用資料PDF出力
   *
   * @return \Illuminate\Http\Response
   */
  public function firstExperienceMaterial()
  {
    try {
      \Log::info('初回体験用資料PDF生成開始');

      $service = new \App\Services\Print\FirstExperienceMaterialPdfService();
      $pdfBinary = $service->generate([], '', '');

      \Log::info('初回体験用資料PDF生成完了', ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('初回体験用資料PDF生成エラー', [
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
   * 委任状（申請･受領）PDF出力
   */
  public function powerOfAttorneyApplication()
  {
    try {
      $service = new \App\Services\Print\PowerOfAttorneyApplicationPdfService();
      $pdfBinary = $service->generate([], '', '');

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('委任状（申請･受領）PDF生成エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  /**
   * 委任状（同意書取得）PDF出力
   */
  public function powerOfAttorneyConsent()
  {
    try {
      $service = new \App\Services\Print\PowerOfAttorneyConsentPdfService();
      $pdfBinary = $service->generate([], '', '');

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('委任状（同意書取得）PDF生成エラー', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ]);

      return response()->json([
        'error' => 'PDF生成に失敗しました',
        'message' => $e->getMessage(),
      ], 500);
    }
  }

  public function treatmentRecord(Request $request, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'record_type' => 'required|in:acupuncture,massage',
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date' => 'required|date',
      ]);

      $recordType = $validated['record_type'];
      $typeName = $recordType === 'acupuncture' ? '施術録（はり・きゅう）' : '施術録（あんま・マッサージ）';

      \Log::info("{$typeName}PDF生成開始", [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'record_type' => $recordType,
        'service_year_month' => $validated['service_year_month'],
        'submission_date' => $validated['submission_date'],
      ]);

      $serviceClass = $recordType === 'acupuncture'
        ? \App\Services\Print\TreatmentRecordAcupuncturePdfService::class
        : \App\Services\Print\TreatmentRecordMassagePdfService::class;

      $service = new $serviceClass();

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
      \Log::error("施術録PDF生成エラー", [
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
   * 医師への御礼状PDF出力
   */
  public function doctorThankYou(Request $request, string $filename)
  {
    $validated = $request->validate([
      'clinic_user_ids'   => 'required|array',
      'clinic_user_ids.*' => 'exists:clinic_users,id',
      'doctor_id'         => 'required|exists:doctors,id',
      'thank_you_option'  => 'required|in:consent,general',
      'submission_date'   => 'required|date',
    ]);

    // TODO: PDFサービス実装後にここで生成・返却
    abort(501, '医師への御礼状PDF生成は未実装です');
  }

  /**
   * 紹介者への御礼状PDF出力
   */
  public function referrerThankYou(Request $request, string $filename)
  {
    $validated = $request->validate([
      'clinic_user_ids'   => 'required|array',
      'clinic_user_ids.*' => 'exists:clinic_users,id',
      'caremanager_id'    => 'required|exists:caremanagers,id',
      'submission_date'   => 'required|date',
    ]);

    // TODO: PDFサービス実装後にここで生成・返却
    abort(501, '紹介者への御礼状PDF生成は未実装です');
  }

  /**
   * 利用者数集計表PDF出力
   */
  public function userCountSummary(Request $request, string $filename)
  {
    $validated = $request->validate([
      'service_year_month' => 'required|date_format:Y-m',
    ]);

    // TODO: PDFサービス実装後にここで生成・返却
    abort(501, '利用者数集計表PDF生成は未実装です');
  }
}
