<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfLayoutEditorController extends Controller
{
  /**
   * レイアウトエディター画面表示
   */
  public function index()
  {
    $pdfTypes = json_decode(Storage::get('config/pdf_types.json'), true);

    return view('pdf-layout-editor.index', [
      'pdfTypes' => $pdfTypes
    ]);
  }

  /**
   * 座標データ取得
   */
  public function getCoordinates(Request $request)
  {
    $coordinatesFile = $request->input('coordinatesFile');

    if (!$coordinatesFile) {
      return response()->json(['error' => '座標ファイルが指定されていません'], 400);
    }

    $filePath = "config/{$coordinatesFile}";

    if (!Storage::exists($filePath)) {
      return response()->json(['error' => '座標ファイルが見つかりません'], 404);
    }

    $coordinates = json_decode(Storage::get($filePath), true);

    return response()->json($coordinates);
  }

  /**
   * 座標データ保存
   */
  public function saveCoordinates(Request $request)
  {
    $coordinatesFile = $request->input('coordinatesFile');
    $coordinates = $request->input('coordinates');

    if (!$coordinatesFile || !$coordinates) {
      return response()->json(['error' => '必須パラメータが不足しています'], 400);
    }

    $filePath = "config/{$coordinatesFile}";

    // JSONを整形して保存
    $jsonContent = json_encode($coordinates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    Storage::put($filePath, $jsonContent);

    return response()->json(['message' => '保存が完了しました']);
  }

  /**
   * PDFテンプレート画像を取得（Base64）
   */
  public function getPdfImage(Request $request)
  {
    $templateFile = $request->input('templateFile');

    if (!$templateFile) {
      return response()->json(['error' => 'テンプレートファイルが指定されていません'], 400);
    }

    $filePath = "pdf_templates/{$templateFile}";

    if (!Storage::exists($filePath)) {
      return response()->json(['error' => 'テンプレートファイルが見つかりません'], 404);
    }

    // PDFの最初のページを画像として返す処理
    // 実装は環境に応じて調整（ImageMagick、GD等を使用）
    // ここでは簡易的にPDF自体を返す
    $pdfPath = Storage::path($filePath);

    return response()->json([
      'pdfPath' => $pdfPath,
      'message' => 'PDFパスを返しました。画像変換は別途実装が必要です。'
    ]);
  }
}
