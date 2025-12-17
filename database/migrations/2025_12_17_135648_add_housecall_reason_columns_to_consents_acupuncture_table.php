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
        Schema::table('consents_acupuncture', function (Blueprint $table) {
            // is_housecall_requiredの後に追加
            $table->unsignedBigInteger('housecall_reason_id')->nullable()->after('is_housecall_required');
            $table->string('housecall_reason_addendum')->nullable()->after('housecall_reason_id');

            // 外部キー制約
            $table->foreign('housecall_reason_id')->references('id')->on('housecall_reasons')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consents_acupuncture', function (Blueprint $table) {
            $table->dropForeign(['housecall_reason_id']);
            $table->dropColumn(['housecall_reason_id', 'housecall_reason_addendum']);
        });
    }
};
