<?php
//-- app/Http/Controllers/PlanController.php --//

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClinicUser;
use App\Models\Plan;
use App\Models\AssistanceLevel;
use App\Http\Traits\SessionConfirmationTrait;

/**
 * 計画情報管理コントローラー
 * 
 * 利用者に紐づくリハビリテーション計画情報のCRUD操作を担当する。
 * - 計画情報の一覧・登録・編集・削除
 * - 計画情報の複製
 */
class PlanController extends Controller
{
    use SessionConfirmationTrait;

    /**
     * 計画情報一覧
     */
    public function index($id)
    {
        $user = ClinicUser::findOrFail($id);
        $planInfos = Plan::where('clinic_user_id', $id)
            ->orderBy('assessment_date', 'desc')
            ->get();

        return view('master.clinic-users.plans.plans_index', [
            'id' => $id,
            'name' => $user->full_name,
            'planInfos' => $planInfos,
            'page_header_title' => '計画情報'
        ]);
    }

    /**
     * 計画情報新規登録フォーム
     */
    public function create(Request $request, $id)
    {
        $user = ClinicUser::findOrFail($id);

        // 介助レベルを取得
        $assistanceLevels = AssistanceLevel::orderBy('id')->get();

        // 各ADL項目で使用する介助レベルIDを定義
        $adlLevelMapping = $this->getAdlLevelMapping();

        // 確認画面から戻った場合、セッションからデータを取得
        $sessionData = $request->session()->get('plan_infos_registration_data');
        $planInfo = $sessionData ? (object)$sessionData : null;

        return view('master.clinic-users.plans.plans_registration', [
            'mode' => 'create',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の計画情報新規登録',
            'id' => $id,
            'planInfo' => $planInfo,
            'assistanceLevels' => $assistanceLevels,
            'adlLevelMapping' => $adlLevelMapping
        ]);
    }

    /**
     * 計画情報登録確認
     */
    public function confirm(Request $request, $id)
    {
        $validated = $request->validate($this->getValidationRules());

        $request->session()->put('plan_infos_registration_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->convertIdsToNames($validated);

        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.plans.create',
            'back_id' => $id,
            'store_route' => 'clinic-users.plans.store',
            'page_header_title' => '計画情報登録内容確認',
            'registration_message' => '計画情報の登録を行います。',
        ]);
    }

    /**
     * 計画情報登録処理
     */
    public function store(Request $request, $id)
    {
        $data = $request->session()->get('plan_infos_registration_data');

        if (!$data) {
            return redirect()->route('clinic-users.plans.create', $id)
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $data['clinic_user_id'] = $id;

        $planInfo = new Plan();
        $planInfo->fill($data);
        $planInfo->save();

        $request->session()->forget('plan_infos_registration_data');

        return redirect()->route('clinic-users.plans.index', $id)
            ->with('success', '計画情報を登録しました。');
    }

    /**
     * 計画情報編集フォーム
     */
    public function edit(Request $request, $id, $plan_id)
    {
        $user = ClinicUser::findOrFail($id);
        $planInfo = Plan::findOrFail($plan_id);

        // 介助レベルを取得
        $assistanceLevels = AssistanceLevel::orderBy('id')->get();

        // 各ADL項目で使用する介助レベルIDを定義
        $adlLevelMapping = $this->getAdlLevelMapping();

        // 確認画面から戻った場合、セッションからデータを取得
        $sessionData = $request->session()->get('plan_infos_edit_data');
        if ($sessionData) {
            $planInfo = (object)$sessionData;
        }

        return view('master.clinic-users.plans.plans_registration', [
            'mode' => 'edit',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の計画情報編集',
            'id' => $id,
            'plan_id' => $plan_id,
            'planInfo' => $planInfo,
            'assistanceLevels' => $assistanceLevels,
            'adlLevelMapping' => $adlLevelMapping
        ]);
    }

    /**
     * 計画情報編集確認
     */
    public function editConfirm(Request $request, $id, $plan_id)
    {
        $validated = $request->validate($this->getValidationRules());

        $request->session()->put('plan_infos_edit_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->convertIdsToNames($validated);

        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.plans.edit',
            'back_id' => $id,
            'back_plan_id' => $plan_id,
            'store_route' => 'clinic-users.plans.edit.update',
            'page_header_title' => '計画情報更新内容確認',
            'registration_message' => '計画情報の更新を行います。',
        ]);
    }

    /**
     * 計画情報更新処理
     */
    public function update(Request $request, $id, $plan_id)
    {
        $data = $request->session()->get('plan_infos_edit_data');

        if (!$data) {
            return redirect()->route('clinic-users.plans.edit', [$id, $plan_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $planInfo = Plan::findOrFail($plan_id);
        $planInfo->fill($data);
        $planInfo->save();

        $request->session()->forget('plan_infos_edit_data');

        return redirect()->route('clinic-users.plans.index', $id)
            ->with('success', '計画情報を更新しました。');
    }

    /**
     * 計画情報複製フォーム
     */
    public function duplicateForm(Request $request, $id, $plan_id)
    {
        $user = ClinicUser::findOrFail($id);
        $originalPlanInfo = Plan::findOrFail($plan_id);

        // 介助レベルを取得
        $assistanceLevels = AssistanceLevel::orderBy('id')->get();

        // 各ADL項目で使用する介助レベルIDを定義
        $adlLevelMapping = $this->getAdlLevelMapping();

        // 確認画面から戻った場合、セッションからデータを取得
        $sessionData = $request->session()->get('plan_infos_duplicate_data');
        $planInfo = $sessionData ? (object)$sessionData : $originalPlanInfo;

        return view('master.clinic-users.plans.plans_registration', [
            'mode' => 'duplicate',
            'page_header_title' => $user->last_name . "\u{2000}" . $user->first_name . "\u{2000}" . '様の計画情報複製',
            'id' => $id,
            'plan_id' => $plan_id,
            'planInfo' => $planInfo,
            'assistanceLevels' => $assistanceLevels,
            'adlLevelMapping' => $adlLevelMapping
        ]);
    }

    /**
     * 計画情報複製確認
     */
    public function duplicateConfirm(Request $request, $id, $plan_id)
    {
        $validated = $request->validate($this->getValidationRules());

        $request->session()->put('plan_infos_duplicate_data', $validated);

        // マスターデータIDを名称に変換
        $displayData = $this->convertIdsToNames($validated);

        $labels = $this->getLabels();

        return view('registration-review', [
            'data' => $displayData,
            'labels' => $labels,
            'back_route' => 'clinic-users.plans.duplicate',
            'back_id' => $id,
            'back_plan_id' => $plan_id,
            'store_route' => 'clinic-users.plans.duplicate.store',
            'page_header_title' => '計画情報複製内容確認',
            'registration_message' => '計画情報の複製登録を行います。',
        ]);
    }

    /**
     * 計画情報複製処理
     */
    public function duplicateStore(Request $request, $id, $plan_id)
    {
        $data = $request->session()->get('plan_infos_duplicate_data');

        if (!$data) {
            return redirect()->route('clinic-users.plans.duplicate', [$id, $plan_id])
                ->with('error', 'セッションが切れました。もう一度入力してください。');
        }

        $data['clinic_user_id'] = $id;

        $planInfo = new Plan();
        $planInfo->fill($data);
        $planInfo->save();

        $request->session()->forget('plan_infos_duplicate_data');

        return redirect()->route('clinic-users.plans.index', $id)
            ->with('success', '計画情報を複製登録しました。');
    }

    /**
     * 計画情報削除
     */
    public function destroy($id, $plan_id)
    {
        $planInfo = Plan::findOrFail($plan_id);
        $planInfo->delete();

        return redirect()->route('clinic-users.plans.index', $id)
            ->with('success', '計画情報を削除しました。');
    }

    /**
     * 計画情報のバリデーションルール
     */
    private function getValidationRules()
    {
        return [
            'assessment_date' => 'required|date',
            'assessor' => 'nullable|string|max:255',
            'audience' => 'nullable|string|max:255',
            'eating_assistance_level_id' => 'nullable|integer',
            'eating_assistance_note' => 'nullable|string',
            'moving_assistance_level_id' => 'nullable|integer',
            'moving_assistance_note' => 'nullable|string',
            'personal_grooming_assistance_level_id' => 'nullable|integer',
            'personal_grooming_assistance_note' => 'nullable|string',
            'using_toilet_assistance_level_id' => 'nullable|integer',
            'using_toilet_assistance_note' => 'nullable|string',
            'bathing_assistance_level_id' => 'nullable|integer',
            'bathing_assistance_note' => 'nullable|string',
            'walking_assistance_level_id' => 'nullable|integer',
            'walking_assistance_note' => 'nullable|string',
            'using_stairs_assistance_level_id' => 'nullable|integer',
            'using_stairs_assistance_note' => 'nullable|string',
            'changing_clothes_assistance_level_id' => 'nullable|integer',
            'changing_clothes_assistance_note' => 'nullable|string',
            'defecation_assistance_level_id' => 'nullable|integer',
            'defecation_assistance_note' => 'nullable|string',
            'urination_assistance_level_id' => 'nullable|integer',
            'urination_assistance_note' => 'nullable|string',
            'communication_note' => 'nullable|string',
            'wish_of_user_and_familiy' => 'nullable|string',
            'care_purpose' => 'nullable|string',
            'rehabilitation_program' => 'nullable|string',
            'home_rehabilitation' => 'nullable|string',
            'change_since_previous_planning' => 'nullable|string',
            'note' => 'nullable|string',
            'user_and_family_consent_date' => 'nullable|date'
        ];
    }

    /**
     * 計画情報のラベル定義
     */
    private function getLabels()
    {
        return [
            'assessment_date' => '評価日',
            'assessor' => '評価者',
            'audience' => '聴衆者',
            'eating_assistance_level_id' => '食事介助レベル',
            'eating_assistance_note' => '食事介助備考',
            'moving_assistance_level_id' => '起居移動レベル',
            'moving_assistance_note' => '起居移動備考',
            'personal_grooming_assistance_level_id' => '整容レベル',
            'personal_grooming_assistance_note' => '整容備考',
            'using_toilet_assistance_level_id' => 'トイレレベル',
            'using_toilet_assistance_note' => 'トイレ備考',
            'bathing_assistance_level_id' => '入浴レベル',
            'bathing_assistance_note' => '入浴備考',
            'walking_assistance_level_id' => '平地歩行レベル',
            'walking_assistance_note' => '平地歩行備考',
            'using_stairs_assistance_level_id' => '階段昇降レベル',
            'using_stairs_assistance_note' => '階段昇降備考',
            'changing_clothes_assistance_level_id' => '更衣レベル',
            'changing_clothes_assistance_note' => '更衣備考',
            'defecation_assistance_level_id' => '排便レベル',
            'defecation_assistance_note' => '排便備考',
            'urination_assistance_level_id' => '排尿レベル',
            'urination_assistance_note' => '排尿備考',
            'communication_note' => 'コミュニケーション',
            'wish_of_user_and_familiy' => 'ご本人・ご家族の希望',
            'care_purpose' => '治療目的',
            'rehabilitation_program' => 'リハビリテーションプログラム',
            'home_rehabilitation' => '自宅でのリハビリテーション',
            'change_since_previous_planning' => '前回計画書作成時からの改善・変化',
            'note' => '障害・注意事項',
            'user_and_family_consent_date' => '本人・家族同意日'
        ];
    }

    /**
     * 計画情報のIDを名称に変換
     */
    private function convertIdsToNames($data)
    {
        $result = $data;

        // 介助レベルIDを名称に変換
        $levelFields = [
            'eating_assistance_level_id',
            'moving_assistance_level_id',
            'personal_grooming_assistance_level_id',
            'using_toilet_assistance_level_id',
            'bathing_assistance_level_id',
            'walking_assistance_level_id',
            'using_stairs_assistance_level_id',
            'changing_clothes_assistance_level_id',
            'defecation_assistance_level_id',
            'urination_assistance_level_id'
        ];

        foreach ($levelFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $level = AssistanceLevel::find($data[$field]);
                $result[$field] = $level ? $level->assistance_level : '';
            }
        }

        return $result;
    }

    /**
     * 各ADL項目で使用可能な介助レベルIDのマッピング
     */
    private function getAdlLevelMapping()
    {
        return [
            'eating' => [2, 3, 8],  // 全介助、部分介助、自立
            'moving' => [2, 4, 6, 8],  // 全介助、中等度介助、要監視又は軽監視、自立
            'personal_grooming' => [1, 8],  // 要介助、自立
            'using_toilet' => [2, 3, 8],  // 全介助、部分介助、自立
            'bathing' => [1, 8],  // 要介助、自立
            'walking' => [5, 7, 6, 8],  // 中等度以上の介助又は不能、車椅子使用、監視又は軽介助、自立
            'using_stairs' => [2, 3, 8],  // 全介助、部分介助、自立
            'changing_clothes' => [5, 3, 8],  // 中等度以上の介助又は不能、軽介助(部分介助)、自立
            'defecation' => [2, 1, 8],  // 全介助、要介助、自立
            'urination' => [2, 1, 9]  // 不能(全介助)、要介助、昼夜問わず自立
        ];
    }
}
