<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 外部キー制約を一時的に無効化
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 既存データを全削除
        DB::table('therapy_contents')->delete();

        // 新しいデータを挿入
        // はり･きゅう (therapy_type = 1)
        DB::table('therapy_contents')->insert([
            ['therapy_type' => 1, 'therapy_content' => 'はり', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 1, 'therapy_content' => 'きゅう', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 1, 'therapy_content' => 'はり･きゅう併用', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 1, 'therapy_content' => '電療（電気針）', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 1, 'therapy_content' => '電療（電気温灸器）', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 1, 'therapy_content' => '電療（電気光線器具）', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // あんま･マッサージ (therapy_type = 2)
        DB::table('therapy_contents')->insert([
            ['therapy_type' => 2, 'therapy_content' => '電療（電気光線器具）', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 2, 'therapy_content' => 'マッサージ', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 2, 'therapy_content' => '変形徒手矯正術', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 2, 'therapy_content' => '温罨法', 'created_at' => now(), 'updated_at' => now()],
            ['therapy_type' => 2, 'therapy_content' => '温罨法･電気光線器具', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 外部キー制約を再有効化
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 旧データを復元
        DB::table('therapy_contents')->delete();

        DB::table('therapy_contents')->insert([
            ['id' => 1, 'therapy_type' => 1, 'therapy_content' => 'はり', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 2, 'therapy_type' => 1, 'therapy_content' => 'はり（電気針併用）', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 3, 'therapy_type' => 1, 'therapy_content' => 'きゅう', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 4, 'therapy_type' => 1, 'therapy_content' => 'きゅう（電気温灸器併用）', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 5, 'therapy_type' => 1, 'therapy_content' => 'はりきゅう併用', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 6, 'therapy_type' => 1, 'therapy_content' => 'はりきゅう併用（電気針･電気温灸器併用）', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-12-05 10:48:17'],
            ['id' => 7, 'therapy_type' => 2, 'therapy_content' => 'マッサージ', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 8, 'therapy_type' => 2, 'therapy_content' => '変形徒手矯正術', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 9, 'therapy_type' => 2, 'therapy_content' => '温罨法', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-11-11 11:58:27'],
            ['id' => 10, 'therapy_type' => 2, 'therapy_content' => '温罨法･電気光線器具', 'created_at' => '2025-11-11 11:58:27', 'updated_at' => '2025-12-05 10:49:10'],
        ]);
    }
};
