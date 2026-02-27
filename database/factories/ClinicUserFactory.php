<?php
// database/factories/ClinicUserFactory.php


namespace Database\Factories;

use App\Models\ClinicUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClinicUser>
 */
class ClinicUserFactory extends Factory
{
    protected $model = ClinicUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 日本語風の名前を生成
        $lastNames  = ['田中', '鈴木', '佐藤', '山田', '伊藤', '渡辺', '中村', '小林', '加藤', '吉田',
                       '山本', '松本', '井上', '木村', '林', '斎藤', '清水', '山口', '池田', '橋本'];
        $firstNames = ['太郎', '花子', '健一', '美咲', '誠', '直子', '義雄', '由美', '浩', '恵子',
                       '隆', '典子', '勇', '智子', '武', '順子', '博', '真由美', '和彦', '恵'];
        $lastKanas  = ['タナカ', 'スズキ', 'サトウ', 'ヤマダ', 'イトウ', 'ワタナベ', 'ナカムラ', 'コバヤシ', 'カトウ', 'ヨシダ',
                       'ヤマモト', 'マツモト', 'イノウエ', 'キムラ', 'ハヤシ', 'サイトウ', 'シミズ', 'ヤマグチ', 'イケダ', 'ハシモト'];
        $firstKanas = ['タロウ', 'ハナコ', 'ケンイチ', 'ミサキ', 'マコト', 'ナオコ', 'ヨシオ', 'ユミ', 'ヒロシ', 'ケイコ',
                       'タカシ', 'ノリコ', 'イサム', 'トモコ', 'タケシ', 'ジュンコ', 'ヒロシ', 'マユミ', 'カズヒコ', 'メグミ'];

        $cities = [
            '札幌市北区', '仙台市青葉区', 'さいたま市大宮区', '千葉市中央区',
            '新宿区', '渋谷区', '世田谷区', '江東区', '品川区', '練馬区',
            '横浜市西区', '横浜市港北区', '川崎市中原区', '相模原市中央区',
            '名古屋市中区', '名古屋市千種区', '京都市左京区', '京都市伏見区',
            '大阪市中央区', '大阪市北区', '堺市堺区', '神戸市兵庫区',
            '広島市中区', '福岡市博多区', '福岡市中央区', '熊本市中央区',
        ];

        $buildingPrefixes = ['グリーン', 'サンシャイン', 'パークサイド', 'コート', 'ハイツ', 'プラザ', 'ガーデン', 'メゾン', 'ビレッジ', 'テラス'];
        $buildingTypes    = ['マンション', 'アパート', 'コーポ', 'ハイツ', 'ビル'];

        $idx      = array_rand($lastNames);
        $firstIdx = array_rand($firstNames);
        $birthYear = $this->faker->numberBetween(1930, 1980);
        $birthday  = $this->faker->dateTimeBetween("{$birthYear}-01-01", "{$birthYear}-12-31")->format('Y-m-d');
        $age       = (int) date('Y') - $birthYear;

        // address_3: 50%の確率でマンション名・部屋番号を生成
        $address3 = $this->faker->boolean(50)
            ? $this->faker->randomElement($buildingPrefixes) . $this->faker->randomElement($buildingTypes)
              . $this->faker->numerify('##') . '号室'
            : null;

        // fax: 20%の確率で生成
        $fax = $this->faker->boolean(20)
            ? $this->faker->numerify('0#-####-####')
            : null;

        // note: 20%の確率で短いメモを生成
        $noteOptions = ['緊急連絡先：別途確認', '家族同居', '独居', '要配慮', '訪問時インターホン使用不可', '玄関に鍵あり'];
        $note = $this->faker->boolean(20)
            ? $this->faker->randomElement($noteOptions)
            : null;

        return [
            'last_name'                     => $lastNames[$idx],
            'first_name'                    => $firstNames[$firstIdx],
            'last_kana'                     => $lastKanas[$idx],
            'first_kana'                    => $firstKanas[$firstIdx],
            'birthday'                      => $birthday,
            'age'                           => $age,
            'gender_id'                     => $this->faker->numberBetween(1, 2),
            'postal_code'                   => $this->faker->numerify('###-####'),
            'address_1'                     => $this->faker->randomElement(['東京都', '大阪府', '神奈川県', '愛知県', '福岡県', '北海道', '宮城県', '広島県']),
            'address_2'                     => $this->faker->randomElement($cities) . $this->faker->numerify('#丁目#番'),
            'address_3'                     => $address3,
            'phone'                         => $this->faker->numerify('0#-####-####'),
            'cell_phone'                    => $this->faker->numerify('0##-####-####'),
            'fax'                           => $fax,
            'email'                         => $this->faker->unique()->safeEmail(),
            'housecall_distance'            => $this->faker->numberBetween(1, 20),
            'housecall_additional_distance' => $this->faker->numberBetween(0, 5),
            'is_redeemed'                   => false,
            'application_count'             => $this->faker->numberBetween(1, 30),
            'note'                          => $note,
        ];
    }
}
