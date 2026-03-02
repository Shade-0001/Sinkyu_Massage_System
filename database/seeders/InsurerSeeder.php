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
      ['insurer_number' => '06130027', 'insurer_name' => '全国健康保険協会 東京支部',     'postal_code' => '160-8507', 'address' => '東京都新宿区西新宿1-24-1',       'recipient_name' => '東京支部 保険給付課',           'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06270006', 'insurer_name' => '全国健康保険協会 大阪支部',     'postal_code' => '530-8232', 'address' => '大阪府大阪市北区梅田3-4-5',     'recipient_name' => '大阪支部 保険給付課',           'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06230016', 'insurer_name' => '全国健康保険協会 愛知支部',     'postal_code' => '460-8508', 'address' => '愛知県名古屋市中区三の丸2-5-1', 'recipient_name' => '愛知支部 保険給付課',           'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13100026', 'insurer_name' => '東海自動車工業健康保険組合',     'postal_code' => '471-0001', 'address' => '愛知県豊和市中央区工業町3-1',   'recipient_name' => '東海自動車工業健保組合 事務局', 'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13220019', 'insurer_name' => '日本電子機器健康保険組合',       'postal_code' => '108-0001', 'address' => '東京都港区海岸通4-2-1',       'recipient_name' => '日本電子機器健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13140037', 'insurer_name' => '近畿電気産業健康保険組合',       'postal_code' => '571-0001', 'address' => '大阪府東摂市産業通1006番地',   'recipient_name' => '近畿電気産業健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '33130027', 'insurer_name' => '東京都国民健康保険団体連合会', 'postal_code' => '102-8017', 'address' => '東京都千代田区富士見2-3-3',     'recipient_name' => '東京都国保連合会 審査第一課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '33270005', 'insurer_name' => '大阪府国民健康保険団体連合会', 'postal_code' => '530-0005', 'address' => '大阪府大阪市北区中之島2-3-18', 'recipient_name' => '大阪府国保連合会 審査第一課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '39130019', 'insurer_name' => '東京都後期高齢者医療広域連合', 'postal_code' => '163-0712', 'address' => '東京都新宿区西新宿2-7-1',       'recipient_name' => '東京都広域連合 給付管理課',     'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '39270003', 'insurer_name' => '大阪府後期高齢者医療広域連合', 'postal_code' => '559-8555', 'address' => '大阪府大阪市住之江区南港北1-14-16', 'recipient_name' => '大阪府広域連合 給付管理課', 'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06140016', 'insurer_name' => '全国健康保険協会 神奈川支部',   'postal_code' => '231-8502', 'address' => '神奈川県横浜市中区北仲通5-57', 'recipient_name' => '神奈川支部 保険給付課',         'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06280014', 'insurer_name' => '全国健康保険協会 兵庫支部',     'postal_code' => '650-0024', 'address' => '兵庫県神戸市中央区海岸通1-2-19', 'recipient_name' => '兵庫支部 保険給付課',         'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13300022', 'insurer_name' => '関西電線工業健康保険組合',       'postal_code' => '541-0001', 'address' => '大阪府大阪市中央区南本町5-3-10', 'recipient_name' => '関西電線工業健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13410029', 'insurer_name' => '首都精密機械健康保険組合',       'postal_code' => '100-0001', 'address' => '東京都千代田区大手町3-5-1',   'recipient_name' => '首都精密機械健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '33140016', 'insurer_name' => '神奈川県国民健康保険団体連合会', 'postal_code' => '231-8410', 'address' => '神奈川県横浜市中区日本大通10', 'recipient_name' => '神奈川県国保連合会 審査第一課', 'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '39140007', 'insurer_name' => '神奈川県後期高齢者医療広域連合', 'postal_code' => '231-8589', 'address' => '神奈川県横浜市中区日本大通1',  'recipient_name' => '神奈川県広域連合 給付管理課',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06120003', 'insurer_name' => '全国健康保険協会 北海道支部',   'postal_code' => '060-8570', 'address' => '北海道札幌市中央区大通西2-6', 'recipient_name' => '北海道支部 保険給付課',         'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '06400024', 'insurer_name' => '全国健康保険協会 福岡支部',     'postal_code' => '812-8512', 'address' => '福岡県福岡市博多区博多駅前3-2-8', 'recipient_name' => '福岡支部 保険給付課',         'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13510015', 'insurer_name' => '東京映像通信健康保険組合',       'postal_code' => '108-0001', 'address' => '東京都港区港南通2-4-8',       'recipient_name' => '東京映像通信健保組合 事務局',   'created_at' => now(), 'updated_at' => now()],
      ['insurer_number' => '13620012', 'insurer_name' => '武蔵情報産業健康保険組合',       'postal_code' => '211-0001', 'address' => '神奈川県川崎市中原区新丸子町2-3-5', 'recipient_name' => '武蔵情報産業健保組合 事務局', 'created_at' => now(), 'updated_at' => now()],
    ]);
  }
}
