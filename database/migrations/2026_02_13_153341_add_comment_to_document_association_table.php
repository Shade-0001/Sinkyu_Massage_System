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
        Schema::table('document_association', function (Blueprint $table) {
            $table->string('comment')->nullable()->after('document_id_2')->comment('文書名称コメント');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_association', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
