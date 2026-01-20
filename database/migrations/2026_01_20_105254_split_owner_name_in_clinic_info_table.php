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
        Schema::table('clinic_info', function (Blueprint $table) {
            // owner_nameカラムの後に新しいカラムを追加
            $table->string('owner_last_name')->nullable()->after('owner_name');
            $table->string('owner_first_name')->nullable()->after('owner_last_name');
        });

        // 既存のowner_nameデータを姓と名に分割（スペースで分割）
        DB::statement("
            UPDATE clinic_info
            SET
                owner_last_name = SUBSTRING_INDEX(owner_name, ' ', 1),
                owner_first_name = TRIM(SUBSTRING(owner_name, LOCATE(' ', owner_name) + 1))
            WHERE owner_name IS NOT NULL AND owner_name != ''
        ");

        Schema::table('clinic_info', function (Blueprint $table) {
            // owner_nameカラムを削除
            $table->dropColumn('owner_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinic_info', function (Blueprint $table) {
            // owner_nameカラムを復元
            $table->string('owner_name')->nullable()->after('clinic_name');
        });

        // 姓と名を結合してowner_nameに戻す
        DB::statement("
            UPDATE clinic_info
            SET owner_name = CONCAT(COALESCE(owner_last_name, ''), ' ', COALESCE(owner_first_name, ''))
            WHERE owner_last_name IS NOT NULL OR owner_first_name IS NOT NULL
        ");

        Schema::table('clinic_info', function (Blueprint $table) {
            // 姓と名のカラムを削除
            $table->dropColumn(['owner_last_name', 'owner_first_name']);
        });
    }
};
