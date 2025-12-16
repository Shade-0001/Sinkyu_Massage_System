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
        Schema::table('insurances', function (Blueprint $table) {
            // 被保険者番号などの大きな数値を扱うカラムをBIGINTに変更
            $table->bigInteger('insured_number')->nullable()->change();
            $table->bigInteger('code_number')->nullable()->change();
            $table->bigInteger('account_number')->nullable()->change();
            $table->bigInteger('locality_code')->nullable()->change();
            $table->bigInteger('recipient_code')->nullable()->change();
            $table->bigInteger('public_funds_payer_code')->nullable()->change();
            $table->bigInteger('public_funds_recipient_code')->nullable()->change();
            $table->bigInteger('locality_code_family')->nullable()->change();
            $table->bigInteger('recipient_code_family')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            // 元のINT型に戻す
            $table->integer('insured_number')->nullable()->change();
            $table->integer('code_number')->nullable()->change();
            $table->integer('account_number')->nullable()->change();
            $table->integer('locality_code')->nullable()->change();
            $table->integer('recipient_code')->nullable()->change();
            $table->integer('public_funds_payer_code')->nullable()->change();
            $table->integer('public_funds_recipient_code')->nullable()->change();
            $table->integer('locality_code_family')->nullable()->change();
            $table->integer('recipient_code_family')->nullable()->change();
        });
    }
};
