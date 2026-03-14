<?php
//-- app/Http/Controllers/CompanyInfoController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CompanyInfoRequest;

class CompanyInfoController extends Controller
{
  // 自社情報indexページ表示
  public function index()
  {
    // セッションに保存されたデータがあれば、それをフラッシュデータとして設定
    $sessionData = session()->get('company_info_data');
    if ($sessionData) {
      request()->merge($sessionData);
      session()->flashInput($sessionData);
      // セッションデータを保持（確認画面に戻った場合にも利用できるように）
      session()->put('company_info_data', $sessionData);
    }

    // 自社情報を取得（最新レコードを使用）
    $companyInfo = DB::table('clinic_info')->orderByDesc('id')->first();

    // 都道府県リストを作成
    $prefectures = $this->getPrefectures();

    // 銀行口座種別の選択肢
    $bankAccountTypes = ['普通', '当座'];

    // 保健所登録場所の選択肢
    $healthCenterLocations = ['施術所所在地', '出張専門施術者所在地'];

    // 帳票フォーマットの選択肢
    $documentFormats = ['標準2013', '神奈川2013', '大阪', '福岡', '愛知', '茨城'];

    return view('master.clinic-info.clinic-info_index', [
      'companyInfo' => $companyInfo,
      'prefectures' => $prefectures,
      'bankAccountTypes' => $bankAccountTypes,
      'healthCenterLocations' => $healthCenterLocations,
      'documentFormats' => $documentFormats,
      'page_header_title' => '自社情報'
    ]);
  }

  // 自社情報登録：確認画面の表示
  public function confirm(CompanyInfoRequest $request)
  {
    $validated = $request->validated();

    // セッションに保存
    $request->session()->put('company_info_data', $validated);

    // 確認画面のラベル設定
    $labels = $this->getCompanyInfoLabels();

    return view('registration-review', [
      'data' => $validated,
      'labels' => $labels,
      'back_route' => 'clinic-info.index',
      'store_route' => 'clinic-info.store',
      'page_header_title' => '自社情報登録内容確認',
      'registration_message' => '自社情報の登録を行います。',
    ]);
  }

  // 自社情報登録・更新処理
  public function store(Request $request)
  {
    // セッションからデータを取得
    $data = $request->session()->get('company_info_data');

    if (!$data) {
      return redirect()->route('clinic-info.index')->with('error', 'セッションが切れました。もう一度入力してください。');
    }

    // 常に新規登録処理
    DB::table('clinic_info')->insert([
      'clinic_name' => $data['clinic_name'] ?? null,
      'owner_last_name' => $data['owner_last_name'] ?? null,
      'owner_first_name' => $data['owner_first_name'] ?? null,
      'owner_birthday' => $data['owner_birthday'] ?? null,
      'postal_code' => $data['postal_code'] ?? null,
      'address_1' => $data['address_1'] ?? null,
      'address_2' => $data['address_2'] ?? null,
      'address_3' => $data['address_3'] ?? null,
      'phone' => $data['phone'] ?? null,
      'cellphone' => $data['cellphone'] ?? null,
      'freephone' => $data['freephone'] ?? null,
      'fax' => $data['fax'] ?? null,
      'email' => $data['email'] ?? null,
      'website_url' => $data['website_url'] ?? null,
      'business_hours_start' => $data['business_hours_start'] ?? null,
      'business_hours_end' => $data['business_hours_end'] ?? null,
      'closed_day_monday' => isset($data['closed_day_monday']) ? 1 : 0,
      'closed_day_tuesday' => isset($data['closed_day_tuesday']) ? 1 : 0,
      'closed_day_wednesday' => isset($data['closed_day_wednesday']) ? 1 : 0,
      'closed_day_thursday' => isset($data['closed_day_thursday']) ? 1 : 0,
      'closed_day_friday' => isset($data['closed_day_friday']) ? 1 : 0,
      'closed_day_saturday' => isset($data['closed_day_saturday']) ? 1 : 0,
      'closed_day_sunday' => isset($data['closed_day_sunday']) ? 1 : 0,
      'bank_account_type' => $data['bank_account_type'] ?? null,
      'bank_name' => $data['bank_name'] ?? null,
      'bank_branch_name' => $data['bank_branch_name'] ?? null,
      'bank_account_name' => $data['bank_account_name'] ?? null,
      'bank_account_name_kana' => $data['bank_account_name_kana'] ?? null,
      'bank_code' => $data['bank_code'] ?? null,
      'bank_branch_code' => $data['bank_branch_code'] ?? null,
      'bank_account_number' => $data['bank_account_number'] ?? null,
      'health_center_registerd_location' => $data['health_center_registerd_location'] ?? null,
      'license_hari_number' => $data['license_hari_number'] ?? null,
      'license_hari_issued_date' => $data['license_hari_issued_date'] ?? null,
      'license_kyu_number' => $data['license_kyu_number'] ?? null,
      'license_kyu_issued_date' => $data['license_kyu_issued_date'] ?? null,
      'license_massage_number' => $data['license_massage_number'] ?? null,
      'license_massage_issued_date' => $data['license_massage_issued_date'] ?? null,
      'billing_prefecture' => $data['billing_prefecture'] ?? null,
      'therapist_number' => $data['therapist_number'] ?? null,
      'medical_institution_number' => $data['medical_institution_number'] ?? null,
      'should_round_amount' => isset($data['should_round_amount']) ? 1 : 0,
      'document_formats' => $data['document_formats'] ?? null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    $message = '自社情報を登録しました。';

    // セッションから登録データを削除
    $request->session()->forget('company_info_data');

    return redirect()->route('clinic-info.index')->with('success', $message);
  }

  // 都道府県リストを取得
  private function getPrefectures()
  {
    return [
      '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
      '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
      '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
      '岐阜県', '静岡県', '愛知県', '三重県',
      '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
      '鳥取県', '島根県', '岡山県', '広島県', '山口県',
      '徳島県', '香川県', '愛媛県', '高知県',
      '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ];
  }

  // 自社情報のラベル取得
  private function getCompanyInfoLabels()
  {
    return [
      'clinic_name' => '事業所名',
      'owner_last_name' => '代表者氏名（姓）',
      'owner_first_name' => '代表者氏名（名）',
      'owner_birthday' => '代表者生年月日',
      'postal_code' => '郵便番号',
      'address_1' => '都道府県',
      'address_2' => '市区町村番地以下',
      'address_3' => 'アパート・マンション名等',
      'phone' => '電話番号',
      'cellphone' => '携帯番号',
      'freephone' => 'フリーダイヤル',
      'fax' => 'FAX番号',
      'email' => 'メールアドレス',
      'website_url' => 'ホームページURL',
      'business_hours_start' => '営業時間（開始）',
      'business_hours_end' => '営業時間（終了）',
      'closed_day_monday' => '定休日（月曜日）',
      'closed_day_tuesday' => '定休日（火曜日）',
      'closed_day_wednesday' => '定休日（水曜日）',
      'closed_day_thursday' => '定休日（木曜日）',
      'closed_day_friday' => '定休日（金曜日）',
      'closed_day_saturday' => '定休日（土曜日）',
      'closed_day_sunday' => '定休日（日曜日）',
      'bank_account_type' => '預金種類',
      'bank_name' => '銀行名',
      'bank_branch_name' => '支店名',
      'bank_account_name' => '口座名義',
      'bank_account_name_kana' => '口座名義（カナ）',
      'bank_code' => '銀行コード',
      'bank_branch_code' => '支店コード',
      'bank_account_number' => '口座番号',
      'health_center_registerd_location' => '保健所登録分',
      'license_hari_number' => 'はり師免許番号',
      'license_hari_issued_date' => 'はり師免許交付年月日',
      'license_kyu_number' => 'きゅう師免許番号',
      'license_kyu_issued_date' => 'きゅう師免許交付年月日',
      'license_massage_number' => 'あん摩・マッサージ師免許番号',
      'license_massage_issued_date' => 'あん摩・マッサージ師免許交付年月日',
      'billing_prefecture' => '請求先都道府県',
      'therapist_number' => '施術者付与 (登録) 番号',
      'medical_institution_number' => '施術機関番号',
      'should_round_amount' => '領収書発行時の領収金額の四捨五入',
      'document_formats' => '申請書等の書式選択',
    ];
  }
}
