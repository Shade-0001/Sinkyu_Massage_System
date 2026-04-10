<?php
//-- app/Http/Controllers/SelfFeeController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelfFeeController extends Controller
{
  /**
   * 自費施術料金のインデックスページを表示
   */
  public function index()
  {
    $items = DB::table('self_fees')->orderBy('id')->get();
    return view('master.self-fees.self-fees_index', [
      'items' => $items,
      'page_header_title' => '自費施術料金編集'
    ]);
  }

  /**
   * 自費施術料金を更新
   */
  public function update(Request $request, $id)
  {
    $request->validate([
      'self_fee_name' => 'required|string|max:255',
      'amount' => 'required|integer|min:0',
    ]);

    DB::table('self_fees')
      ->where('id', $id)
      ->update([
        'self_fee_name' => $request->self_fee_name,
        'amount' => $request->amount,
      ]);

    return redirect()->route('master.self-fees.index')->with('success', 'データを更新しました。');
  }

  /**
   * 自費施術料金を新規登録
   */
  public function store(Request $request)
  {
    $request->validate([
      'self_fee_name' => 'required|string|max:255',
      'amount' => 'required|integer|min:0',
    ]);

    DB::table('self_fees')->insert([
      'self_fee_name' => $request->self_fee_name,
      'amount' => $request->amount,
    ]);

    return redirect()->route('master.self-fees.index')->with('success', 'データを登録しました。');
  }

  /**
   * 自費施術料金を削除
   */
  public function destroy($id)
  {
    DB::table('self_fees')->where('id', $id)->delete();
    return redirect()->route('master.self-fees.index')->with('success', 'データを削除しました。');
  }
}
