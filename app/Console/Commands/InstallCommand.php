<?php

namespace App\Console\Commands;

use App\Support\Installer;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'izinara:install {--demo : Muat juga data demo} {--password= : Kata sandi admin} {--email= : Email admin}';
    protected $description = 'Pasang Izinara lewat terminal: migrasi, data master, akun admin, (opsional) data demo.';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Kata sandi untuk pengguna "admin" (min. 8 karakter)');
        if (strlen((string) $password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');
            return self::FAILURE;
        }
        $this->output->write(Installer::run($password, $this->option('email'), (bool) $this->option('demo')));
        $this->info('Izinara terpasang. Masuk dengan pengguna "admin".');

        return self::SUCCESS;
    }
}
