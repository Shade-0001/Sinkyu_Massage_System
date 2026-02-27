<?php
// database/seeders/CareManagerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CareManagerSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('caremanagers')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('caremanagers')->insert([
      [
        'last_name' => '岡田', 'first_name' => '恵',
        'last_name_kana' => 'オカダ', 'first_name_kana' => 'メグミ',
        'service_providers_id' => null,
        'postal_code' => '160-0024', 'address_1' => '東京都', 'address_2' => '新宿区西新宿2-3-4', 'address_3' => '新宿ケアサポートセンター',
        'phone' => '03-1122-3344', 'cell_phone' => '080-1122-3344', 'fax' => '03-1122-3345', 'email' => 'okada@care-shinjuku.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '村上', 'first_name' => '浩',
        'last_name_kana' => 'ムラカミ', 'first_name_kana' => 'ヒロシ',
        'service_providers_id' => null,
        'postal_code' => '530-0003', 'address_1' => '大阪府', 'address_2' => '大阪市北区堂島1-4-5', 'address_3' => '梅田ケアプランセンター',
        'phone' => '06-2233-4455', 'cell_phone' => '090-2233-4455', 'fax' => '06-2233-4456', 'email' => 'murakami@care-umeda.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '藤田', 'first_name' => '真由美',
        'last_name_kana' => 'フジタ', 'first_name_kana' => 'マユミ',
        'service_providers_id' => null,
        'postal_code' => '460-0004', 'address_1' => '愛知県', 'address_2' => '名古屋市中区丸の内2-5-6', 'address_3' => '名古屋中央ケアセンター',
        'phone' => '052-344-5566', 'cell_phone' => '080-3445-5667', 'fax' => '052-344-5567', 'email' => 'fujita@care-nagoya.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '西村', 'first_name' => '和彦',
        'last_name_kana' => 'ニシムラ', 'first_name_kana' => 'カズヒコ',
        'service_providers_id' => null,
        'postal_code' => '220-0005', 'address_1' => '神奈川県', 'address_2' => '横浜市西区南幸2-6-7', 'address_3' => '横浜西ケアマネジャー事務所',
        'phone' => '045-455-6677', 'cell_phone' => '090-4556-6778', 'fax' => '045-455-6678', 'email' => 'nishimura@care-yokohama.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '橋本', 'first_name' => '典子',
        'last_name_kana' => 'ハシモト', 'first_name_kana' => 'ノリコ',
        'service_providers_id' => null,
        'postal_code' => '812-0013', 'address_1' => '福岡県', 'address_2' => '福岡市博多区博多駅南3-7-8', 'address_3' => '博多ケアプランステーション',
        'phone' => '092-566-7788', 'cell_phone' => '080-5667-7889', 'fax' => '092-566-7789', 'email' => 'hashimoto@care-hakata.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '石田', 'first_name' => '勇',
        'last_name_kana' => 'イシダ', 'first_name_kana' => 'イサム',
        'service_providers_id' => null,
        'postal_code' => '060-0003', 'address_1' => '北海道', 'address_2' => '札幌市中央区北3条西4-8-9', 'address_3' => '札幌ケアサポート',
        'phone' => '011-677-8899', 'cell_phone' => '090-6778-8990', 'fax' => '011-677-8890', 'email' => 'ishida@care-sapporo.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '森', 'first_name' => '智子',
        'last_name_kana' => 'モリ', 'first_name_kana' => 'トモコ',
        'service_providers_id' => null,
        'postal_code' => '980-0012', 'address_1' => '宮城県', 'address_2' => '仙台市青葉区花京院1-9-10', 'address_3' => '仙台中央ケアマネ事務所',
        'phone' => '022-788-9900', 'cell_phone' => '080-7889-9001', 'fax' => '022-788-9901', 'email' => 'mori@care-sendai.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
      [
        'last_name' => '池田', 'first_name' => '武',
        'last_name_kana' => 'イケダ', 'first_name_kana' => 'タケシ',
        'service_providers_id' => null,
        'postal_code' => '730-0013', 'address_1' => '広島県', 'address_2' => '広島市中区八丁堀3-10-11', 'address_3' => '広島東ケアプランセンター',
        'phone' => '082-899-0011', 'cell_phone' => '090-8990-0112', 'fax' => '082-899-0012', 'email' => 'ikeda@care-hiroshima.example.com',
        'note' => null, 'created_at' => now(), 'updated_at' => now(),
      ],
    ]);
  }
}
