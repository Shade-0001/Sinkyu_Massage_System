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
   * 座標調整管理画面を表示
   *
   * @return \Illuminate\View\View
   */
  public function coordinateAdjuster()
  {
    return view('prints.coordinate_adjuster');
  }

  /**
   * 現在の座標設定を取得
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCoordinates()
  {
    $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');

    if (file_exists($configPath)) {
      $json = file_get_contents($configPath);
      $coordinates = json_decode($json, true);

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

      $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');
      file_put_contents($configPath, json_encode($coordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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
   * @return \Illuminate\Http\Response
   */
  public function previewPdf(Request $request)
  {
    try {
      // ログ：プレビューエンドポイントが呼ばれたことを記録
      \Log::info('PreviewPdf invoked', ['ip' => $request->ip(), 'has_coordinates' => $request->has('coordinates')]);

      // 一時的に座標設定を更新
      $coordinates = $request->input('coordinates');
      $configPath = storage_path('app/config/acupuncture_benefit_coordinates.json');
      $originalCoordinates = file_get_contents($configPath);

      // 一時保存
      file_put_contents($configPath, json_encode($coordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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

      // サンプルデータでPDF生成
      $clinicUsers = DB::table('clinic_users')->limit(1)->pluck('id')->toArray();

      if (empty($clinicUsers)) {
        // 元の設定に戻す
        file_put_contents($configPath, $originalCoordinates);

        return response()->json([
          'error' => '利用者データが存在しません',
        ], 404);
      }

      // 座標ファイル更新後に新しいインスタンスを生成
      $service = new AcupunctureBenefitPdfService();

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
