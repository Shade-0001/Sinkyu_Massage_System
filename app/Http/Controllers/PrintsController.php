<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Print\AcupunctureBenefitPdfService;
use Illuminate\Support\Facades\DB;

/**
 * 印刷メニューコントローラー
 */
class PrintsController extends Controller
{
  /**
   * 印刷メニューを表示
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    // 利用者一覧を取得してモーダル用に渡す
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('last_kana')
      ->get();

    return view('prints.prints_index', compact('clinicUsers'));
  }

  /**
   * はり・きゅう療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param AcupunctureBenefitPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function acupunctureBenefit(Request $request, AcupunctureBenefitPdfService $service, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date' => 'required|date',
      ]);

      \Log::info('PDF生成開始', [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'submission_date' => $validated['submission_date'],
      ]);

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['service_year_month'],
        $validated['submission_date']
      );

      \Log::info('PDF生成完了', ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('PDF生成エラー', [
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
   * あんま・マッサージ療養費支給申請書PDF出力
   *
   * @param Request $request
   * @param \App\Services\Print\MassageBenefitPdfService $service
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  public function massageBenefit(Request $request, \App\Services\Print\MassageBenefitPdfService $service, string $filename)
  {
    try {
      $validated = $request->validate([
        'clinic_user_ids' => 'required|array',
        'clinic_user_ids.*' => 'exists:clinic_users,id',
        'service_year_month' => 'required|date_format:Y-m',
        'submission_date' => 'required|date',
      ]);

      \Log::info('あんま・マッサージPDF生成開始', [
        'clinic_user_ids' => $validated['clinic_user_ids'],
        'service_year_month' => $validated['service_year_month'],
        'submission_date' => $validated['submission_date'],
      ]);

      $pdfBinary = $service->generate(
        $validated['clinic_user_ids'],
        $validated['service_year_month'],
        $validated['submission_date']
      );

      \Log::info('あんま・マッサージPDF生成完了', ['size' => strlen($pdfBinary)]);

      return response($pdfBinary, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline',
      ]);
    } catch (\Exception $e) {
      \Log::error('あんま・マッサージPDF生成エラー', [
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
    // PDFタイプをクエリパラメータから取得（デフォルト: therapy_benefit_acupuncture）
    $pdfType = $request->query('pdf_type', 'therapy_benefit_acupuncture');

    // PDFタイプ名を設定
    $pdfTypeName = $pdfType === 'therapy_benefit_massage' ? 'あんま・マッサージ療養費支給申請書' : 'はり・きゅう療養費支給申請書';

    // 利用者一覧を取得
    $clinicUsers = DB::table('clinic_users')
      ->select('id', 'last_name', 'first_name', 'last_kana', 'first_kana')
      ->orderBy('last_kana')
      ->get();

    // サンプルデータ用のマスタデータを取得
    $masterData = [
      'genders' => DB::table('gender')->select('id', 'gender')->get(),
      'relationships' => DB::table('relationships_with_clinic_user')->select('id', 'relationship')->get(),
      'insurance_types_1' => DB::table('insurance_types_1')->select('id', 'insurance_type_1')->get(),
      'insurance_types_3' => DB::table('insurance_types_3')->select('id', 'insurance_type_3')->get(),
    ];

    // 最新の施術料金データを取得
    $treatmentFees = DB::table('treatment_fees')
      ->orderBy('created_at', 'desc')
      ->first();

    return view('prints.coordinate_adjuster', compact('clinicUsers', 'pdfType', 'pdfTypeName', 'masterData', 'treatmentFees'));
  }

  /**
   * 現在の座標設定を取得
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCoordinates(Request $request)
  {
    // PDFタイプをクエリパラメータから取得（デフォルト: therapy_benefit_acupuncture）
    $pdfType = $request->query('pdf_type', 'therapy_benefit_acupuncture');
    $configFileName = $pdfType === 'therapy_benefit_massage' ? 'massage_benefit_coordinates.json' : 'acupuncture_benefit_coordinates.json';
    $configPath = storage_path('app/config/' . $configFileName);

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
      $configFileName = $pdfType === 'therapy_benefit_massage' ? 'massage_benefit_coordinates.json' : 'acupuncture_benefit_coordinates.json';
      $configPath = storage_path('app/config/' . $configFileName);

      // x=0, y=0のフィールドを除外（不要なフィールドを保存しない）
      $filteredCoordinates = [];
      $removedCount = 0;
      foreach ($coordinates as $key => $value) {
        if (isset($value['x']) && isset($value['y']) && $value['x'] === 0 && $value['y'] === 0) {
          $removedCount++;
          continue; // x=0, y=0のフィールドはスキップ
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
      // ログ：プレビューエンドポイントが呼ばれたことを記録
      \Log::info('PreviewPdf invoked', ['ip' => $request->ip(), 'has_coordinates' => $request->has('coordinates')]);

      // PDFタイプを取得
      $pdfType = $request->input('pdf_type', 'therapy_benefit_acupuncture');
      $configFileName = $pdfType === 'therapy_benefit_massage' ? 'massage_benefit_coordinates.json' : 'acupuncture_benefit_coordinates.json';
      $configPath = storage_path("app/config/{$configFileName}");

      // 一時的に座標設定を更新
      $coordinates = $request->input('coordinates');
      $originalCoordinates = file_get_contents($configPath);
      
      // x=0, y=0のフィールドを除外（描画されないようにする）
      $filteredCoordinates = [];
      $removedCount = 0;
      foreach ($coordinates as $k => $v) {
        if (isset($v['x']) && isset($v['y']) && $v['x'] === 0 && $v['y'] === 0) {
          $removedCount++;
          continue; // x=0, y=0のフィールドはスキップ
        }
        $filteredCoordinates[$k] = $v;
      }
      
      if ($removedCount > 0) {
        \Log::info("PreviewPdf: x=0,y=0フィールドを除外", ['removed_count' => $removedCount]);
      }
      
      // フィルタ後の座標を使用
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

      // サンプルデータ表示モードの確認
      $showSampleData = $request->input('show_sample_data', false);

      // ログ：letterSpacingが設定されているフィールドを確認（デバッグ用）
      try {
        foreach ($coordinates as $k => $v) {
          if (isset($v['letterSpacing']) && (float)$v['letterSpacing'] !== 0.0) {
            \Log::info('Preview coordinates letterSpacing', ['key' => $k, 'letterSpacing' => $v['letterSpacing']]);
          }
        }
      } catch (\Throwable $e) {
        \Log::warning('Preview letterSpacing logging failed', ['message' => $e->getMessage()]);
      }

      // リクエストから利用者IDを取得、なければ最初の利用者を使用
      $clinicUserId = $request->input('clinic_user_id');

      if ($clinicUserId) {
        $clinicUsers = [(int)$clinicUserId];
      } else {
        $clinicUsers = DB::table('clinic_users')->limit(1)->pluck('id')->toArray();
      }

      if (empty($clinicUsers)) {
        // 元の設定に戻す
        file_put_contents($configPath, $originalCoordinates);

        return response()->json([
          'error' => '利用者データが存在しません',
        ], 404);
      }

      // PDFタイプに応じてサービスを選択
      if ($pdfType === 'therapy_benefit_massage') {
        $service = new \App\Services\Print\MassageBenefitPdfService();
      } else {
        $service = new AcupunctureBenefitPdfService();
      }

      // サンプルデータ表示モードを設定
      if ($showSampleData && method_exists($service, 'setSampleDataMode')) {
        $service->setSampleDataMode(true);

        // カスタムサンプルデータがある場合は設定
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
        date('Y-m-d')
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
