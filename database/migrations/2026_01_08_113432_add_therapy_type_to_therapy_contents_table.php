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
        Schema::table('therapy_contents', function (Blueprint $table) {
            $table->tinyInteger('therapy_type')->after('id')->comment('1:はり･きゅう, 2:あんま･マッサージ');
        });

        // 既存データにtherapy_typeを設定
        DB::table('therapy_contents')->whereIn('id', [1, 2, 3, 4, 5, 6])->update(['therapy_type' => 1]); // はり･きゅう
        DB::table('therapy_contents')->whereIn('id', [7, 8, 9, 10])->update(['therapy_type' => 2]); // あんま･マッサージ
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('therapy_contents', function (Blueprint $table) {
            $table->dropColumn('therapy_type');
        });
    }
};
