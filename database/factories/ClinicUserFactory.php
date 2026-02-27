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

        $idx       = array_rand($lastNames);
        $firstIdx  = array_rand($firstNames);
        $birthYear = $this->faker->numberBetween(1930, 1980);
        $birthday  = $this->faker->dateTimeBetween("{$birthYear}-01-01", "{$birthYear}-12-31")->format('Y-m-d');
        $age       = (int) date('Y') - $birthYear;

        return [
            'last_name'                    => $lastNames[$idx],
            'first_name'                   => $firstNames[$firstIdx],
            'last_kana'                    => $lastKanas[$idx],
            'first_kana'                   => $firstKanas[$firstIdx],
            'birthday'                     => $birthday,
            'age'                          => $age,
            'gender_id'                    => $this->faker->numberBetween(1, 2),
            'postal_code'                  => $this->faker->numerify('###-####'),
            'address_1'                    => $this->faker->randomElement(['東京都', '大阪府', '神奈川県', '愛知県', '福岡県', '北海道', '宮城県', '広島県']),
            'address_2'                    => $this->faker->city() . $this->faker->numerify('#丁目#番'),
            'address_3'                    => null,
            'phone'                        => $this->faker->numerify('0#-####-####'),
            'cell_phone'                   => $this->faker->numerify('0##-####-####'),
            'fax'                          => null,
            'email'                        => $this->faker->unique()->safeEmail(),
            'housecall_distance'           => $this->faker->numberBetween(1, 20),
            'housecall_additional_distance' => $this->faker->numberBetween(0, 5),
            'is_redeemed'                  => false,
            'application_count'            => $this->faker->numberBetween(1, 30),
            'note'                         => null,
        ];
    }
}
