<?php
//-- database/migrations/2025_11_12_121217_rename_house_visit_reasons_to_housecall_reasons.php --//

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
        if (!Schema::hasTable('housecall_reasons') && Schema::hasTable('house_visit_reasons')) {
            Schema::rename('house_visit_reasons', 'housecall_reasons');

            Schema::table('housecall_reasons', function (Blueprint $table) {
                $table->renameColumn('house_visit_reason', 'housecall_reason');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // カラム名を戻す
        Schema::table('housecall_reasons', function (Blueprint $table) {
            $table->renameColumn('housecall_reason', 'house_visit_reason');
        });

        // テーブル名を戻す
        Schema::rename('housecall_reasons', 'house_visit_reasons');
    }
};
