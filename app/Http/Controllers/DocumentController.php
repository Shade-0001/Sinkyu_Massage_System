<?php
//-- app/Http/Controllers/DocumentController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
  /**
   * 文書のインデックスページを表示
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
    // document_templatesテーブルからカテゴリ一覧を取得
    $categories = DB::table('document_templates')
      ->select('document_category')
      ->distinct()
      ->whereNotNull('document_category')
      ->orderBy('document_category')
      ->pluck('document_category');

    return view('master.documents.documents_registration', [
      'mode' => 'create',
      'page_header_title' => '文書新規登録',
      'categories' => $categories
    ]);
  }


  /**
   * 編集フォームを表示
   */
  public function edit($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      return redirect()->route('master.documents.index')->with('error', '文書が見つからない。');
    }

    // カテゴリ一覧を取得
    $categories = DB::table('document_templates')
      ->select('document_category')
      ->distinct()
      ->whereNotNull('document_category')
      ->orderBy('document_category')
      ->pluck('document_category');

    return view('master.documents.documents_registration', [
      'mode' => 'edit',
      'page_header_title' => '文面編集',
      'document' => $document,
      'categories' => $categories
    ]);
  }

  /**
   * 文書を更新
   */
  public function update(Request $request, $id)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) use ($id) {
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->where('id', '!=', $id)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string|max:2000',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|integer',
    ]);

    DB::table('documents')
      ->where('id', $id)
      ->update([
        'document_category' => $request->document_category,
        'document_name' => $request->document_name,
        'content' => $request->content,
        'font_size' => $request->font_size ?? 12,
        'line_height' => $request->line_height ?? 7,
        'updated_at' => now(),
      ]);

    return redirect()->route('master.documents.index')->with('success', '文書情報を更新完了。');
  }

  /**
   * 文書を新規登録
   */
  public function store(Request $request)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) {
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string|max:2000',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|integer',
    ]);

    DB::table('documents')->insert([
      'document_category' => $request->category,
      'document_name' => $request->name,
      'content' => $request->content,
      'font_size' => $request->font_size ?? 12,
      'line_height' => $request->line_height ?? 7,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return redirect()->route('master.documents.index')->with('success', '登録完了');
  }

  /**
   * 文書を削除
   */
  public function destroy($id)
  {
    DB::table('documents')->where('id', $id)->delete();
    return redirect()->route('master.documents.index')->with('success', '削除完了');
  }

  /**
   * 文書複製画面表示
   */
  public function duplicate($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      return redirect()->route('master.documents.index')->with('error', '文書が見つからない。');
    }

    // カテゴリ一覧を取得
    $categories = DB::table('document_templates')
      ->select('document_category')
      ->distinct()
      ->whereNotNull('document_category')
      ->orderBy('document_category')
      ->pluck('document_category');

    return view('master.documents.documents_registration', [
      'mode' => 'duplicate',
      'page_header_title' => '文書複製',
      'document' => $document,
      'categories' => $categories
    ]);
  }

  /**
   * 文書複製登録処理
   */
  public function duplicateStore(Request $request)
  {
    $request->validate([
      'document_category' => 'required|string|max:255',
      'document_name' => [
        'required',
        'string',
        'max:255',
        function ($attribute, $value, $fail) {
          $exists = DB::table('documents')
            ->where('document_name', $value)
            ->exists();
          if ($exists) {
            $fail('既存の文書名称と重複。文書名称を変更が必要。');
          }
        }
      ],
      'content' => 'required|string|max:2000',
      'font_size' => 'nullable|integer',
      'line_height' => 'nullable|integer',
    ]);

    DB::table('documents')->insert([
      'category' => $request->category,
      'name' => $request->name,
      'content' => $request->content,
      'font_size' => $request->font_size ?? 12,
      'line_height' => $request->line_height ?? 7,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return redirect()->route('master.documents.index')->with('success', '文書を複製登録完了。');
  }

  /**
   * 文書のプレビューを表示
   */
  public function preview($id)
  {
    $document = DB::table('documents')->where('id', $id)->first();

    if (!$document) {
      abort(404, '文書が見つかりません');
    }

    // clinic_infoテーブルから事業所情報を取得
    $clinicInfo = DB::table('clinic_info')->first();

    // カテゴリに応じてテンプレートビューを決定
    $viewName = 'master.documents.templates.request_doc'; // デフォルト

    // カテゴリごとのテンプレートマッピング（今後拡張可能）
    $categoryTemplateMap = [
      '依頼状' => 'master.documents.templates.request_doc',
      '報告書' => 'master.documents.templates.request_doc', // 今は同じテンプレート
      '計画書' => 'master.documents.templates.request_doc', // 今は同じテンプレート
    ];

    if (isset($categoryTemplateMap[$document->document_category])) {
      $viewName = $categoryTemplateMap[$document->document_category];
    }

    // TCPDFを使用してPDFを生成
    $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');

    // PDFメタデータ設定
    $pdf->SetCreator('Sinkyu Massage System');
    $pdf->SetAuthor('System');
    $pdf->SetTitle($document->document_name ?? 'Document');

    // ヘッダー・フッターを削除
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // 日本語フォント設定
    $pdf->SetFont('kozgopromedium', '', 12);

    // マージン設定（テンプレート内で margin を設定しているため、ここでは 0 に設定）
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(TRUE, 0);

    // ページ追加
    $pdf->AddPage();

    // HTMLコンテンツを生成
    $html = view($viewName, compact('document', 'clinicInfo'))->render();

    // HTMLをPDFに出力
    $pdf->writeHTML($html, true, false, true, false, '');

    // PDFを新規ウィンドウで表示
    $filename = ($document->document_name ?? 'document') . '.pdf';
    return response($pdf->Output($filename, 'I'))
      ->header('Content-Type', 'application/pdf');
  }

  /**
   * 文書名称の重複チェック（Ajax用）
   */
  public function checkDuplicateName(Request $request)
  {
    $name = $request->input('document_name');
    $excludeId = $request->input('exclude_id'); // 編集時は自分自身を除外

    $query = DB::table('documents')->where('document_name', $name);

    // 編集時は自分自身のIDを除外
    if ($excludeId) {
      $query->where('id', '!=', $excludeId);
    }

    $exists = $query->exists();

    return response()->json(['exists' => $exists]);
  }
}
