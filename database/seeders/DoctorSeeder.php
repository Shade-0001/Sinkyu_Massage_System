<?php
// database/seeders/DoctorSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('doctors')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('doctors')->insert([
      [
        'last_name' => '青木', 'first_name' => '浩二',
        'last_name_kana' => 'アオキ', 'first_name_kana' => 'コウジ',
        'medical_institutions_id' => null,
        'postal_code' => '160-0023', 'address_1' => '東京都', 'address_2' => '新宿区西新宿1-2-3', 'address_3' => '青木内科クリニック',
        'phone' => '03-1111-2222', 'cell_phone' => null, 'fax' => '03-1111-2223', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '松本', 'first_name' => '恵子',
        'last_name_kana' => 'マツモト', 'first_name_kana' => 'ケイコ',
        'medical_institutions_id' => null,
        'postal_code' => '530-0002', 'address_1' => '大阪府', 'address_2' => '大阪市北区曽根崎2-3-4', 'address_3' => '松本整形外科',
        'phone' => '06-2222-3333', 'cell_phone' => null, 'fax' => '06-2222-3334', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '高橋', 'first_name' => '隆',
        'last_name_kana' => 'タカハシ', 'first_name_kana' => 'タカシ',
        'medical_institutions_id' => null,
        'postal_code' => '460-0003', 'address_1' => '愛知県', 'address_2' => '名古屋市中区錦3-4-5', 'address_3' => '高橋神経内科',
        'phone' => '052-333-4444', 'cell_phone' => null, 'fax' => '052-333-4445', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '加藤', 'first_name' => '信夫',
        'last_name_kana' => 'カトウ', 'first_name_kana' => 'ノブオ',
        'medical_institutions_id' => null,
        'postal_code' => '220-0004', 'address_1' => '神奈川県', 'address_2' => '横浜市西区北幸1-5-6', 'address_3' => '加藤リハビリクリニック',
        'phone' => '045-444-5555', 'cell_phone' => null, 'fax' => '045-444-5556', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '木村', 'first_name' => '良子',
        'last_name_kana' => 'キムラ', 'first_name_kana' => 'リョウコ',
        'medical_institutions_id' => null,
        'postal_code' => '812-0012', 'address_1' => '福岡県', 'address_2' => '福岡市博多区博多駅東2-6-7', 'address_3' => '木村内科',
        'phone' => '092-555-6666', 'cell_phone' => null, 'fax' => '092-555-6667', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '林', 'first_name' => '正義',
        'last_name_kana' => 'ハヤシ', 'first_name_kana' => 'マサヨシ',
        'medical_institutions_id' => null,
        'postal_code' => '060-0002', 'address_1' => '北海道', 'address_2' => '札幌市中央区北2条西3-7-8', 'address_3' => '林整形外科クリニック',
        'phone' => '011-666-7777', 'cell_phone' => null, 'fax' => '011-666-7778', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '清水', 'first_name' => '博',
        'last_name_kana' => 'シミズ', 'first_name_kana' => 'ヒロシ',
        'medical_institutions_id' => null,
        'postal_code' => '980-0011', 'address_1' => '宮城県', 'address_2' => '仙台市青葉区上杉1-2-9', 'address_3' => '清水内科神経科クリニック',
        'phone' => '022-777-8888', 'cell_phone' => null, 'fax' => '022-777-8889', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '吉田', 'first_name' => '順子',
        'last_name_kana' => 'ヨシダ', 'first_name_kana' => 'ジュンコ',
        'medical_institutions_id' => null,
        'postal_code' => '730-0012', 'address_1' => '広島県', 'address_2' => '広島市中区上八丁堀10-11', 'address_3' => '吉田リハビリ内科',
        'phone' => '082-888-9999', 'cell_phone' => null, 'fax' => '082-888-9990', 'email' => null,
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
    ]);
  }
}
