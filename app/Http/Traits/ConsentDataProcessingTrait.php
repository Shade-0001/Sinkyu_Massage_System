<?php

namespace App\Http\Traits;

use App\Models\Illness;
use App\Models\Condition;
use App\Services\BodypartService;

/**
 * 同意医師履歴データ処理トレイト
 * 
 * あんま・マッサージと鍼灸の同意医師履歴で共通するデータ処理を提供する。
 * - カスタムマスターデータの登録
 * - チェックボックスデータの処理
 * - Bodypartsデータの準備
 */
trait ConsentDataProcessingTrait
{
    /**
     * カスタム傷病名・発病負傷経過の処理
     *
     * フォームで新規に入力された傷病名や発病負傷経過を
     * マスターテーブルに登録し、IDを設定する。
     *
     * @param array &$data フォームデータ（参照渡し）
     * @return void
     */
    protected function processCustomMasterData(array &$data)
    {
        // カスタム傷病名の処理（マッサージ用）
        if (!empty($data['disease_name_custom'])) {
            $illness = Illness::create([
                'illness_name' => $data['disease_name_custom']
            ]);
            $data['injury_and_illness_name_id'] = $illness->id;
            unset($data['disease_name_custom']);
        }

        // カスタム傷病名の処理（鍼灸用）
        if (!empty($data['illness_name_acupuncture_addendum'])) {
            $illness = Illness::create([
                'illness_name' => $data['illness_name_acupuncture_addendum']
            ]);
            $data['illness_name_acupuncture_id'] = $illness->id;
            unset($data['illness_name_acupuncture_addendum']);
        }

        // カスタム発病負傷経過の処理（マッサージ用）
        if (!empty($data['disease_progress_custom'])) {
            $condition = Condition::create([
                'condition_name' => $data['disease_progress_custom']
            ]);
            $data['condition_id'] = $condition->id;
            unset($data['disease_progress_custom']);
        }

        // カスタム発病負傷経過の処理（鍼灸用）
        if (!empty($data['condition_custom'])) {
            $condition = Condition::create([
                'condition_name' => $data['condition_custom']
            ]);
            $data['condition'] = $condition->id;
            unset($data['condition_custom']);
        }
    }

    /**
     * チェックボックスデータの抽出とフラグ設定
     * 
     * フォームのチェックボックスデータを抽出し、
     * データベース保存用のフラグを設定する。
     *
     * @param array &$data フォームデータ（参照渡し）
     * @return array チェックボックスの配列
     */
    protected function processCheckboxData(array &$data)
    {
        $symptom1 = $data['symptom1'] ?? [];
        $symptom2 = $data['symptom2'] ?? [];
        $treatmentType1 = $data['treatment_type1'] ?? [];
        $treatmentType2 = $data['treatment_type2'] ?? [];

        // 症状・施術タイプのフラグを設定
        $data['is_symptom_1'] = in_array('筋麻痺', $symptom1);
        $data['is_symptom_2'] = !empty($data['symptom2_joint_disorder']);
        $data['is_symptom_3'] = !empty($data['symptom3_other']);
        $data['is_therapy_type_1'] = in_array('マッサージ', $treatmentType1);
        $data['is_therapy_type_2'] = !empty($data['treatment_type2_corrective_hand']);

        // フォームのname属性をモデルのフィールドにマッピング
        if (isset($data['symptom2_other_text'])) {
            $data['symtom_2_addendum'] = $data['symptom2_other_text'];
        }
        if (isset($data['symptom3'])) {
            $data['symtom_3_addendum'] = $data['symptom3'];
        }

        return compact('symptom1', 'symptom2', 'treatmentType1', 'treatmentType2');
    }

    /**
     * チェックボックス以外のデータ抽出
     * 
     * ConsentMassageモデルに保存するデータから
     * チェックボックス関連フィールドを除外する。
     *
     * @param array $data フォームデータ
     * @return array 保存用データ
     */
    protected function extractConsentData(array $data)
    {
        $exclude = [
            'symptom1',
            'symptom2',
            'treatment_type1',
            'treatment_type2',
            'symptom2_joint_disorder',
            'symptom2_other',
            'symptom2_other_text',
            'symptom3_other',
            'symptom3',
            'treatment_type2_corrective_hand'
        ];

        return array_diff_key($data, array_flip($exclude));
    }

    /**
     * Bodypartsデータの準備（編集時・複製時）
     * 
     * データベースから取得した同意医師履歴に対して、
     * フォーム表示用のBodypartsデータを準備する。
     *
     * @param object $history ConsentMassageモデルインスタンス
     * @param int $historyId 履歴ID
     * @return object 準備済みの履歴データ
     */
    protected function prepareBodypartsForEdit($history, $historyId)
    {
        $bodypartService = app(BodypartService::class);

        // Bodypartsデータをチェックボックス用に変換
        $symptom1Parts = $bodypartService->getBodypartsValues(
            $historyId,
            'symtom_1_bodyparts_id'
        );
        $symptom2Parts = $bodypartService->getBodypartsValues(
            $historyId,
            'symtom_2_bodyparts_id'
        );
        $treatmentType1Parts = $bodypartService->getBodypartsValues(
            $historyId,
            'therapy_type_1_bodyparts_id'
        );
        $treatmentType2Parts = $bodypartService->getBodypartsValues(
            $historyId,
            'therapy_type_2_bodyparts_id'
        );

        // 症状・施術タイプのフラグに基づいてチェックボックス値を設定
        if ($history->is_symptom_1) {
            $symptom1Parts[] = '筋麻痺';
        }
        if ($history->is_therapy_type_1) {
            $treatmentType1Parts[] = 'マッサージ';
        }

        $history->symptom1 = $symptom1Parts;
        $history->symptom2 = $symptom2Parts;
        $history->treatment_type1 = $treatmentType1Parts;
        $history->treatment_type2 = $treatmentType2Parts;

        // 単独チェックボックスとテキストフィールドをマッピング
        $history->symptom2_joint_disorder = $history->is_symptom_2 ?? false;
        $history->symptom2_other = !empty($history->symtom_2_addendum);
        $history->symptom2_other_text = $history->symtom_2_addendum ?? '';
        $history->symptom3_other = $history->is_symptom_3 ?? false;
        $history->symptom3 = $history->symtom_3_addendum ?? '';
        $history->treatment_type2_corrective_hand = $history->is_therapy_type_2 ?? false;

        return $history;
    }
}
