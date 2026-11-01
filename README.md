# Izinara — manajemen proyek perizinan impor & izin edar pangan

Izinara mengelola setiap kebutuhan izin sebagai **proyek**: dari kebutuhan muncul (PO impor, produk baru, izin akan habis, sertifikasi halal) sampai semua izin terbit dan tercatat di register.

Dibangun dengan **Laravel 12 + MySQL**, tanpa build step front-end, sehingga bisa dipasang di shared hosting cPanel.

## Fitur

- **WBS & jadwal CPM**: paket per jenis izin, kegiatan, milestone, dependensi, slack, jalur kritis, baseline vs rencana vs realisasi.
- **Gantt chart** per proyek dan portofolio; manajer bisa menggeser jadwal langsung di Gantt.
- **Copy from template**: proyek baru cukup disalin dari Template WBS (atau disusun otomatis dari item code / disalin dari proyek lain).
- **Rujukan peraturan perundang-undangan** di tingkat jenis izin, langkah, dan kegiatan — dengan status keberlakuan (berlaku, berlaku dengan perubahan, perlu konfirmasi, dicabut) dan penggantian rujukan massal saat peraturan diganti.
- **Lampiran digital** (PDF, Excel, Word, JPG, ZIP, email, …) di kegiatan dan dokumen wajib, dengan verifikasi dokumen.
- **Komunikasi antar bagian**: komentar dengan @mention orang/bagian, komentar pada dokumen, komentar perlu tindakan, memo bertenggat dengan statistik respons, kotak masuk notifikasi.
- **Beban kerja PIC** per minggu vs kapasitas, **proyeksi** P50/P80 berbasis histori, izin terbit/habis per bulan, skenario.
- **Register izin** dengan pemicu proyek perpanjangan.
- **Sertifikasi halal**: proyek registrasi SHLN dan sertifikasi halal BPJPH.
- **Halaman informasi** (landing page) untuk pengguna internal, **tombol Bantuan** di setiap layar dan **Panduan** lengkap dengan tangkapan layar.
- **Data demo PT Indoguna Utama**: 18 proyek (3 per kelompok izin), lampiran contoh, diskusi, memo, register izin.

## Instalasi

- **cPanel tanpa terminal**: lihat [INSTALL-CPANEL.md](INSTALL-CPANEL.md) — unggah ZIP rilis, buat database, jalankan wizard `/install`.
- **Pengembangan lokal**:

```bash
composer install
cp .env.example .env && php artisan key:generate
# isi DB_* di .env
php artisan izinara:install --demo --password=admin12345
php artisan serve
```

## Struktur penting

| Lokasi | Isi |
|---|---|
| `app/Services/Scheduler.php` | Penjadwalan CPM (forward/backward pass, slack, jalur kritis) |
| `app/Services/ProjectBuilder.php` | Penyusunan WBS otomatis dari kelompok izin & item |
| `app/Services/WbsTemplateService.php` | Simpan/salin Template WBS |
| `app/Services/Forecaster.php` | Faktor keterlambatan, proyeksi P50/P80, beban kerja |
| `app/Services/Communication.php` | Komentar, @mention, memo, notifikasi |
| `database/seeders/MasterDataSeeder.php` | Bagian, peraturan, jenis izin & langkah, kelompok izin |
| `database/seeders/DemoSeeder.php` | Data demo Indoguna |
| `config/bantuan.php` | Isi tombol Bantuan & halaman Panduan |
| `tools/screenshot-bantuan.cjs` | Membuat ulang tangkapan layar Panduan (Playwright) |

## Pengujian

```bash
php artisan test
```

## Catatan peraturan

Pustaka peraturan bawaan diperiksa per 1 November 2026. Peraturan bertanda *perlu konfirmasi* sebaiknya dicek ke JDIH instansi terkait sebelum dipakai sebagai acuan; admin dapat memperbarui status dan mengganti rujukan di menu **§ Peraturan**.
