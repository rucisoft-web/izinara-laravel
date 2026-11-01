<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/** Utilitas instalasi: status terpasang, tulis .env, migrasi & seed. Dipakai wizard web dan perintah artisan. */
class Installer
{
    public static function lockPath(): string
    {
        return storage_path('app/installed');
    }

    public static function installed(): bool
    {
        return filter_var(env('IZINARA_INSTALLED', false), FILTER_VALIDATE_BOOL) || file_exists(self::lockPath());
    }

    /** Ubah/menambah nilai di file .env tanpa menghapus baris lain. */
    public static function setEnv(array $values): void
    {
        $path = base_path('.env');
        $env = file_exists($path) ? file_get_contents($path) : '';
        foreach ($values as $key => $value) {
            $value = (string) $value;
            if ($value === '' || preg_match('/[\s#"\'\\\\$=]/', $value)) {
                $value = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
            }
            $line = $key.'='.$value;
            $env = preg_match('/^'.preg_quote($key, '/').'=.*$/m', $env)
                ? preg_replace('/^'.preg_quote($key, '/').'=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env)
                : rtrim($env)."\n".$line."\n";
        }
        file_put_contents($path, $env);
    }

    /** Migrasi + data master + akun admin (+ data demo). Mengembalikan log keluaran artisan. */
    public static function run(string $adminPassword, ?string $adminEmail = null, bool $demo = false): string
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $log = '';
        Artisan::call('migrate', ['--force' => true]);
        $log .= Artisan::output();
        Artisan::call('db:seed', ['--force' => true]);
        $log .= Artisan::output();
        $admin = User::where('username', 'admin')->first();
        $admin?->update(array_filter(['password' => Hash::make($adminPassword), 'email' => $adminEmail]));
        if ($demo) {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
            $log .= Artisan::output();
        }
        @mkdir(dirname(self::lockPath()), 0775, true);
        file_put_contents(self::lockPath(), 'Terpasang '.now()->toDateTimeString()."\n");

        return $log;
    }
}
