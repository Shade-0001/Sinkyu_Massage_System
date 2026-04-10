<?php
//-- app/Http/Controllers/TreatmentFeeController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreatmentFeeController extends Controller
{
  /**
   * 施術料金一覧ページを表示
   */
  public function index()
  {
    $items = DB::table('treatment_fees')->orderBy('period_start', 'desc')->get();
    return view('master.treatment-fees.treatment-fees_index', [
      'items' => $items,
      'page_header_title' => '施術料金編集'
    ]);
  }

  /**
   * 施術料金新規登録フォームを表示
   */
  public function create()
  {
    return view('master.treatment-fees.treatment-fees_registration', [
      'mode' => 'create',
      'page_header_title' => '施術料金新規登録',
      'item' => (object)[]
    ]);
  }

  /**
   * 施術料金を新規登録
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'period_start' => 'required|date',
      'period_end' => 'required|date|after_or_equal:period_start',
      'hari_first' => 'required|integer|min:0',
      'hari_normal' => 'required|integer|min:0',
      'hari_and_elec_needle_first' => 'required|integer|min:0',
      'hari_and_elec_needle_normal' => 'required|integer|min:0',
      'kyu_first' => 'required|integer|min:0',
      'kyu_normal' => 'required|integer|min:0',
      'kyu_and_elec_moxa_heater_first' => 'required|integer|min:0',
      'kyu_and_elec_moxa_heater_normal' => 'required|integer|min:0',
      'hari_and_kyu_first' => 'required|integer|min:0',
      'hari_and_kyu_normal' => 'required|integer|min:0',
      'hari_and_kyu_elec_ray_first' => 'required|integer|min:0',
      'hari_and_kyu_elec_ray_normal' => 'required|integer|min:0',
      'housecall_max_2km_first' => 'required|integer|min:0',
      'housecall_max_2km_normal' => 'required|integer|min:0',
      'housecall_additional_max_4km_first' => 'required|integer|min:0',
      'housecall_additional_max_4km_normal' => 'required|integer|min:0',
      'massage_trunk_first' => 'required|integer|min:0',
      'massage_trunk_normal' => 'required|integer|min:0',
      'massage_upper_limb_r_first' => 'required|integer|min:0',
      'massage_upper_limb_r_normal' => 'required|integer|min:0',
      'massage_upper_limb_l_first' => 'required|integer|min:0',
      'massage_upper_limb_l_normal' => 'required|integer|min:0',
      'massage_lower_limb_r_first' => 'required|integer|min:0',
      'massage_lower_limb_r_normal' => 'required|integer|min:0',
      'massage_lower_limb_l_first' => 'required|integer|min:0',
      'massage_lower_limb_l_normal' => 'required|integer|min:0',
      'manual_correction_first' => 'required|integer|min:0',
      'manual_correction_normal' => 'required|integer|min:0',
      'fomentation_first' => 'required|integer|min:0',
      'fomentation_normal' => 'required|integer|min:0',
      'fomentation_and_elec_ray_first' => 'required|integer|min:0',
      'fomentation_and_elec_ray_normal' => 'required|integer|min:0',
    ]);

    DB::table('treatment_fees')->insert($validated);

    return redirect()->route('master.treatment-fees.index')->with('success', 'データを登録しました。');
  }

  /**
   * 施術料金編集フォームを表示
   */
  public function edit($id)
  {
    $item = DB::table('treatment_fees')->where('id', $id)->first();

    if (!$item) {
      return redirect()->route('master.treatment-fees.index')->with('error', 'データが見つからない');
    }

    return view('master.treatment-fees.treatment-fees_registration', [
      'mode' => 'edit',
      'page_header_title' => '施術料金編集',
      'item' => $item
    ]);
  }

  /**
   * 施術料金を更新
   */
  public function update(Request $request, $id)
  {
    $validated = $request->validate([
      'period_start' => 'required|date',
      'period_end' => 'required|date|after_or_equal:period_start',
      'hari_first' => 'required|integer|min:0',
      'hari_normal' => 'required|integer|min:0',
      'hari_and_elec_needle_first' => 'required|integer|min:0',
      'hari_and_elec_needle_normal' => 'required|integer|min:0',
      'kyu_first' => 'required|integer|min:0',
      'kyu_normal' => 'required|integer|min:0',
      'kyu_and_elec_moxa_heater_first' => 'required|integer|min:0',
      'kyu_and_elec_moxa_heater_normal' => 'required|integer|min:0',
      'hari_and_kyu_first' => 'required|integer|min:0',
      'hari_and_kyu_normal' => 'required|integer|min:0',
      'hari_and_kyu_elec_ray_first' => 'required|integer|min:0',
      'hari_and_kyu_elec_ray_normal' => 'required|integer|min:0',
      'housecall_max_2km_first' => 'required|integer|min:0',
      'housecall_max_2km_normal' => 'required|integer|min:0',
      'housecall_additional_max_4km_first' => 'required|integer|min:0',
      'housecall_additional_max_4km_normal' => 'required|integer|min:0',
      'massage_trunk_first' => 'required|integer|min:0',
      'massage_trunk_normal' => 'required|integer|min:0',
      'massage_upper_limb_r_first' => 'required|integer|min:0',
      'massage_upper_limb_r_normal' => 'required|integer|min:0',
      'massage_upper_limb_l_first' => 'required|integer|min:0',
      'massage_upper_limb_l_normal' => 'required|integer|min:0',
      'massage_lower_limb_r_first' => 'required|integer|min:0',
      'massage_lower_limb_r_normal' => 'required|integer|min:0',
      'massage_lower_limb_l_first' => 'required|integer|min:0',
      'massage_lower_limb_l_normal' => 'required|integer|min:0',
      'manual_correction_first' => 'required|integer|min:0',
      'manual_correction_normal' => 'required|integer|min:0',
      'fomentation_first' => 'required|integer|min:0',
      'fomentation_normal' => 'required|integer|min:0',
      'fomentation_and_elec_ray_first' => 'required|integer|min:0',
      'fomentation_and_elec_ray_normal' => 'required|integer|min:0',
    ]);

    DB::table('treatment_fees')
      ->where('id', $id)
      ->update($validated);

    return redirect()->route('master.treatment-fees.index')->with('success', 'データを更新しました。');
  }

  /**
   * 施術料金を削除
   */
  public function destroy($id)
  {
    DB::table('treatment_fees')->where('id', $id)->delete();
    return redirect()->route('master.treatment-fees.index')->with('success', 'データを削除しました。');
  }
}
