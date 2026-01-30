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
            // INT(11)からBIGINTに変更（免許証記号番号は14桁程度になる可能性があるため）
            $table->bigInteger('license_hari_code_number')->nullable()->change();
            $table->bigInteger('license_kyu_code_number')->nullable()->change();
            $table->bigInteger('license_massage_code_number')->nullable()->change();
            $table->bigInteger('member_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapists', function (Blueprint $table) {
            // BIGINTからINT(11)に戻す
            $table->integer('license_hari_code_number')->nullable()->change();
            $table->integer('license_kyu_code_number')->nullable()->change();
            $table->integer('license_massage_code_number')->nullable()->change();
            $table->integer('member_number')->nullable()->change();
        });
    }
};
