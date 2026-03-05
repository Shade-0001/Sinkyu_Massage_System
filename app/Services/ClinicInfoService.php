<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ClinicInfoService
{
  private $clinicInfo = null;
  private $allClinicInfos = null;

  /**
   * 診療所情報を取得（キャッシュ付き・最新レコード）
   */
  public function getClinicInfo()
  {
    if ($this->clinicInfo === null) {
      $this->clinicInfo = DB::table('clinic_info')->orderByDesc('id')->first();
    }

    return $this->clinicInfo;
  }

  /**
   * 全 clinic_info を created_at 昇順で取得（キャッシュ付き）
   */
  private function getAllClinicInfos()
  {
    if ($this->allClinicInfos === null) {
      $this->allClinicInfos = DB::table('clinic_info')->orderBy('created_at')->get();
    }

    return $this->allClinicInfos;
  }

  /**
   * 指定日付時点で有効な clinic_info を返す
   * （日付以前で最新のレコード。该当なければ最古のレコード）
   *
   * RecordSeeder の getClinicInfoForDate と同一ロジック。
   */
  public function getClinicInfoForDate(string $date): object
  {
    $all = $this->getAllClinicInfos();
    $dateTs = strtotime($date);
    $matched = null;

    foreach ($all as $info) {
      if (strtotime($info->created_at) <= $dateTs) {
        $matched = $info;
      }
    }

    return $matched ?? $all->first();
  }

  /**
   * 定休日情報を配列形式で取得（最新 clinic_info 基準）
   */
  public function getClosedDays(): array
  {
    return $this->buildClosedDaysArray($this->getClinicInfo());
  }

  /**
   * 指定日付時点で有効な clinic_info を基準に定休日情報を取得
   */
  public function getClosedDaysForDate(string $date): array
  {
    return $this->buildClosedDaysArray($this->getClinicInfoForDate($date));
  }

  /**
   * clinic_info オブジェクトから定休日配列を生成
   */
  private function buildClosedDaysArray($clinicInfo): array
  {
    return [
      'monday' => $clinicInfo->closed_day_monday ?? 0,
      'tuesday' => $clinicInfo->closed_day_tuesday ?? 0,
      'wednesday' => $clinicInfo->closed_day_wednesday ?? 0,
      'thursday' => $clinicInfo->closed_day_thursday ?? 0,
      'friday' => $clinicInfo->closed_day_friday ?? 0,
      'saturday' => $clinicInfo->closed_day_saturday ?? 0,
      'sunday' => $clinicInfo->closed_day_sunday ?? 0,
    ];
  }

  /**
   * 営業時間情報を取得
   */
  public function getBusinessHours(): array
  {
    $clinicInfo = $this->getClinicInfo();

    return [
      'open_time' => $clinicInfo->open_time ?? null,
      'close_time' => $clinicInfo->close_time ?? null,
    ];
  }

  /**
   * 診療所名を取得
   */
  public function getClinicName(): ?string
  {
    $clinicInfo = $this->getClinicInfo();
    return $clinicInfo->name ?? null;
  }

  /**
   * 診療所の電話番号を取得
   */
  public function getClinicPhone(): ?string
  {
    $clinicInfo = $this->getClinicInfo();
    return $clinicInfo->phone ?? null;
  }

  /**
   * 診療所の住所を取得
   */
  public function getClinicAddress(): array
  {
    $clinicInfo = $this->getClinicInfo();

    return [
      'postal_code' => $clinicInfo->postal_code ?? null,
      'prefecture' => $clinicInfo->prefecture ?? null,
      'city' => $clinicInfo->city ?? null,
      'address' => $clinicInfo->address ?? null,
      'building' => $clinicInfo->building ?? null,
    ];
  }
}
