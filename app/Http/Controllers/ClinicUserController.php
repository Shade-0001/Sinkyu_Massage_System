<?php
//-- app/Http/Controllers/ClinicUserController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClinicUser;
use App\Http\Requests\ClinicUserRequest;

/**
 * 利用者管理コントローラー
 *
 * 利用者の基本情報（氏名、住所、連絡先、往診距離等）のCRUD操作を担当する。
 * - 利用者の一覧・登録・編集・削除
 *
 * 保険情報、同意医師履歴、計画情報は別コントローラーで管理：
 * @see InsuranceController
 * @see ConsentMassageController
 * @see ConsentAcupunctureController
 * @see PlanController
 */
class ClinicUserController extends Controller
{
    /**
     * 利用者一覧を表示
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // DataTablesを使用するため、全件取得
        $clinicUsers = ClinicUser::orderBy('id', 'desc')->get();

        return view('master.clinic-users.clinic-users_index', [
            'clinicUsers' => $clinicUsers,
            'page_header_title' => '利用者',
        ]);
    }

    /**
     * 利用者新規登録フォーム
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('master.clinic-users.clinic-users_registration', [
            'mode' => 'create',
            'page_header_title' => '利用者‐登録 (新規)',
            'clinicUser' => null
        ]);
    }

    /**
     * 新規登録：確認画面の表示
     * 
     * @param ClinicUserRequest $request
     * @return \Illuminate\View\View
     */
    public function confirm(ClinicUserRequest $request)
    {
        $validated = $request->validated();

        // セッションに保存
        $request->session()->put('registration_data', $validated);

        // 確認画面のラベル設定
        $labels = $this->getLabels();

        // 表示用データ（姓名をまとめる）
        $displayData = $this->buildDisplayData($validated);

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.create',
            'store_route' => 'clinic-users.store',
            'page_header_title' => '利用者登録内容確認',
            'registration_message' => '利用者の登録を行います。',
        ]);
    }

    /**
     * 新規登録：データ保存処理
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // セッションからデータを取得
        $data = $request->session()->get('registration_data');

        if (!$data) {
            return redirect()->route('clinic-users.create')
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // データベースに保存
        $clinicUser = new ClinicUser();
        $clinicUser->fill($data);
        $clinicUser->save();

        // セッションをクリア
        $request->session()->forget('registration_data');

        return redirect()->route('clinic-users.index')
            ->with('success', 'データを登録しました。');
    }

    /**
     * 編集画面の表示
     * 
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $clinicUser = ClinicUser::findOrFail($id);
        
        return view('master.clinic-users.clinic-users_registration', [
            'mode' => 'edit',
            'page_header_title' => '利用者‐登録 (編集)',
            'clinicUser' => $clinicUser
        ]);
    }

    /**
     * 編集：確認画面の表示
     * 
     * @param ClinicUserRequest $request
     * @return \Illuminate\View\View
     */
    public function editConfirm(ClinicUserRequest $request)
    {
        $validated = $request->validated();

        // セッションに保存
        $request->session()->put('edit_data', $validated);

        // 確認画面のラベル設定
        $labels = $this->getLabels();

        // 表示用データ（姓名をまとめる）
        $displayData = $this->buildDisplayData($validated);

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.edit',
            'back_id' => $validated['id'],
            'store_route' => 'clinic-users.edit.update',
            'page_header_title' => '利用者更新内容確認',
            'registration_message' => '利用者の更新を行います。',
        ]);
    }

    /**
     * 編集：データ更新処理
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // セッションからデータを取得
        $data = $request->session()->get('edit_data');

        if (!$data) {
            return redirect()->route('clinic-users.index')
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // データベースを更新
        $clinicUser = ClinicUser::findOrFail($data['id']);
        $clinicUser->fill($data);
        $clinicUser->save();

        // セッションをクリア
        $request->session()->forget('edit_data');

        return redirect()->route('clinic-users.index')
            ->with('success', 'データを更新しました。');
    }

    /**
     * 削除処理
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $clinicUser = ClinicUser::findOrFail($id);
        $clinicUser->delete();

        return redirect()->route('clinic-users.index')
            ->with('success', 'データを削除しました。');
    }

    /**
     * 利用者のフィールドラベルを取得
     *
     * @return array フィールド名 => ラベル名の配列
     */
    private function buildDisplayData(array $validated): array
    {
        $data = $validated;
        $data['full_name'] = trim(($validated['last_name'] ?? '') . "\u{2000}" . ($validated['first_name'] ?? ''));
        $data['full_kana'] = trim(($validated['last_kana'] ?? '') . "\u{2000}" . ($validated['first_kana'] ?? ''));
        return $data;
    }

    private function getLabels()
    {
        return [
            'full_name' => '氏名',
            'full_kana' => 'フリガナ',
            'birthday' => '生年月日',
            'age' => '年齢',
            'gender_id' => '性別',
            'postal_code' => '郵便番号',
            'address_1' => '都道府県',
            'address_2' => '市区町村番地以下',
            'address_3' => 'アパート・マンション名等',
            'phone' => '電話番号',
            'cell_phone' => '携帯番号',
            'fax' => 'FAX番号',
            'email' => 'メールアドレス',
            'housecall_distance' => '往診距離（合計）',
            'housecall_additional_distance' => '往診加算距離',
            'is_redeemed' => '償還対象',
            'application_count' => '申請書提出開始回数[大阪市のみ]',
            'note' => 'メモ'
        ];
    }
}
