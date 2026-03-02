<?php
// database/seeders/ClinicUserSeeder.php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClinicUser;

class ClinicUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicUser::factory()->count(100)->create();
    }
}
