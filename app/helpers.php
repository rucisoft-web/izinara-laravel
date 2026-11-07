<?php

use Carbon\CarbonInterface;

if (! function_exists('tgl')) {
    /** Tanggal format Indonesia singkat: 05 Nov 2026. */
    function tgl($date, string $fmt = 'd M Y'): string
    {
        if (! $date) return '–';
        $d = $date instanceof CarbonInterface ? $date : \Carbon\Carbon::parse($date);
        return $d->locale('id')->translatedFormat($fmt);
    }
}

if (! function_exists('badge')) {
    /** Badge status berwarna. */
    function badge(?string $key, ?string $label = null): string
    {
        $cls = match ($key) {
            'selesai', 'terverifikasi', 'hijau', 'aktif', 'berlaku', 'rendah' => 'ok',
            'berjalan', 'dijawab', 'diajukan', 'berlaku_diubah', 'normal' => 'info',
            'menunggu', 'kuning', 'segera_habis', 'perlu_konfirmasi', 'tertahan', 'terbuka', 'sedang', 'tinggi' => 'warn',
            'terhambat', 'ditolak', 'merah', 'kedaluwarsa', 'dicabut', 'kritis', 'batal' => 'bad',
            default => 'muted',
        };
        return '<span class="badge b-'.$cls.'">'.e($label ?? ucfirst(str_replace('_', ' ', (string) $key))).'</span>';
    }
}

if (! function_exists('health_dot')) {
    function health_dot(string $h): string
    {
        $t = ['hijau' => 'Sesuai jadwal', 'kuning' => 'Perlu perhatian', 'merah' => 'Terlambat / terhambat', 'abu' => 'Batal'][$h] ?? $h;
        return '<span class="dot dot-'.$h.'" title="'.$t.'"></span>';
    }
}
