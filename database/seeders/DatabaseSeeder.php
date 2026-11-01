<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** php artisan db:seed            → data master + akun admin
     *  php artisan db:seed --class=DemoSeeder → tambah data demo Indoguna */
    public function run(): void
    {
        $this->call(MasterDataSeeder::class);
        $this->call(AdminSeeder::class);
    }
}
