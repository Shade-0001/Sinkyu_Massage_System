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
        Schema::table('documents', function (Blueprint $table) {
            $table->boolean('show_patient_info')->default(false)->after('line_height');
            $table->string('patient_name', 100)->nullable()->after('show_patient_info');
            $table->string('patient_illness', 100)->nullable()->after('patient_name');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['show_patient_info', 'patient_name', 'patient_illness']);
        });
    }
};
