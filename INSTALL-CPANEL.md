# Instalasi Izinara di cPanel (tanpa terminal)

Paket rilis `izinara-laravel-vX.zip` sudah berisi folder `vendor/`, jadi **tidak perlu Composer, Node.js, atau SSH**.

## Kebutuhan hosting

| Item | Minimal |
|---|---|
| PHP | 8.2 atau lebih baru (atur di cPanel → **Select PHP Version** / **MultiPHP Manager**) |
| Ekstensi PHP | pdo_mysql, mbstring, openssl, fileinfo, tokenizer, xml, ctype; zip & gd disarankan |
| Database | MySQL 5.7+ atau MariaDB 10.3+ |
| Ruang disk | ± 60 MB aplikasi + lampiran |

## Langkah 1 — Buat database

1. cPanel → **MySQL® Databases**.
2. *Create New Database*, mis. `izinara` (nama lengkap jadi `akuncpanel_izinara`).
3. *Add New User*, mis. `izinara` dengan kata sandi kuat.
4. *Add User To Database* → centang **ALL PRIVILEGES**.

Catat nama database, pengguna, dan kata sandinya.

## Langkah 2 — Unggah file

Pilih **salah satu** cara:

### Cara A (disarankan): subdomain/domain diarahkan ke folder `public`
1. cPanel → **File Manager** → buka folder home (mis. `/home/akuncpanel`).
2. Buat folder `izinara`, masuk, **Upload** ZIP, lalu **Extract**.
3. cPanel → **Domains** (atau **Subdomains**) → buat/ubah domain, mis. `izin.indoguna.co.id`, dan isi **Document Root** = `izinara/public`.

Dengan cara ini file `.env`, `storage/`, dan `vendor/` berada di luar document root.

### Cara B: langsung ke `public_html`
Bila document root tidak bisa diubah, ekstrak ZIP langsung ke `public_html` (atau subfolder di dalamnya).
File `.htaccess` di akar paket meneruskan semua permintaan ke folder `public/` dan menolak akses ke `.env`.

## Langkah 3 — Jalankan wizard instalasi

1. Buka alamat aplikasi di browser, mis. `https://izin.indoguna.co.id`. Anda otomatis diarahkan ke `/install`.
2. Pastikan semua pemeriksaan server bertanda ✓.
3. Isi host database (`localhost`), nama database, pengguna, dan kata sandi dari Langkah 1.
4. Isi email dan kata sandi **admin** (minimal 8 karakter).
5. Centang **Muat data demo Indoguna** bila ingin data latihan untuk pengenalan aplikasi ke pengguna (bisa dikosongkan untuk instalasi produksi).
6. Klik **Pasang Izinara** dan tunggu 1–2 menit.

Wizard akan: membuat `.env` dan `APP_KEY`, membuat semua tabel, mengisi data master (bagian, peraturan, jenis izin & langkah, kelompok izin, template), membuat akun `admin`, dan (opsional) memuat data demo. Setelah selesai, `/install` otomatis ditutup.

## Langkah 4 — Masuk

* Admin: pengguna `admin`, kata sandi yang Anda buat.
* Akun demo (bila dimuat), kata sandi `izinara`: `sari.manajer` (manajer), `dewi.izin` & `fajar.izin` (perizinan), `rina.ml` & `maya.ml` (BPOM), `hasan.halal` & `nur.halal` (halal), `budi.purchasing`, `andi.impor`, `tono.direksi` (viewer).

**Untuk produksi:** setelah masa latihan, ganti kata sandi akun demo atau nonaktifkan di menu *Pengguna & bagian*, atau pasang ulang tanpa data demo.

## Pengaturan tambahan

* **Batas unggah lampiran.** Izinara menerima file hingga 25 MB. Di cPanel → *Select PHP Version* → *Options* (atau *MultiPHP INI Editor*), set `upload_max_filesize = 32M` dan `post_max_size = 64M`.
* **HTTPS.** Aktifkan AutoSSL/Let's Encrypt di cPanel → *SSL/TLS Status*, lalu pastikan `APP_URL` di `.env` memakai `https://`.

## Instalasi lewat Terminal (bila tersedia SSH/Terminal cPanel)

```bash
cd ~/izinara
cp .env.example .env
php artisan key:generate
# edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL
php artisan izinara:install --demo      # tanpa --demo untuk produksi
```

## Memperbarui versi

1. Cadangkan database (cPanel → *phpMyAdmin* → Export) dan folder `storage/app/private` (lampiran).
2. Unggah ZIP versi baru dan ekstrak menimpa file lama — **jangan hapus** `.env` dan `storage/`.
3. Jalankan migrasi: lewat Terminal `php artisan migrate --force`.

## Pemecahan masalah

| Gejala | Solusi |
|---|---|
| Error 500 / halaman putih | Pastikan PHP ≥ 8.2. Lihat `storage/logs/laravel.log`. Folder `storage/` dan `bootstrap/cache/` harus bisa ditulis (izin 755/775). |
| "Tidak bisa terhubung ke database" | Gunakan nama lengkap berawalan akun cPanel (`akuncpanel_izinara`) dan pastikan pengguna sudah ditambahkan ke database. |
| CSS tidak tampil / URL mengandung `/public` | Gunakan Cara A (document root ke `public`), atau periksa `.htaccess` di akar sudah terunggah (file tersembunyi—aktifkan *Show Hidden Files*). |
| Lampiran gagal diunggah | Naikkan `upload_max_filesize` dan `post_max_size`. |
| Ingin mengulang instalasi | Hapus file `storage/app/installed`, kosongkan database, buka lagi `/install`. |
