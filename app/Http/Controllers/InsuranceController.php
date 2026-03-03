<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClinicUser;
use App\Models\Insurance;
use App\Models\Insurer;
use App\Http\Requests\InsuranceRequest;
use App\Services\InsuranceDataConverter;
use App\Services\InsurerService;
use App\Http\Traits\SessionConfirmationTrait;

/**
 * 保険情報管理コントローラー
 * 
 * 利用者に紐づく医療保険情報のCRUD操作を担当する。
 * - 保険情報の一覧・登録・編集・削除
 * - 保険情報の複製
 * - 保険情報履歴の印刷
 */
class InsuranceController extends Controller
{
    use SessionConfirmationTrait;

    protected $insuranceDataConverter;
    protected $insurerService;

    public function __construct(
        InsuranceDataConverter $insuranceDataConverter,
        InsurerService $insurerService
    ) {
        $this->insuranceDataConverter = $insuranceDataConverter;
        $this->insurerService = $insurerService;
    }

    /**
     * 保険情報一覧を表示
     */
    public function index($id)
    {
        $user = ClinicUser::findOrFail($id);

        // DataTablesを使用するため、全件取得
        $insurances = Insurance::where('clinic_user_id', $id)
            ->with('insurer')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('clinic-users.insurances.insurances_index', [
            'id' => $id,
            'name' => $user->full_name,
            'insurances' => $insurances,
            'page_header_title' => '保険情報'
        ]);
    }

    /**
     * 保険情報新規登録画面
     */
    public function create($id)
    {
        $user = ClinicUser::findOrFail($id);
        $insurers = Insurer::all();

        // セッションからデータを取得して old() にセット
        $sessionData = session('insurances_registration_data');
        if ($sessionData) {
            // セッションデータを再度フラッシュして old() で利用可能にする
            request()->merge($sessionData);
            session()->flashInput($sessionData);
            // セッションデータを保持（確認画面に戻った場合にも利用できるように）
            session()->put('insurances_registration_data', $sessionData);
        }

        return view('clinic-users.insurances.insurances_registration', [
            'mode' => 'create',
            'page_header_title' => $user->last_name . '  ' . $user->first_name . '  様の保険情報新規登録',
            'userId' => $id,
            'insurance' => null,
            'insurers' => $insurers
        ]);
    }

    /**
     * 保険情報新規登録：確認画面の表示
     */
    public function confirm(InsuranceRequest $request, $id)
    {
        $validated = $request->validated();

        // セッションに保存
        $request->session()->put('insurances_registration_data', $validated);

        // 確認画面のラベル設定
        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $validated,
            'labels' => $labels,
            'back_route' => 'clinic-users.insurances.create',
            'back_id' => $id,
            'store_route' => 'clinic-users.insurances.store',
            'page_header_title' => '保険情報登録内容確認',
            'registration_message' => '保険情報の登録を行います。',
        ]);
    }

    /**
     * 保険情報新規登録
     */
    public function store(Request $request, $id)
    {
        // セッションからデータを取得
        $data = $request->session()->get('insurances_registration_data');

        if (!$data) {
            return redirect()->route('clinic-users.insurances.create', $id)
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // 保険者IDを取得または新規作成
        $insurersId = $this->insurerService->getOrCreateInsurerId($data);

        // データ変換（共通サービス使用）
        $saveData = $this->insuranceDataConverter->convertToIds($data, [
            'clinic_user_id' => $id,
            'insurers_id' => $insurersId
        ]);

        // 保険情報保存
        $insurance = new Insurance();
        $insurance->fill($saveData);
        $insurance->save();

        // セッションをクリア
        $request->session()->forget('insurances_registration_data');

        return view('registration-done', [
            'page_header_title' => '保険情報登録完了',
            'message' => '保険情報を登録しました。',
            'index_route' => 'clinic-users.insurances.index',
            'index_id' => $id,
            'list_route' => null
        ])->with('index_id', $id);
    }

    /**
     * 保険情報編集画面
     */
    public function edit($id, $insurance_id)
    {
        $user = ClinicUser::findOrFail($id);
        $insurance = Insurance::with('insurer')->findOrFail($insurance_id);
        $insurers = Insurer::all();

        return view('clinic-users.insurances.insurances_registration', [
            'mode' => 'edit',
            'page_header_title' => $user->last_name . '  ' . $user->first_name . '  様の保険情報編集',
            'userId' => $id,
            'insurance' => $insurance,
            'insurers' => $insurers
        ]);
    }

    /**
     * 保険情報編集：確認画面の表示
     */
    public function editConfirm(InsuranceRequest $request, $id, $insurance_id)
    {
        $validated = $request->validated();

        // 保険情報IDをセッションに保存
        $validated['insurance_id'] = $insurance_id;

        // セッションに保存
        $request->session()->put('insurances_edit_data', $validated);

        // 確認画面のラベル設定
        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $validated,
            'labels' => $labels,
            'back_route' => 'clinic-users.insurances.edit',
            'back_id' => $id,
            'back_insurance_id' => $insurance_id,
            'store_route' => 'clinic-users.insurances.edit.update',
            'page_header_title' => '保険情報更新内容確認',
            'registration_message' => '保険情報の更新を行います。',
        ]);
    }

    /**
     * 保険情報編集：更新処理
     */
    public function update(Request $request, $id, $insurance_id)
    {
        // セッションからデータを取得
        $data = $request->session()->get('insurances_edit_data');

        if (!$data) {
            return redirect()->route('clinic-users.insurances.edit', [$id, $insurance_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $insurance = Insurance::findOrFail($insurance_id);

        // 保険者IDを取得または新規作成
        $insurersId = $this->insurerService->getOrCreateInsurerId($data, $insurance->insurers_id);

        // データ変換（共通サービス使用）
        $saveData = $this->insuranceDataConverter->convertToIds($data, ['insurers_id' => $insurersId]);

        $insurance->fill($saveData);
        $insurance->save();

        // セッションをクリア
        $request->session()->forget('insurances_edit_data');

        return redirect()->route('clinic-users.insurances.index', $id)
            ->with('success', '保険情報を更新しました。');
    }

    /**
     * 保険情報複製：フォーム表示
     */
    public function duplicateForm($id, $insurance_id)
    {
        $user = ClinicUser::findOrFail($id);
        $insurance = Insurance::with('insurer')->findOrFail($insurance_id);
        $insurers = Insurer::all();

        return view('clinic-users.insurances.insurances_registration', [
            'mode' => 'duplicate',
            'page_header_title' => $user->last_name . '  ' . $user->first_name . '  様の保険情報複製',
            'userId' => $id,
            'insurance' => $insurance,
            'insurers' => $insurers
        ]);
    }

    /**
     * 保険情報複製：確認画面の表示
     */
    public function duplicateConfirm(InsuranceRequest $request, $id, $insurance_id)
    {
        $validated = $request->validated();

        // 保険情報IDをセッションに保存
        $validated['insurance_id'] = $insurance_id;

        // セッションに保存
        $request->session()->put('insurances_duplicate_data', $validated);

        // 確認画面のラベル設定
        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $validated,
            'labels' => $labels,
            'back_route' => 'clinic-users.insurances.duplicate',
            'back_id' => $id,
            'back_insurance_id' => $insurance_id,
            'store_route' => 'clinic-users.insurances.duplicate.store',
            'page_header_title' => '保険情報複製内容確認',
            'registration_message' => '保険情報の複製を行います。',
        ]);
    }

    /**
     * 保険情報複製：実行
     */
    public function duplicateStore(Request $request, $id, $insurance_id)
    {
        // セッションからデータを取得
        $data = $request->session()->get('insurances_duplicate_data');

        if (!$data) {
            return redirect()->route('clinic-users.insurances.duplicate', [$id, $insurance_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // 保険者IDを取得または新規作成
        $insurersId = $this->insurerService->getOrCreateInsurerId($data);

        // データ変換（共通サービス使用）
        $saveData = $this->insuranceDataConverter->convertToIds($data, [
            'clinic_user_id' => $id,
            'insurers_id' => $insurersId
        ]);

        // 保険情報保存
        $insurance = new Insurance();
        $insurance->fill($saveData);
        $insurance->save();

        // セッションをクリア
        $request->session()->forget('insurances_duplicate_data');

        return redirect()->route('clinic-users.insurances.index', $id)
            ->with('success', '保険情報を複製しました。');
    }

    /**
     * 保険情報削除
     */
    public function destroy($id, $insurance_id)
    {
        $insurance = Insurance::findOrFail($insurance_id);
        $insurance->delete();

        return redirect()->route('clinic-users.insurances.index', $id)
            ->with('success', '保険情報を削除しました。');
    }

    /**
     * 医療保険履歴印刷
     */
    public function print($id)
    {
        $user = ClinicUser::findOrFail($id);

        // 保険情報を新しい順に取得
        $insurances = Insurance::where('clinic_user_id', $id)
            ->with('insurer')
            ->orderBy('created_at', 'desc')
            ->get();

        // TCPDFを使用してPDFを生成
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8');

        // PDFメタデータ設定
        $pdf->SetCreator('Sinkyu Massage System');
        $pdf->SetAuthor('System');
        $pdf->SetTitle('医療保険情報履歴一覧表');

        // ヘッダー・フッターを削除
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // マージン設定
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        // 日本語フォント設定（M+ 1 Medium）
        $pdf->SetFont('mplus1medium', '', 9);

        // ページ追加
        $pdf->AddPage();

        // HTMLコンテンツを生成
        $html = view('clinic-users.insurances.insurances_pdf', [
            'user' => $user,
            'insurances' => $insurances
        ])->render();

        // HTMLをPDFに出力
        $pdf->writeHTML($html, true, false, true, false, '');

        // PDFを新規ウィンドウで表示
        return response($pdf->Output('医療保険情報履歴一覧表_' . $user->full_name . '.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * 保険情報のフィールドラベルを取得
     */
    private function getLabels()
    {
        return [
            'insurance_type_1' => '保険種別１',
            'insurance_type_2' => '保険種別２',
            'insurance_type_3' => '保険種別３',
            'insured_person_type' => '本人・家族',
            'insured_number' => '被保険者番号',
            'code_number' => '記号',
            'account_number' => '番号',
            'locality_code' => '区市町村番号',
            'recipient_code' => '受給者番号',
            'license_acquisition_date' => '資格取得年月日',
            'certification_date' => '認定年月日',
            'issue_date' => '発行（交付）年月日',
            'expenses_borne_ratio' => '一部負担金の割合',
            'expiry_date' => '有効期限',
            'is_redeemed' => '償還対象',
            'insured_name' => '被保険者氏名',
            'relationship_with_clinic_user' => '利用者との続柄',
            'is_healthcare_subsidized' => '医療助成対象',
            'public_funds_payer_code' => '公費負担者番号',
            'public_funds_recipient_code' => '公費受給者番号',
            'locality_code_family' => '区市町村番号（家族）',
            'recipient_code_family' => '受給者番号（家族）',
            'new_insurer_number' => '保険者番号',
            'new_insurer_name' => '保険者名称',
            'new_postal_code' => '郵便番号',
            'new_address' => '住所',
            'new_recipient_name' => '提出先名称'
        ];
    }
}
