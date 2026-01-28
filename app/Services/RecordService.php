<?php

namespace App\Services;

use App\Models\Record;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordService
{
  /**
   * 複数日付の実績データを一括作成
   */
  public function createRecordsForDates(array $validated, array $housecallDistances, array $duplicateOptions = []): void
  {
    DB::transaction(function () use ($validated, $housecallDistances, $duplicateOptions) {
      // 自費施術かどうかを判定
      $therapyContentId = $validated['therapy_content_id'];
      $selfFeeId = null;

      if (str_starts_with($therapyContentId, 'self_')) {
        $selfFeeId = (int) str_replace('self_', '', $therapyContentId);
        $therapyContentId = null;
      }

      // 選択された日付ごとにレコードを作成
      foreach ($housecallDistances as $date => $distance) {
        $recordData = $this->prepareRecordData($validated, $date, $distance, $therapyContentId, $selfFeeId, count($housecallDistances));

        // メインレコード作成
        $record = Record::create($recordData);

        // 身体部位の保存
        $this->saveBodyparts($record->id, $validated);

        // 複製レコードの作成（あんま･マッサージの場合）
        if ($validated['therapy_type'] == 2) {
          $this->createDuplicateRecords($validated, $date, $distance, count($housecallDistances), $duplicateOptions);
        }
      }
    });
  }

  /**
   * 実績データを更新（同一グループのレコードを削除して再作成）
   */
  public function updateRecordsForDates(int $recordId, array $validated, array $housecallDistances, array $duplicateOptions = []): void
  {
    DB::transaction(function () use ($recordId, $validated, $housecallDistances, $duplicateOptions) {
      // 元のレコードを取得
      $originalRecord = DB::table('records')->where('id', $recordId)->first();

      if (!$originalRecord) {
        throw new \Exception('実績データが見つかりません。');
      }

      // 同一グループの全レコードを取得
      $deletedRecords = DB::table('records')
        ->where('clinic_user_id', $originalRecord->clinic_user_id)
        ->where('therapy_content_id', $originalRecord->therapy_content_id)
        ->where('therapist_id', $originalRecord->therapist_id)
        ->where('start_time', $originalRecord->start_time)
        ->where('end_time', $originalRecord->end_time)
        ->get();

      // 削除対象のレコードIDを取得
      $deletedRecordIds = $deletedRecords->pluck('id')->toArray();

      // 元の登録日時を保持
      $originalCreatedAt = $deletedRecords->min('created_at');

      // 関連データを削除
      DB::table('bodyparts-records')
        ->whereIn('records_id', $deletedRecordIds)
        ->delete();

      Record::whereIn('id', $deletedRecordIds)->delete();

      // 新しいレコードを作成
      foreach ($housecallDistances as $date => $distance) {
        $recordData = $this->prepareRecordData($validated, $date, $distance, $validated['therapy_content_id'], null, count($housecallDistances));

        // メインレコード作成（元の登録日時を保持）
        $record = new Record($recordData);
        $record->created_at = $originalCreatedAt;
        $record->save();

        // 身体部位の保存
        $this->saveBodyparts($record->id, $validated);

        // 複製レコードの作成（あんま･マッサージの場合）
        if ($validated['therapy_type'] == 2) {
          $this->createDuplicateRecords($validated, $date, $distance, count($housecallDistances), $duplicateOptions, $originalCreatedAt);
        }
      }
    });
  }

  /**
   * レコードデータの準備
   */
  private function prepareRecordData(array $validated, string $date, $distance, $therapyContentId, $selfFeeId, int $therapyDays): array
  {
    return [
      'clinic_user_id' => $validated['clinic_user_id'],
      'date' => $date,
      'start_time' => $validated['start_time'],
      'end_time' => $validated['end_time'],
      'therapy_type' => $validated['therapy_type'],
      'therapy_category' => $validated['therapy_category'],
      'insurance_category' => $validated['insurance_category'] ?? null,
      'housecall_distance' => $validated['therapy_category'] == 2 ? $distance : null,
      'therapy_days' => $therapyDays,
      'consent_expiry' => $validated['consent_expiry'] ?? null,
      'therapy_content_id' => $therapyContentId,
      'self_fee_id' => $selfFeeId,
      'bill_category_id' => $validated['bill_category_id'],
      'therapist_id' => $validated['therapist_id'],
      'abstract' => $validated['abstract'] ?? null,
    ];
  }

  /**
   * 身体部位データの保存
   */
  private function saveBodyparts(int $recordId, array $validated): void
  {
    if ($validated['therapy_type'] == 2 && isset($validated['bodyparts'])) {
      foreach ($validated['bodyparts'] as $bodypartId) {
        DB::table('bodyparts-records')->insert([
          'records_id' => $recordId,
          'therapy_type_bodyparts_id' => $bodypartId,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }
    }
  }

  /**
   * 複製レコードの作成（あんま･マッサージ用）
   */
  private function createDuplicateRecords(array $validated, string $date, $distance, int $therapyDays, array $duplicateOptions, $originalCreatedAt = null): void
  {
    $duplicateContents = $this->getDuplicateContents($duplicateOptions);

    foreach ($duplicateContents as $contentId) {
      $recordData = [
        'clinic_user_id' => $validated['clinic_user_id'],
        'date' => $date,
        'start_time' => $validated['start_time'],
        'end_time' => $validated['end_time'],
        'therapy_type' => $validated['therapy_type'],
        'therapy_category' => $validated['therapy_category'],
        'insurance_category' => $validated['insurance_category'] ?? null,
        'housecall_distance' => $validated['therapy_category'] == 2 ? $distance : null,
        'therapy_days' => $therapyDays,
        'consent_expiry' => $validated['consent_expiry'] ?? null,
        'therapy_content_id' => $contentId,
        'bill_category_id' => $validated['bill_category_id'],
        'therapist_id' => $validated['therapist_id'],
        'abstract' => $validated['abstract'] ?? null,
      ];

      if ($originalCreatedAt) {
        $duplicateRecord = new Record($recordData);
        $duplicateRecord->created_at = $originalCreatedAt;
        $duplicateRecord->save();
      } else {
        $duplicateRecord = Record::create($recordData);
      }

      // 複製したレコードにも身体部位を保存
      $this->saveBodyparts($duplicateRecord->id, $validated);
    }
  }

  /**
   * 複製対象の施術内容IDを取得
   */
  private function getDuplicateContents(array $duplicateOptions): array
  {
    $contents = [];

    if (!empty($duplicateOptions['duplicate_massage'])) {
      $contents[] = 7; // マッサージ
    }
    if (!empty($duplicateOptions['duplicate_warm_compress'])) {
      $contents[] = 9; // 温罨法
    }
    if (!empty($duplicateOptions['duplicate_warm_electric'])) {
      $contents[] = 10; // 温罨法・電気光線器具
    }
    if (!empty($duplicateOptions['duplicate_manual_correction'])) {
      $contents[] = 8; // 変形徒手矯正術
    }

    return $contents;
  }

  /**
   * 利用者の保険情報を取得
   */
  public function getInsurancesForUser(int $userId): array
  {
    $insurances = DB::table('insurances')
      ->leftJoin('insurers', 'insurances.insurers_id', '=', 'insurers.id')
      ->where('insurances.clinic_user_id', $userId)
      ->select(
        'insurances.*',
        'insurers.insurer_number'
      )
      ->orderBy('insurances.expiry_date', 'desc')
      ->get()
      ->toArray();

    return $insurances;
  }

  /**
   * 利用者の同意書情報を取得
   */
  public function getConsentsForUser(int $userId, int $therapyType)
  {
    $tableName = $therapyType == 1 ? 'consents_acupuncture' : 'consents_massage';

    return DB::table($tableName)
      ->where('clinic_user_id', $userId)
      ->orderBy('consenting_end_date', 'desc')
      ->first();
  }
}
