<?php
// database/seeders/TherapistSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TherapistSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('therapists')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('therapists')->insert([
      [
        'last_name' => '山田', 'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ', 'first_name_kana' => 'タロウ',
        'postal_code' => '160-0022', 'address_1' => '東京都', 'address_2' => '新宿区新宿3-1-1', 'address_3' => null,
        'phone' => '03-1234-5678', 'cell_phone' => '090-1234-5678', 'fax' => '03-6912-5678', 'email' => 'yamada.taro@example.com',
        'license_massage_code_number' => 12345, 'license_massage_issued_date' => '2010-03-15',
        'license_hari_code_number' => 11111, 'license_hari_issued_date' => '2010-03-15',
        'license_kyu_code_number' => 11111, 'license_kyu_issued_date' => '2010-03-15',
        'member_number' => 10001, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '鈴木', 'first_name' => '花子',
        'last_name_kana' => 'スズキ', 'first_name_kana' => 'ハナコ',
        'postal_code' => '530-0001', 'address_1' => '大阪府', 'address_2' => '大阪市北区梅田1-2-3', 'address_3' => 'メゾンビル19号室',
        'phone' => '06-2345-6789', 'cell_phone' => '080-2345-6789', 'fax' => '06-8146-9999', 'email' => 'suzuki.hanako@example.com',
        'license_massage_code_number' => 23456, 'license_massage_issued_date' => '2012-03-20',
        'license_hari_code_number' => 34567, 'license_hari_issued_date' => '2012-03-20',
        'license_kyu_code_number' => 34567, 'license_kyu_issued_date' => '2012-03-20',
        'member_number' => 10002, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '佐藤', 'first_name' => '健一',
        'last_name_kana' => 'サトウ', 'first_name_kana' => 'ケンイチ',
        'postal_code' => '460-0008', 'address_1' => '愛知県', 'address_2' => '名古屋市中区栄3-4-5', 'address_3' => null,
        'phone' => '052-345-6789', 'cell_phone' => '090-3456-7890', 'fax' => '04-9380-4320', 'email' => 'sato.kenichi@example.com',
        'license_massage_code_number' => 45678, 'license_massage_issued_date' => '2008-03-18',
        'license_hari_code_number' => 33333, 'license_hari_issued_date' => '2008-03-18',
        'license_kyu_code_number' => 33333, 'license_kyu_issued_date' => '2008-03-18',
        'member_number' => 10003, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '田中', 'first_name' => '美咲',
        'last_name_kana' => 'タナカ', 'first_name_kana' => 'ミサキ',
        'postal_code' => '220-0012', 'address_1' => '神奈川県', 'address_2' => '横浜市西区みなとみらい2-3-4', 'address_3' => 'ハイツコーポ33号室',
        'phone' => '045-456-7890', 'cell_phone' => '080-4567-8901', 'fax' => '05-0614-8641', 'email' => 'tanaka.misaki@example.com',
        'license_massage_code_number' => 56789, 'license_massage_issued_date' => '2015-03-22',
        'license_hari_code_number' => 67890, 'license_hari_issued_date' => '2015-03-22',
        'license_kyu_code_number' => 67890, 'license_kyu_issued_date' => '2015-03-22',
        'member_number' => 10004, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '伊藤', 'first_name' => '誠',
        'last_name_kana' => 'イトウ', 'first_name_kana' => 'マコト',
        'postal_code' => '812-0011', 'address_1' => '福岡県', 'address_2' => '福岡市博多区博多駅前1-5-6', 'address_3' => null,
        'phone' => '092-567-8901', 'cell_phone' => '090-5678-9012', 'fax' => '07-1848-2962', 'email' => 'ito.makoto@example.com',
        'license_massage_code_number' => 78901, 'license_massage_issued_date' => '2011-03-17',
        'license_hari_code_number' => 55555, 'license_hari_issued_date' => '2011-03-17',
        'license_kyu_code_number' => 55555, 'license_kyu_issued_date' => '2011-03-17',
        'member_number' => 10005, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '渡辺', 'first_name' => '直子',
        'last_name_kana' => 'ワタナベ', 'first_name_kana' => 'ナオコ',
        'postal_code' => '060-0001', 'address_1' => '北海道', 'address_2' => '札幌市中央区北1条西2-7-8', 'address_3' => 'レジデンス47号室',
        'phone' => '011-678-9012', 'cell_phone' => '080-6789-0123', 'fax' => '08-3082-7283', 'email' => 'watanabe.naoko@example.com',
        'license_massage_code_number' => 89012, 'license_massage_issued_date' => '2013-03-19',
        'license_hari_code_number' => 90123, 'license_hari_issued_date' => '2013-03-19',
        'license_kyu_code_number' => 90123, 'license_kyu_issued_date' => '2013-03-19',
        'member_number' => 10006, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '中村', 'first_name' => '義雄',
        'last_name_kana' => 'ナカムラ', 'first_name_kana' => 'ヨシオ',
        'postal_code' => '980-0021', 'address_1' => '宮城県', 'address_2' => '仙台市青葉区中央1-3-9', 'address_3' => null,
        'phone' => '022-789-0123', 'cell_phone' => '090-7890-1234', 'fax' => '09-4316-1604', 'email' => 'nakamura.yoshio@example.com',
        'license_massage_code_number' => 90123, 'license_massage_issued_date' => '2007-03-14',
        'license_hari_code_number' => 77777, 'license_hari_issued_date' => '2007-03-14',
        'license_kyu_code_number' => 77777, 'license_kyu_issued_date' => '2007-03-14',
        'member_number' => 10007, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '小林', 'first_name' => '由美',
        'last_name_kana' => 'コバヤシ', 'first_name_kana' => 'ユミ',
        'postal_code' => '730-0011', 'address_1' => '広島県', 'address_2' => '広島市中区基町10-11', 'address_3' => 'ロイヤルマンション61号室',
        'phone' => '082-890-1234', 'cell_phone' => '080-8901-2345', 'fax' => '02-5550-5925', 'email' => 'kobayashi.yumi@example.com',
        'license_massage_code_number' => 11234, 'license_massage_issued_date' => '2016-03-23',
        'license_hari_code_number' => 12345, 'license_hari_issued_date' => '2016-03-23',
        'license_kyu_code_number' => 12345, 'license_kyu_issued_date' => '2016-03-23',
        'member_number' => 10008, 'note' => null,
        'created_at' => now(), 'updated_at' => now(),
      ],
    ]);
  }
}
