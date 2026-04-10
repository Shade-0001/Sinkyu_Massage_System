<?php
//-- app/Http/Controllers/DoctorsController.php --//


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\DoctorRequest;

class DoctorsController extends Controller
{
  // 医師一覧表示
  public function index()
  {
    // DataTablesを使用するため、全件取得
    $doctors = DB::table('doctors')
      ->leftJoin('medical_institutions', 'doctors.medical_institutions_id', '=', 'medical_institutions.id')
      ->select(
        'doctors.*',
        'medical_institutions.medical_institution_name'
      )
      ->orderBy('doctors.id', 'desc')
      ->get();

    return view('master.doctors.doctors_index', compact('doctors'));
  }

  // 医師新規登録画面表示
  public function create()
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('doctors_registration_data');
    if ($sessionData) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('doctors_registration_data', $sessionData);
    }

    // 医療機関マスタを取得
    $medicalInstitutions = DB::table('medical_institutions')
      ->orderBy('medical_institution_name', 'asc')
      ->get();

    return view('master.doctors.doctors_registration', [
      'mode' => 'create',
      'page_header_title' => '医師‐登録 (新規)',
      'doctor' => null,
      'medicalInstitutions' => $medicalInstitutions
    ]);
  }

  // 医師新規登録：確認画面の表示
  public function confirm(DoctorRequest $request)
  {
    $validated = $request->validated();

    // セッションに保存
    $request->session()->put('doctors_registration_data', $validated);

    // 確認画面のラベル設定
    $labels = $this->getDoctorLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'doctors.create',
      'store_route' => 'doctors.store',
      'registration_message' => '医師の登録を行います。',
      'breadcrumb_name' => 'doctors.confirm',
      'page_header_title' => '医師‐登録 (新規)',
    ]);
  }

  // 医師新規登録処理
  public function store(Request $request)
  {
    // セッションからデータを取得
    $data = $request->session()->get('doctors_registration_data');

    if (!$data) {
      return redirect()->route('doctors.create')->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // 医療機関の新規登録処理
    $medicalInstitutionId = $data['medical_institutions_id'] ?? null;

    if (!$medicalInstitutionId && !empty($data['new_medical_institution_name'])) {
      $medicalInstitutionId = DB::table('medical_institutions')->insertGetId([
        'medical_institution_name' => $data['new_medical_institution_name'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // データ挿入
    DB::table('doctors')->insert([
      'last_name' => $data['last_name'],
      'first_name' => $data['first_name'] ?? null,
      'last_name_kana' => $data['last_name_kana'] ?? null,
      'first_name_kana' => $data['first_name_kana'] ?? null,
      'medical_institutions_id' => $medicalInstitutionId,
      'postal_code' => $data['postal_code'] ?? null,
      'address_1' => $data['address_1'] ?? null,
      'address_2' => $data['address_2'] ?? null,
      'address_3' => $data['address_3'] ?? null,
      'phone' => $data['phone'] ?? null,
      'cell_phone' => $data['cell_phone'] ?? null,
      'fax' => $data['fax'] ?? null,
      'email' => $data['email'] ?? null,
      'note' => $data['note'] ?? null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    // セッションから登録データを削除
    $request->session()->forget('doctors_registration_data');

    return redirect()->route('doctors.index')->with('success', 'データを登録しました。');
  }

  // 医師編集画面表示
  public function edit($id)
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('doctors_edit_data');
    $sessionId = session()->get('doctors_edit_id');
    if ($sessionData && $sessionId == $id) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('doctors_edit_data', $sessionData);
      session()->put('doctors_edit_id', $sessionId);
    }

    $doctor = DB::table('doctors')->where('id', $id)->first();

    if (!$doctor) {
      return redirect()->route('doctors.index')->with('error', '医師が見つかりません。');
    }

    $medicalInstitutions = DB::table('medical_institutions')
      ->orderBy('medical_institution_name', 'asc')
      ->get();

    return view('master.doctors.doctors_registration', [
      'mode' => 'edit',
      'page_header_title' => '医師‐登録 (編集)',
      'doctor' => $doctor,
      'medicalInstitutions' => $medicalInstitutions
    ]);
  }

  // 医師編集：確認画面の表示
  public function editConfirm(DoctorRequest $request, $id)
  {
    $validated = $request->validated();

    // セッションに保存
    $request->session()->put('doctors_edit_data', $validated);
    $request->session()->put('doctors_edit_id', $id);

    // 確認画面のラベル設定
    $labels = $this->getDoctorLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'doctors.edit',
      'back_id' => $id,
      'store_route' => 'doctors.update',
      'registration_message' => '',
      'breadcrumb_name' => 'doctors.edit.confirm',
      'page_header_title' => '医師‐登録 (編集)',
    ]);
  }

  // 医師更新処理
  public function update(Request $request, $id)
  {
    $data = $request->session()->get('doctors_edit_data');
    $sessionId = $request->session()->get('doctors_edit_id');

    if (!$data || $sessionId != $id) {
      return redirect()->route('doctors.edit', $id)->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // 医療機関の新規登録処理
    $medicalInstitutionId = $data['medical_institutions_id'] ?? null;

    if (!$medicalInstitutionId && !empty($data['new_medical_institution_name'])) {
      $medicalInstitutionId = DB::table('medical_institutions')->insertGetId([
        'medical_institution_name' => $data['new_medical_institution_name'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // データ更新
    DB::table('doctors')->where('id', $id)->update([
      'last_name' => $data['last_name'],
      'first_name' => $data['first_name'] ?? null,
      'last_name_kana' => $data['last_name_kana'] ?? null,
      'first_name_kana' => $data['first_name_kana'] ?? null,
      'medical_institutions_id' => $medicalInstitutionId,
      'postal_code' => $data['postal_code'] ?? null,
      'address_1' => $data['address_1'] ?? null,
      'address_2' => $data['address_2'] ?? null,
      'address_3' => $data['address_3'] ?? null,
      'phone' => $data['phone'] ?? null,
      'cell_phone' => $data['cell_phone'] ?? null,
      'fax' => $data['fax'] ?? null,
      'email' => $data['email'] ?? null,
      'note' => $data['note'] ?? null,
      'updated_at' => now(),
    ]);

    // セッションから編集データを削除
    $request->session()->forget('doctors_edit_data');
    $request->session()->forget('doctors_edit_id');

    return redirect()->route('doctors.index')->with('success', 'データを更新しました。');
  }

  // 医師複製画面表示
  public function duplicate($id)
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('doctors_duplicate_data');
    if ($sessionData) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('doctors_duplicate_data', $sessionData);
    }

    $doctor = DB::table('doctors')->where('id', $id)->first();

    if (!$doctor) {
      return redirect()->route('doctors.index')->with('error', '医師が見つかりません。');
    }

    $medicalInstitutions = DB::table('medical_institutions')
      ->orderBy('medical_institution_name', 'asc')
      ->get();

    return view('master.doctors.doctors_registration', [
      'mode' => 'duplicate',
      'page_header_title' => '医師‐登録 (複製)',
      'doctor' => $doctor,
      'medicalInstitutions' => $medicalInstitutions
    ]);
  }

  // 医師複製：確認画面の表示
  public function duplicateConfirm(DoctorRequest $request)
  {
    $validated = $request->validated();

    // セッションに保存
    $request->session()->put('doctors_duplicate_data', $validated);
    $request->session()->put('doctors_duplicate_source_id', $validated['source_doctor_id']);

    // 確認画面のラベル設定
    $labels = $this->getDoctorLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'doctors.duplicate',
      'back_id' => $validated['source_doctor_id'],
      'store_route' => 'doctors.duplicate.store',
      'registration_message' => '医師の複製登録を行います。',
      'breadcrumb_name' => 'doctors.duplicate.confirm',
      'page_header_title' => '医師‐登録 (複製)',
    ]);
  }

  // 医師複製登録処理
  public function duplicateStore(Request $request)
  {
    $data = $request->session()->get('doctors_duplicate_data');

    if (!$data) {
      return redirect()->route('doctors.index')->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // 医療機関の新規登録処理
    $medicalInstitutionId = $data['medical_institutions_id'] ?? null;

    if (!$medicalInstitutionId && !empty($data['new_medical_institution_name'])) {
      $medicalInstitutionId = DB::table('medical_institutions')->insertGetId([
        'medical_institution_name' => $data['new_medical_institution_name'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // データ挿入
    DB::table('doctors')->insert([
      'last_name' => $data['last_name'],
      'first_name' => $data['first_name'] ?? null,
      'last_name_kana' => $data['last_name_kana'] ?? null,
      'first_name_kana' => $data['first_name_kana'] ?? null,
      'medical_institutions_id' => $medicalInstitutionId,
      'postal_code' => $data['postal_code'] ?? null,
      'address_1' => $data['address_1'] ?? null,
      'address_2' => $data['address_2'] ?? null,
      'address_3' => $data['address_3'] ?? null,
      'phone' => $data['phone'] ?? null,
      'cell_phone' => $data['cell_phone'] ?? null,
      'fax' => $data['fax'] ?? null,
      'email' => $data['email'] ?? null,
      'note' => $data['note'] ?? null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    // セッションから複製データを削除
    $request->session()->forget('doctors_duplicate_data');

    return redirect()->route('doctors.index')->with('success', 'データを複製しました。');
  }

  // 医師のラベル取得
  private function getDoctorLabels()
  {
    return [
      'last_name' => '姓',
      'first_name' => '名',
      'last_name_kana' => 'セイ',
      'first_name_kana' => 'メイ',
      'new_medical_institution_name' => '新規医療機関名',
      'postal_code' => '郵便番号',
      'address_1' => '都道府県',
      'address_2' => '市区町村番地以下',
      'address_3' => 'アパート・マンション名等',
      'phone' => '電話番号',
      'cell_phone' => '携帯番号',
      'fax' => 'FAX番号',
      'email' => 'メールアドレス',
      'note' => 'メモ',
    ];
  }

  // 医師削除
  public function destroy($id)
  {
    DB::table('doctors')->where('id', $id)->delete();
    return redirect()->route('doctors.index')->with('success', 'データを削除しました。');
  }
}
