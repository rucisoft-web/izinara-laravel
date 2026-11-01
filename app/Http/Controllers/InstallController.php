<?php

namespace App\Http\Controllers;

use App\Support\Installer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    public function show(Request $request)
    {
        $checks = [
            'PHP ≥ 8.2 (terpasang '.PHP_VERSION.')' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'Ekstensi pdo_mysql' => extension_loaded('pdo_mysql'),
            'Ekstensi mbstring' => extension_loaded('mbstring'),
            'Ekstensi openssl' => extension_loaded('openssl'),
            'Ekstensi fileinfo (deteksi jenis lampiran)' => extension_loaded('fileinfo'),
            'Ekstensi zip & gd (opsional, untuk lampiran contoh demo)' => extension_loaded('zip') && extension_loaded('gd'),
            'Folder storage/ dapat ditulis' => is_writable(storage_path()),
            'Folder bootstrap/cache dapat ditulis' => is_writable(base_path('bootstrap/cache')),
            'File .env dapat ditulis' => is_writable(base_path('.env')) || (! file_exists(base_path('.env')) && is_writable(base_path())),
        ];

        return view('install.show', ['checks' => $checks, 'url' => $request->getSchemeAndHttpHost().$request->getBaseUrl()]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'app_url' => 'required|url',
            'db_host' => 'required|string', 'db_port' => 'required|integer',
            'db_database' => 'required|string', 'db_username' => 'required|string', 'db_password' => 'nullable|string',
            'admin_email' => 'required|email', 'admin_password' => 'required|string|min:8|confirmed',
        ], [], ['admin_password' => 'kata sandi admin', 'db_database' => 'nama database', 'db_username' => 'pengguna database']);

        try {
            new \PDO("mysql:host={$d['db_host']};port={$d['db_port']};dbname={$d['db_database']};charset=utf8mb4", $d['db_username'], $d['db_password'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['db_host' => 'Tidak bisa terhubung ke database: '.$e->getMessage()]);
        }

        Installer::setEnv([
            'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => rtrim($d['app_url'], '/'),
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $d['db_host'], 'DB_PORT' => $d['db_port'],
            'DB_DATABASE' => $d['db_database'], 'DB_USERNAME' => $d['db_username'], 'DB_PASSWORD' => $d['db_password'] ?? '',
        ]);
        config([
            'app.url' => rtrim($d['app_url'], '/'),
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $d['db_host'], 'database.connections.mysql.port' => $d['db_port'],
            'database.connections.mysql.database' => $d['db_database'], 'database.connections.mysql.username' => $d['db_username'],
            'database.connections.mysql.password' => $d['db_password'] ?? '',
        ]);
        DB::purge('mysql');

        try {
            Installer::run($d['admin_password'], $d['admin_email'], $request->boolean('demo'));
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['install' => 'Instalasi gagal: '.$e->getMessage()]);
        }

        return redirect('/login')->with('ok', 'Izinara berhasil dipasang. Masuk dengan pengguna "admin" dan kata sandi yang baru dibuat'
            .($request->boolean('demo') ? ', atau akun demo (kata sandi "izinara").' : '.'));
    }
}
