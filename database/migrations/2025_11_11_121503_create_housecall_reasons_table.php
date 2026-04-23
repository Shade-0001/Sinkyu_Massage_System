<?php
//-- database/migrations/2025_11_11_121503_create_housecall_reasons_table.php --//

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection(env('DB_CONNECTION', 'mysql'))->create('housecall_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('housecall_reason');
            $table->timestamps();
        });

        // 初期データ投入
        DB::connection(env('DB_CONNECTION', 'mysql'))->table('housecall_reasons')->insert([
            ['housecall_reason' => '独歩による公共交通機関を使っての外出が困難', 'created_at' => now(), 'updated_at' => now()],
            ['housecall_reason' => '認知症や視覚･内部･精神障害などにより単独での外出が困難', 'created_at' => now(), 'updated_at' => now()],
            ['housecall_reason' => 'その他', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(env('DB_CONNECTION', 'mysql'))->dropIfExists('housecall_reasons');
    }
};
