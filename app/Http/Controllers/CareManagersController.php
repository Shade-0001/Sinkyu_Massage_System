<?php
//-- app/Http/Controllers/CareManagersController.php --//


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CareManagerRequest;

class CareManagersController extends Controller
{
  // ケアマネ一覧表示
  public function index()
  {
    // DataTablesを使用するため、全件取得
    // service_providersテーブルとJOINしてサービス事業者名を取得
    $careManagers = DB::table('caremanagers')
      ->leftJoin('service_providers', 'caremanagers.service_providers_id', '=', 'service_providers.id')
      ->select(
        'caremanagers.*',
        'service_providers.service_provider_name'
      )
      ->orderBy('caremanagers.id', 'desc')
      ->get();

    return view('master.caremanagers.caremanagers_index', [
      'careManagers' => $careManagers,
      'page_header_title' => 'ケアマネ',
    ]);
  }

  // ケアマネ新規登録画面表示
  public function create()
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('caremanagers_registration_data');
    if ($sessionData) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('caremanagers_registration_data', $sessionData);
    }

    // サービス事業者一覧を取得
    $serviceProviders = DB::table('service_providers')
      ->orderBy('service_provider_name', 'asc')
      ->get();

    return view('master.caremanagers.caremanagers_registration', [
      'mode' => 'create',
      'page_header_title' => 'ケアマネ新規登録',
      'careManager' => null,
      'serviceProviders' => $serviceProviders
    ]);
  }

  // ケアマネ新規登録：確認画面の表示
  public function confirm(CareManagerRequest $request)
  {
    $validated = $request->validated();

    // service_provider_name_customが入力されている場合、確認画面用に表示データを設定
    if (!empty($validated['service_provider_name_custom'])) {
      $validated['service_provider_name_display'] = $validated['service_provider_name_custom'] . ' (新規登録)';
    }

    // セッションに保存
    $request->session()->put('caremanagers_registration_data', $validated);

    // 確認画面のラベル設定
    $labels = $this->getCareManagerLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'caremanagers.create',
      'store_route' => 'caremanagers.store',
      'page_header_title' => 'ケアマネ登録内容確認',
      'registration_message' => 'ケアマネの登録を行います。',
    ]);
  }

  // ケアマネ新規登録処理
  public function store(Request $request)
  {
    // セッションからデータを取得
    $data = $request->session()->get('caremanagers_registration_data');

    if (!$data) {
      return redirect()->route('caremanagers.create')->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // service_provider_name_customが入力されている場合、service_providersテーブルに新規登録
    $serviceProviderId = $data['service_providers_id'] ?? null;
    if (!empty($data['service_provider_name_custom'])) {
      $serviceProviderId = DB::table('service_providers')->insertGetId([
        'service_provider_name' => $data['service_provider_name_custom'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // データ挿入
    DB::table('caremanagers')->insert([
      'last_name' => $data['last_name'],
      'first_name' => $data['first_name'],
      'last_name_kana' => $data['last_name_kana'] ?? null,
      'first_name_kana' => $data['first_name_kana'] ?? null,
      'service_providers_id' => $serviceProviderId,
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
    $request->session()->forget('caremanagers_registration_data');

    return redirect()->route('caremanagers.index')->with('success', 'データを登録しました。');
  }

  // ケアマネ編集画面表示
  public function edit($id)
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('caremanagers_edit_data');
    $sessionId = session()->get('caremanagers_edit_id');
    if ($sessionData && $sessionId == $id) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('caremanagers_edit_data', $sessionData);
      session()->put('caremanagers_edit_id', $sessionId);
    }

    $careManager = DB::table('caremanagers')->where('id', $id)->first();

    if (!$careManager) {
      return redirect()->route('caremanagers.index')->with('error', 'ケアマネが見つかりません。');
    }

    // サービス事業者一覧を取得
    $serviceProviders = DB::table('service_providers')
      ->orderBy('service_provider_name', 'asc')
      ->get();

    return view('master.caremanagers.caremanagers_registration', [
      'mode' => 'edit',
      'page_header_title' => 'ケアマネ編集',
      'careManager' => $careManager,
      'serviceProviders' => $serviceProviders
    ]);
  }

  // ケアマネ編集：確認画面の表示
  public function editConfirm(CareManagerRequest $request, $id)
  {
    $validated = $request->validated();

    // service_provider_name_customが入力されている場合、確認画面用に表示データを設定
    if (!empty($validated['service_provider_name_custom'])) {
      $validated['service_provider_name_display'] = $validated['service_provider_name_custom'] . ' (新規登録)';
    }

    // セッションに保存
    $request->session()->put('caremanagers_edit_data', $validated);
    $request->session()->put('caremanagers_edit_id', $id);

    // 確認画面のラベル設定
    $labels = $this->getCareManagerLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'caremanagers.edit',
      'back_id' => $id,
      'store_route' => 'caremanagers.update',
      'page_header_title' => 'ケアマネ編集内容確認',
      'registration_message' => 'ケアマネの更新を行います。',
    ]);
  }

  // ケアマネ更新処理
  public function update(Request $request, $id)
  {
    $data = $request->session()->get('caremanagers_edit_data');
    $sessionId = $request->session()->get('caremanagers_edit_id');

    if (!$data || $sessionId != $id) {
      return redirect()->route('caremanagers.edit', $id)->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // service_provider_name_customが入力されている場合、service_providersテーブルに新規登録
    $serviceProviderId = $data['service_providers_id'] ?? null;
    if (!empty($data['service_provider_name_custom'])) {
      $serviceProviderId = DB::table('service_providers')->insertGetId([
        'service_provider_name' => $data['service_provider_name_custom'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // データ更新
    DB::table('caremanagers')->where('id', $id)->update([
      'last_name' => $data['last_name'],
      'first_name' => $data['first_name'],
      'last_name_kana' => $data['last_name_kana'] ?? null,
      'first_name_kana' => $data['first_name_kana'] ?? null,
      'service_providers_id' => $serviceProviderId,
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
    $request->session()->forget('caremanagers_edit_data');
    $request->session()->forget('caremanagers_edit_id');

    return redirect()->route('caremanagers.index')->with('success', 'データを更新しました。');
  }

  // ケアマネのラベル取得
  private function getCareManagerLabels()
  {
    return [
      'last_name' => '姓',
      'first_name' => '名',
      'last_name_kana' => 'セイ',
      'first_name_kana' => 'メイ',
      'service_providers_id' => 'サービス事業者名',
      'service_provider_name_display' => 'サービス事業者名',
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

  // ケアマネ削除
  public function destroy($id)
  {
    DB::table('caremanagers')->where('id', $id)->delete();
    return redirect()->route('caremanagers.index')->with('success', 'データを削除しました。');
  }
}
