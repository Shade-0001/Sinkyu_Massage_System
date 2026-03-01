<?php
//-- app/Services/BodypartService.php --//

namespace App\Services;

use App\Models\Bodypart;
use Illuminate\Support\Facades\DB;

/**
 * 身体部位サービス
 * 
 * 同意医師履歴で使用する身体部位（bodyparts）の変換・保存処理を提供する。
 * - 日本語名称 ⇔ bodypart値の変換
 * - Bodypartsリレーションの保存・削除
 */
class BodypartService
{
    /**
     * 日本語名称 → bodypart値のマッピング
     */
    private const MAPPING = [
        '左上肢' => 'upper_limb_l',
        '右上肢' => 'upper_limb_r',
        '左下肢' => 'lower_limb_l',
        '右下肢' => 'lower_limb_r',
        '右肩' => 'shoulder_r',
        '右肘' => 'elbow_r',
        '右手首' => 'wrist_r',
        '右股関節' => 'coxa_r',
        '右膝' => 'knee_r',
        '右足首' => 'ankle_r',
        '左肩' => 'shoulder_l',
        '左肘' => 'elbow_l',
        '左手首' => 'wrist_l',
        '左股関節' => 'coxa_l',
        '左膝' => 'knee_l',
        '左足首' => 'ankle_l'
    ];

    /**
     * Bodypart値を日本語名称に変換して取得
     * 
     * データベースから取得したbodypart IDを日本語名称の配列に変換する。
     * フォーム表示時に使用。
     *
     * @param int $historyId 同意医師履歴ID
     * @param string $columnName カラム名（symtom_1_bodyparts_id等）
     * @return array 日本語名称の配列
     */
    public function getBodypartsValues($historyId, $columnName)
    {
        $reverseMapping = array_flip(self::MAPPING);

        $bodypartIds = DB::table('bodyparts-consents_massage')
            ->where('consents_massage_id', $historyId)
            ->whereNotNull($columnName)
            ->pluck($columnName);

        $values = [];
        foreach ($bodypartIds as $bodypartId) {
            $bodypart = Bodypart::find($bodypartId);
            if ($bodypart && isset($reverseMapping[$bodypart->bodypart])) {
                $values[] = $reverseMapping[$bodypart->bodypart];
            }
        }

        return $values;
    }

    /**
     * Bodypartsリレーションを保存
     * 
     * チェックボックスで選択された身体部位をリレーションテーブルに保存する。
     *
     * @param object $consentMassage ConsentMassageモデルインスタンス
     * @param array $symptom1 症状1の身体部位
     * @param array $symptom2 症状2の身体部位
     * @param array $treatmentType1 施術の種類1の身体部位
     * @param array $treatmentType2 施術の種類2の身体部位
     * @return void
     */
    public function saveBodypartsRelations(
        $consentMassage,
        array $symptom1,
        array $symptom2,
        array $treatmentType1,
        array $treatmentType2
    ) {
        $this->saveBodypartsByColumn(
            $consentMassage->id,
            'symtom_1_bodyparts_id',
            $symptom1
        );
        $this->saveBodypartsByColumn(
            $consentMassage->id,
            'symtom_2_bodyparts_id',
            $symptom2
        );
        $this->saveBodypartsByColumn(
            $consentMassage->id,
            'therapy_type_1_bodyparts_id',
            $treatmentType1
        );
        $this->saveBodypartsByColumn(
            $consentMassage->id,
            'therapy_type_2_bodyparts_id',
            $treatmentType2
        );
    }

    /**
     * 特定カラムにBodypartsを保存
     * 
     * @param int $consentId 同意医師履歴ID
     * @param string $columnName カラム名
     * @param array $values 日本語名称の配列
     * @return void
     */
    private function saveBodypartsByColumn($consentId, $columnName, array $values)
    {
        foreach ($values as $value) {
            if (isset(self::MAPPING[$value])) {
                $bodypart = Bodypart::where('bodypart', self::MAPPING[$value])->first();
                if ($bodypart) {
                    DB::table('bodyparts-consents_massage')->insert([
                        'consents_massage_id' => $consentId,
                        $columnName => $bodypart->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    /**
     * 既存のBodypartsリレーションを削除
     * 
     * 編集時に既存のリレーションを削除してから再保存する際に使用。
     *
     * @param int $historyId 同意医師履歴ID
     * @return void
     */
    public function deleteBodypartsRelations($historyId)
    {
        DB::table('bodyparts-consents_massage')
            ->where('consents_massage_id', $historyId)
            ->delete();
    }
}
