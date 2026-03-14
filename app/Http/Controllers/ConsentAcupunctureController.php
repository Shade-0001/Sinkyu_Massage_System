<?php
//-- app/Http/Controllers/ConsentAcupunctureController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClinicUser;
use App\Models\ConsentAcupuncture;
use App\Models\Doctor;
use App\Models\Illness;
use App\Models\BillCategory;
use App\Models\Outcome;
use App\Models\HousecallReason;
use App\Models\TherapyContent;
use App\Models\Condition;
use App\Models\WorkScopeType;
use App\Http\Requests\ConsentMassageRequest;
use App\Services\BodypartService;
use App\Services\ConsentDataConverter;
use App\Http\Traits\SessionConfirmationTrait;
use App\Http\Traits\ConsentDataProcessingTrait;

/**
 * 同意医師履歴（はり・きゅう）管理コントローラー
 * 
 * 利用者に紐づく鍼灸の同意医師履歴のCRUD操作を担当する。
 * - 同意医師履歴の一覧・登録・編集・削除
 * - 同意医師履歴の複製
 * - 同意医師履歴の印刷
 * 
 * 注: あんま・マッサージと同じConsentMassageモデルを使用
 */
class ConsentAcupunctureController extends Controller
{
    use SessionConfirmationTrait;
    use ConsentDataProcessingTrait;

    protected $bodypartService;
    protected $consentDataConverter;

    public function __construct(
        BodypartService $bodypartService,
        ConsentDataConverter $consentDataConverter
    ) {
        $this->bodypartService = $bodypartService;
        $this->consentDataConverter = $consentDataConverter;
    }

    /**
     * 同意医師履歴一覧を表示
     */
    public function index($id)
    {
        $user = ClinicUser::findOrFail($id);
        $consentingHistories = ConsentAcupuncture::where('clinic_user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('master.clinic-users.consents-acupuncture.consents-acupuncture_index', [
            'id' => $id,
            'name' => $user->full_name,
            'consentingHistories' => $consentingHistories,
            'page_header_title' => '同意医師履歴（鍼灸）'
        ]);
    }

    /**
     * 同意医師履歴新規登録フォーム
     */
    public function create(Request $request, $id)
    {
        $user = ClinicUser::findOrFail($id);

        // マスターデータを取得
        $masterData = $this->getMasterData();

        // 確認画面から戻った場合、セッションからデータを取得
        $sessionData = $request->session()->get('consents_acupuncture_registration_data');
        $history = $sessionData ? (object)$sessionData : null;

        return view('master.clinic-users.consents-acupuncture.consents-acupuncture_registration', array_merge([
            'mode' => 'create',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の同意医師履歴新規登録',
            'id' => $id,
            'history' => $history,
        ], $masterData));
    }

    /**
     * 同意医師履歴登録確認
     */
    public function confirm(ConsentMassageRequest $request, $id)
    {
        $validated = $request->validated();

        $request->session()->put('consents_acupuncture_registration_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->consentDataConverter->convertIdsToNames($validated);

        $labels = $this->consentDataConverter->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.consents-acupuncture.registration',
            'back_id' => $id,
            'store_route' => 'clinic-users.consents-acupuncture.store',
            'page_header_title' => '同意医師履歴（はり・きゅう）登録内容確認',
            'registration_message' => '同意医師履歴（はり・きゅう）の登録を行います。',
        ]);
    }

    /**
     * 同意医師履歴保存
     */
    public function store(Request $request, $id)
    {
        $data = $request->session()->get('consents_acupuncture_registration_data');

        if (!$data) {
            return redirect()->route('clinic-users.consents-acupuncture.registration', $id)
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $data['clinic_user_id'] = $id;

        // カスタムマスターデータの処理
        $this->processCustomMasterData($data);

        // ConsentAcupunctureを保存（bodypartsリレーションは不要）
        $consentData = $this->extractConsentData($data);

        ConsentAcupuncture::create($consentData);

        $request->session()->forget('consents_acupuncture_registration_data');

        return redirect()->route('clinic-users.consents-acupuncture.index', $id)
            ->with('success', '同意医師履歴が登録されました。');
    }

    /**
     * 同意医師履歴編集フォーム
     */
    public function edit(Request $request, $id, $history_id)
    {
        $user = ClinicUser::findOrFail($id);

        // 確認画面から戻った場合はセッションデータを優先、そうでない場合はDBから取得
        $sessionData = $request->session()->get('consents_acupuncture_edit_data');
        if ($sessionData) {
            $history = (object)$sessionData;
        } else {
            $history = ConsentAcupuncture::findOrFail($history_id);
        }

        // マスターデータを取得
        $masterData = $this->getMasterData();

        return view('master.clinic-users.consents-acupuncture.consents-acupuncture_registration', array_merge([
            'mode' => 'edit',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の同意医師履歴編集',
            'id' => $id,
            'history_id' => $history_id,
            'history' => $history,
        ], $masterData));
    }

    /**
     * 同意医師履歴編集確認
     */
    public function editConfirm(ConsentMassageRequest $request, $id, $history_id)
    {
        $validated = $request->validated();

        $request->session()->put('consents_acupuncture_edit_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->consentDataConverter->convertIdsToNames($validated);

        $labels = $this->consentDataConverter->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.consents-acupuncture.edit',
            'back_id' => $id,
            'back_history_id' => $history_id,
            'store_route' => 'clinic-users.consents-acupuncture.edit.update',
            'page_header_title' => '同意医師履歴（はり・きゅう）更新内容確認',
            'registration_message' => '同意医師履歴（はり・きゅう）の更新を行います。',
        ]);
    }

    /**
     * 同意医師履歴更新
     */
    public function update(Request $request, $id, $history_id)
    {
        $data = $request->session()->get('consents_acupuncture_edit_data');

        if (!$data) {
            return redirect()->route('clinic-users.consents-acupuncture.edit', [$id, $history_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        // カスタムマスターデータの処理
        $this->processCustomMasterData($data);

        // ConsentAcupunctureを更新（bodypartsリレーションは不要）
        $consentData = $this->extractConsentData($data);

        $history = ConsentAcupuncture::findOrFail($history_id);
        $history->update($consentData);

        $request->session()->forget('consents_acupuncture_edit_data');

        return redirect()->route('clinic-users.consents-acupuncture.index', $id)
            ->with('success', '同意医師履歴が更新されました。');
    }

    /**
     * 同意医師履歴複製フォーム
     */
    public function duplicateForm(Request $request, $id, $history_id)
    {
        $user = ClinicUser::findOrFail($id);

        // 確認画面から戻った場合はセッションデータを優先、そうでない場合はDBから取得
        $sessionData = $request->session()->get('consents_acupuncture_duplicate_data');
        if ($sessionData) {
            $history = (object)$sessionData;
        } else {
            $history = ConsentAcupuncture::findOrFail($history_id);
        }

        // マスターデータを取得
        $masterData = $this->getMasterData();

        return view('master.clinic-users.consents-acupuncture.consents-acupuncture_registration', array_merge([
            'mode' => 'duplicate',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の同意医師履歴複製',
            'id' => $id,
            'history_id' => $history_id,
            'history' => $history,
        ], $masterData));
    }

    /**
     * 同意医師履歴複製確認
     */
    public function duplicateConfirm(ConsentMassageRequest $request, $id, $history_id)
    {
        $validated = $request->validated();

        $request->session()->put('consents_acupuncture_duplicate_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->consentDataConverter->convertIdsToNames($validated);

        $labels = $this->consentDataConverter->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.consents-acupuncture.duplicate',
            'back_id' => $id,
            'back_history_id' => $history_id,
            'store_route' => 'clinic-users.consents-acupuncture.duplicate.store',
            'page_header_title' => '同意医師履歴（はり・きゅう）複製内容確認',
            'registration_message' => '同意医師履歴（はり・きゅう）の複製を行います。',
        ]);
    }

    /**
     * 同意医師履歴複製保存
     */
    public function duplicateStore(Request $request, $id, $history_id)
    {
        $data = $request->session()->get('consents_acupuncture_duplicate_data');

        if (!$data) {
            return redirect()->route('clinic-users.consents-acupuncture.duplicate', [$id, $history_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $data['clinic_user_id'] = $id;

        // カスタムマスターデータの処理
        $this->processCustomMasterData($data);

        // ConsentAcupunctureを保存（bodypartsリレーションは不要）
        $consentData = $this->extractConsentData($data);

        ConsentAcupuncture::create($consentData);

        $request->session()->forget('consents_acupuncture_duplicate_data');

        return redirect()->route('clinic-users.consents-acupuncture.index', $id)
            ->with('success', '同意医師履歴が複製されました。');
    }

    /**
     * 同意医師履歴削除
     */
    public function destroy($id, $history_id)
    {
        $history = ConsentAcupuncture::findOrFail($history_id);
        $history->delete();

        return redirect()->route('clinic-users.consents-acupuncture.index', $id)
            ->with('success', '同意医師履歴が削除されました。');
    }

    /**
     * 同意医師履歴印刷
     */
    public function print($id)
    {
        $user = ClinicUser::findOrFail($id);
        $histories = ConsentAcupuncture::where('clinic_user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('master.clinic-users.consents-acupuncture.consents-acupuncture_pdf', [
            'user' => $user,
            'histories' => $histories
        ]);
    }

    /**
     * マスターデータを取得
     */
    private function getMasterData()
    {
        return [
            'doctors' => Doctor::orderBy('last_name')->orderBy('first_name')->get(),
            'diseaseNames' => Illness::orderBy('illness_name')->get(),
            'billingCategories' => BillCategory::orderBy('bill_category')->get(),
            'outcomes' => Outcome::orderBy('outcome')->get(),
            'housecallReasons' => HousecallReason::orderBy('id')->get(),
            'initialTreatments' => TherapyContent::orderBy('therapy_content')->get(),
            'diseaseProgresses' => Condition::orderBy('condition_name')->get(),
            'workRelatedCategories' => WorkScopeType::orderBy('work_scope_type')->get(),
        ];
    }
}
