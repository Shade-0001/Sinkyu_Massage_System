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
        Schema::table('therapists', function (Blueprint $table) {
            // 不要な免許証番号フィールドを削除
            $table->dropColumn([
                'license_hari_number',
                'license_kyu_number',
                'license_massage_number',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapists', function (Blueprint $table) {
            // 削除したフィールドを復元
            $table->integer('license_hari_number')->nullable()->after('license_hari_id');
            $table->integer('license_kyu_number')->nullable()->after('license_kyu_id');
            $table->integer('license_massage_number')->nullable()->after('license_massage_id');
        });
    }
};
