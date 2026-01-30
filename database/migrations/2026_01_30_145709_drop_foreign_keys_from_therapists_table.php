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
            // 外部キー制約を削除
            $table->dropForeign('therapists_license_FK');
            $table->dropForeign('therapists_license_FK_1');
            $table->dropForeign('therapists_license_FK_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapists', function (Blueprint $table) {
            // 外部キー制約を復元
            $table->foreign('license_hari_id', 'therapists_license_FK')
                  ->references('id')
                  ->on('license');
            $table->foreign('license_kyu_id', 'therapists_license_FK_1')
                  ->references('id')
                  ->on('license');
            $table->foreign('license_massage_id', 'therapists_license_FK_2')
                  ->references('id')
                  ->on('license');
        });
    }
};
