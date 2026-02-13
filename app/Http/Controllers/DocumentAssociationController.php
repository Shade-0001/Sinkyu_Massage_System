<?php
//-- app/Http/Controllers/DocumentAssociationController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentAssociationController extends Controller
{
  /**
   * 標準文書の確認および関連付けのインデックスページを表示
   */
  public function index()
  {
    // カテゴリをハードコード定義
    $categories = [
      '依頼状',
      '御礼状',
      '挨拶状',
      '資料',
    ];

    // 文書カラムの項目をハードコード定義（idはdocument_id_1として使用）
    $fixedDocuments = [
      // 依頼状カテゴリ
      ['id' => 1, 'document_category' => '依頼状', 'document_name' => '同意書依頼（サンプル版）はり・きゅう'],
      ['id' => 2, 'document_category' => '依頼状', 'document_name' => '同意書依頼（サンプル版）あんま・マッサージ'],
      ['id' => 3, 'document_category' => '依頼状', 'document_name' => '同意書依頼（医師指定）はり・きゅう'],
      ['id' => 4, 'document_category' => '依頼状', 'document_name' => '同意書依頼（医師指定）あんま・マッサージ'],

      // 御礼状カテゴリ
      ['id' => 5, 'document_category' => '御礼状', 'document_name' => '医師への御礼状（同意書発行）'],
      ['id' => 6, 'document_category' => '御礼状', 'document_name' => '医師への御礼状（一般）'],
      ['id' => 7, 'document_category' => '御礼状', 'document_name' => '紹介者への御礼状'],

      // 挨拶状カテゴリ
      ['id' => 8, 'document_category' => '挨拶状', 'document_name' => '報告書挨拶状（医師向け）'],
      ['id' => 9, 'document_category' => '挨拶状', 'document_name' => '報告書挨拶状（ケアマネ向け）'],
      ['id' => 10, 'document_category' => '挨拶状', 'document_name' => '報告書挨拶状（利用者向け）'],
      ['id' => 11, 'document_category' => '挨拶状', 'document_name' => '挨拶状'],

      // 資料カテゴリ
      ['id' => 12, 'document_category' => '資料', 'document_name' => '療養費支給申請書の代理作成について'],
      ['id' => 13, 'document_category' => '資料', 'document_name' => '日常生活評価票'],
      ['id' => 14, 'document_category' => '資料', 'document_name' => '代理受領について'],
      ['id' => 15, 'document_category' => '資料', 'document_name' => '療養費の償還払い制度について'],
    ];

    // documentsテーブルから全文書を取得（セレクトボックス用）
    $documents = DB::table('documents')->orderBy('id')->get();

    // document_associationから既存の関連付けを取得
    $associations = DB::table('document_association')->get()->keyBy('document_id_1');

    return view('master.document-association.document-association_index', [
      'categories' => $categories,
      'fixedDocuments' => $fixedDocuments,
      'documents' => $documents,
      'associations' => $associations,
      'page_header_title' => '標準文書の確認および関連付け'
    ]);
  }

  /**
   * 標準文書を関連付け
   */
  public function associate(Request $request, $id)
  {
    $request->validate([
      'document_id_2' => 'nullable|integer',
    ]);

    // 既存の関連付けがあるかチェック
    $existing = DB::table('document_association')
      ->where('document_id_1', $id)
      ->first();

    $now = now();

    if ($existing) {
      // 更新
      DB::table('document_association')
        ->where('document_id_1', $id)
        ->update([
          'document_id_2' => $request->document_id_2,
          'updated_at' => $now,
        ]);
    } else {
      // 新規作成
      DB::table('document_association')->insert([
        'document_id_1' => $id,
        'document_id_2' => $request->document_id_2,
        'created_at' => $now,
        'updated_at' => $now,
      ]);
    }

    return redirect()->route('master.document-association.index')->with('success', '関連付け完了');
  }
}
