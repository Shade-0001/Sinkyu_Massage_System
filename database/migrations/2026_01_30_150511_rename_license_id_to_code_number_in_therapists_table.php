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
            // フィールド名をidからcode_numberに変更
            $table->renameColumn('license_hari_id', 'license_hari_code_number');
            $table->renameColumn('license_kyu_id', 'license_kyu_code_number');
            $table->renameColumn('license_massage_id', 'license_massage_code_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapists', function (Blueprint $table) {
            // フィールド名をcode_numberからidに戻す
            $table->renameColumn('license_hari_code_number', 'license_hari_id');
            $table->renameColumn('license_kyu_code_number', 'license_kyu_id');
            $table->renameColumn('license_massage_code_number', 'license_massage_id');
        });
    }
};
