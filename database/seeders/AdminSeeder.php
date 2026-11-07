<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['username' => 'admin'], [
            'name' => 'Administrator Izinara', 'email' => 'admin@izinara.local',
            'password' => env('IZINARA_ADMIN_PASSWORD', 'admin'), 'role' => 'admin', 'job_title' => 'Administrator sistem',
        ]);
    }
}
