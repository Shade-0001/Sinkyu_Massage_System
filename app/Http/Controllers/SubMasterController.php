<?php
//-- app/Http/Controllers/SubMasterController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubMasterController extends Controller
{
  /**
   * サブマスター登録のインデックスページを表示
   */
  public function index()
  {
    // 各テーブルのレコード数を取得
    $counts = [
      'medical_institutions' => DB::table('medical_institutions')->count(),
      'service_providers' => DB::table('service_providers')->count(),
      'conditions' => DB::table('conditions')->count(),
      'illnesses_massage' => DB::table('illnesses_massage')->count(),
    ];

    return view('submaster.submaster_index', [
      'counts' => $counts,
      'page_header_title' => 'サブマスター登録'
    ]);
  }

  /**
   * 医療機関一覧ページを表示
   */
  public function medicalInstitutions()
  {
    $items = DB::table('medical_institutions')->orderBy('id')->get();
    return view('submaster.medical-institutions', [
      'items' => $items,
      'page_header_title' => '医療機関'
    ]);
  }

  /**
   * 医療機関を更新
   */
  public function updateMedicalInstitution(Request $request, $id)
  {
    $request->validate([
      'medical_institution_name' => 'required|string|max:255',
    ]);

    DB::table('medical_institutions')
      ->where('id', $id)
      ->update(['medical_institution_name' => $request->medical_institution_name]);

    return redirect()->route('submaster.medical-institutions')->with('success', 'データを更新しました。');
  }

  /**
   * 医療機関を新規登録
   */
  public function storeMedicalInstitution(Request $request)
  {
    $request->validate([
      'medical_institution_name' => 'required|string|max:255',
    ]);

    DB::table('medical_institutions')->insert(['medical_institution_name' => $request->medical_institution_name]);

    return redirect()->route('submaster.medical-institutions')->with('success', 'データを登録しました。');
  }

  /**
   * 医療機関を削除
   */
  public function destroyMedicalInstitution($id)
  {
    DB::table('medical_institutions')->where('id', $id)->delete();
    return redirect()->route('submaster.medical-institutions')->with('success', 'データを削除しました。');
  }

  /**
   * サービス提供者一覧ページを表示
   */
  public function serviceProviders()
  {
    $items = DB::table('service_providers')->orderBy('id')->get();
    return view('submaster.service-providers', [
      'items' => $items,
      'page_header_title' => 'サービス提供者'
    ]);
  }

  /**
   * サービス提供者を更新
   */
  public function updateServiceProvider(Request $request, $id)
  {
    $request->validate([
      'service_provider_name' => 'required|string|max:255',
    ]);

    DB::table('service_providers')
      ->where('id', $id)
      ->update(['service_provider_name' => $request->service_provider_name]);

    return redirect()->route('submaster.service-providers')->with('success', 'データを更新しました。');
  }

  /**
   * サービス提供者を新規登録
   */
  public function storeServiceProvider(Request $request)
  {
    $request->validate([
      'service_provider_name' => 'required|string|max:255',
    ]);

    DB::table('service_providers')->insert(['service_provider_name' => $request->service_provider_name]);

    return redirect()->route('submaster.service-providers')->with('success', 'データを登録しました。');
  }

  /**
   * サービス提供者を削除
   */
  public function destroyServiceProvider($id)
  {
    DB::table('service_providers')->where('id', $id)->delete();
    return redirect()->route('submaster.service-providers')->with('success', 'データを削除しました。');
  }

  /**
   * 状態一覧ページを表示
   */
  public function conditions()
  {
    $items = DB::table('conditions')->orderBy('id')->get();
    return view('submaster.conditions', [
      'items' => $items,
      'page_header_title' => '発病負傷経過（あんま・マッサージ）'
    ]);
  }

  /**
   * 状態を更新
   */
  public function updateCondition(Request $request, $id)
  {
    $request->validate([
      'condition_name' => 'required|string|max:255',
    ]);

    DB::table('conditions')
      ->where('id', $id)
      ->update(['condition_name' => $request->condition_name]);

    return redirect()->route('submaster.conditions')->with('success', 'データを更新しました。');
  }

  /**
   * 状態を新規登録
   */
  public function storeCondition(Request $request)
  {
    $request->validate([
      'condition_name' => 'required|string|max:255',
    ]);

    DB::table('conditions')->insert(['condition_name' => $request->condition_name]);

    return redirect()->route('submaster.conditions')->with('success', 'データを登録しました。');
  }

  /**
   * 状態を削除
   */
  public function destroyCondition($id)
  {
    DB::table('conditions')->where('id', $id)->delete();
    return redirect()->route('submaster.conditions')->with('success', 'データを削除しました。');
  }

  /**
   * 疾病一覧ページを表示
   */
  public function illnessesMassage()
  {
    $items = DB::table('illnesses_massage')->orderBy('id')->get();
    return view('submaster.illnesses-massage', [
      'items' => $items,
      'page_header_title' => '傷病名（あんま・マッサージ）'
    ]);
  }

  /**
   * 疾病を更新
   */
  public function updateIllnessMassage(Request $request, $id)
  {
    $request->validate([
      'illness_name' => 'required|string|max:255',
    ]);

    DB::table('illnesses_massage')
      ->where('id', $id)
      ->update(['illness_name' => $request->illness_name]);

    return redirect()->route('submaster.illnesses-massage')->with('success', 'データを更新しました。');
  }

  /**
   * 疾病を新規登録
   */
  public function storeIllnessMassage(Request $request)
  {
    $request->validate([
      'illness_name' => 'required|string|max:255',
    ]);

    DB::table('illnesses_massage')->insert(['illness_name' => $request->illness_name]);

    return redirect()->route('submaster.illnesses-massage')->with('success', 'データを登録しました。');
  }

  /**
   * 疾病を削除
   */
  public function destroyIllnessMassage($id)
  {
    DB::table('illnesses_massage')->where('id', $id)->delete();
    return redirect()->route('submaster.illnesses-massage')->with('success', 'データを削除しました。');
  }
}
