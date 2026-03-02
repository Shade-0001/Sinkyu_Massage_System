<?php
// database/seeders/InsurerSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurerSeeder extends Seeder
{
  public function run(): void
  {
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=0');
    DB::connection('sinkyu_massage_system_db')->table('insurers')->truncate();
    DB::connection('sinkyu_massage_system_db')->statement('SET FOREIGN_KEY_CHECKS=1');

    DB::connection('sinkyu_massage_system_db')->table('insurers')->insert([

      // 協会けんぽ（先頭2桁: 06）
      ['insurer_number' => '06130027', 'insurer_name' => '全国健康保険協会 東京支部',   'postal_code' => '160-8507', 'address' => '東京都新宿区西新宿1-24-1',         'recipient_name' => '東京支部 保険給付課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06270006', 'insurer_name' => '全国健康保険協会 大阪支部',   'postal_code' => '530-8232', 'address' => '大阪府大阪市北区梅田3-4-5',       'recipient_name' => '大阪支部 保険給付課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06230016', 'insurer_name' => '全国健康保険協会 愛知支部',   'postal_code' => '460-8508', 'address' => '愛知県名古屋市中区三の丸2-5-1',   'recipient_name' => '愛知支部 保険給付課',   'created_at' => now(), 'updated_at' => now()],

      // 組合健保（先頭2桁: 13〜19）
      ['insurer_number' => '13100026', 'insurer_name' => '東海自動車工業健康保険組合', 'postal_code' => '471-0001', 'address' => '愛知県豊田市中央区工業町3-1',     'recipient_name' => '東海自動車工業健保組合 事務局', 'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13220019', 'insurer_name' => '日本電子機器健康保険組合',   'postal_code' => '108-0001', 'address' => '東京都港区海岸通4-2-1',           'recipient_name' => '日本電子機器健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '14310018', 'insurer_name' => '関西繊維工業健康保険組合',   'postal_code' => '541-0053', 'address' => '大阪府大阪市中央区本町2-6-12',   'recipient_name' => '関西繊維工業健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],

      // 国民健康保険（先頭2桁: 31〜34）
      ['insurer_number' => '33130027', 'insurer_name' => '東京都国民健康保険団体連合会',   'postal_code' => '102-8017', 'address' => '東京都千代田区富士見2-3-3',       'recipient_name' => '東京都国保連合会 審査第一課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '33270005', 'insurer_name' => '大阪府国民健康保険団体連合会',   'postal_code' => '530-0005', 'address' => '大阪府大阪市北区中之島2-3-18',   'recipient_name' => '大阪府国保連合会 審査第一課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '33140016', 'insurer_name' => '神奈川県国民健康保険団体連合会', 'postal_code' => '231-8410', 'address' => '神奈川県横浜市中区日本大通10',   'recipient_name' => '神奈川県国保連合会 審査第一課', 'created_at' => now(), 'updated_at' => now()],

      // 後期高齢者医療（先頭2桁: 39）
      ['insurer_number' => '39130019', 'insurer_name' => '東京都後期高齢者医療広域連合',   'postal_code' => '163-0712', 'address' => '東京都新宿区西新宿2-7-1',         'recipient_name' => '東京都広域連合 給付管理課',     'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '39270003', 'insurer_name' => '大阪府後期高齢者医療広域連合',   'postal_code' => '559-8555', 'address' => '大阪府大阪市住之江区南港北1-14-16', 'recipient_name' => '大阪府広域連合 給付管理課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '39140007', 'insurer_name' => '神奈川県後期高齢者医療広域連合', 'postal_code' => '231-8589', 'address' => '神奈川県横浜市中区日本大通1',     'recipient_name' => '神奈川県広域連合 給付管理課',   'created_at' => now(), 'updated_at' => now()],

      // 国保組合（先頭2桁: 67）
      ['insurer_number' => '67010011', 'insurer_name' => '東京都医師国民健康保険組合',   'postal_code' => '113-0033', 'address' => '東京都文京区本郷2-38-21',         'recipient_name' => '東京都医師国保組合 事務局',     'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '67020028', 'insurer_name' => '大阪府歯科医師国民健康保険組合', 'postal_code' => '540-0006', 'address' => '大阪府大阪市中央区法円坂1-1-35', 'recipient_name' => '大阪府歯科医師国保組合 事務局', 'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '67030014', 'insurer_name' => '東京建設業国民健康保険組合',   'postal_code' => '108-0014', 'address' => '東京都港区芝5-26-20',             'recipient_name' => '東京建設業国保組合 事務局',     'created_at' => now(), 'updated_at' => now()],

      // 共済組合（先頭2桁: 72〜75）
      ['insurer_number' => '72140023', 'insurer_name' => '地方職員共済組合 東京支部',   'postal_code' => '102-8435', 'address' => '東京都千代田区平河町2-7-4',       'recipient_name' => '地方職員共済組合 給付課',       'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '73010016', 'insurer_name' => '国家公務員共済組合連合会',     'postal_code' => '102-8082', 'address' => '東京都千代田区三番町5-6',         'recipient_name' => '国家公務員共済連合会 給付部',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '74010019', 'insurer_name' => '日本私立学校振興・共済事業団', 'postal_code' => '113-8441', 'address' => '東京都文京区湯島2-1-1',           'recipient_name' => '私学事業団 共済部',             'created_at' => now(), 'updated_at' => now()],

      // 船員保険（先頭2桁: 02）
      ['insurer_number' => '02010003', 'insurer_name' => '全国健康保険協会 船員保険部', 'postal_code' => '105-8513', 'address' => '東京都港区虎ノ門1-12-10',         'recipient_name' => '船員保険部 給付課',             'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '02020010', 'insurer_name' => '全国健康保険協会 船員保険 大阪出張所', 'postal_code' => '559-0034', 'address' => '大阪府大阪市住之江区南港北2-1-10', 'recipient_name' => '船員保険部 大阪出張所', 'created_at' => now(), 'updated_at' => now()],

    ]);
  }
}
