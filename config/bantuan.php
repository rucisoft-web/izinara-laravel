<?php

/*
|--------------------------------------------------------------------------
| Isi tombol "Bantuan" di setiap layar dan halaman /panduan
|--------------------------------------------------------------------------
| Setiap topik dipetakan ke satu atau beberapa nama route. Tombol Bantuan
| di kanan atas membuka topik yang cocok dengan layar yang sedang dibuka.
| Gambar disimpan di public/img/bantuan/ (dibuat ulang dengan
| `node tools/screenshot-bantuan.cjs` setelah data demo dimuat).
*/

return [
    'urutan' => [
        'Memulai' => ['konsep', 'dashboard', 'tugas-saya'],
        'Proyek perizinan' => ['proyek-daftar', 'proyek-baru', 'proyek-detail', 'gantt', 'kegiatan', 'lampiran'],
        'Peraturan & referensi' => ['peraturan', 'jenis-izin', 'kelompok-izin', 'template-wbs', 'item-pemasok'],
        'Komunikasi antar bagian' => ['diskusi', 'memo', 'kotak-masuk'],
        'Analisis & proyeksi' => ['beban-kerja', 'proyeksi', 'register-izin'],
        'Administrasi' => ['pengguna'],
    ],

    'topik' => [

        'konsep' => [
            'judul' => 'Konsep Izinara',
            'routes' => [],
            'ringkas' => 'Setiap kebutuhan izin dikelola sebagai proyek: dari kebutuhan muncul sampai semua izin terbit dan tercatat di register.',
            'konsep' => [
                'Izinara memperlakukan pengurusan izin impor dan izin edar seperti proyek. Kebutuhan (PO baru, produk baru, izin akan habis, atau sertifikasi halal) menjadi satu proyek dengan jadwal, PIC, milestone, dan tenggat.',
                'Proyek dipecah menjadi WBS (Work Breakdown Structure): paket "Kebutuhan izin" → paket per jenis izin (mis. Rekomendasi Kementan, PI Kemendag, SKI BPOM, Karantina) → kegiatan → milestone. Setiap kegiatan punya durasi, dependensi (pendahulu), PIC, dokumen wajib, lampiran, dan referensi peraturan.',
                'Jadwal dihitung otomatis dengan metode jalur kritis (CPM): Izinara mencari tanggal paling awal setiap kegiatan, slack (kelonggaran), dan kegiatan kritis yang bila terlambat akan memundurkan seluruh proyek. Baseline menyimpan rencana awal sebagai pembanding.',
                'Data proyek yang sudah selesai dipakai untuk proyeksi: rasio realisasi vs baseline per jenis izin menghasilkan perkiraan selesai P50/P80, beban kerja PIC per minggu, dan izin yang akan terbit atau habis per bulan.',
            ],
            'langkah' => [
                'Admin mengisi master: bagian, pengguna, peraturan, jenis izin & langkah standar, kelompok izin, item code, pemasok.',
                'Manajer membuat proyek baru — paling cepat dengan "Salin dari template WBS" sehingga WBS tidak perlu diketik ulang.',
                'Manajer menetapkan PIC per bagian, menyimpan baseline, lalu PIC mengerjakan kegiatan di "Tugas saya".',
                'PIC memperbarui status/progres, mengunggah lampiran, mengajukan dokumen untuk diverifikasi, dan berdiskusi dengan @mention.',
                'Saat izin terbit, PIC mencatatnya ke register izin. Register memicu proyek perpanjangan sebelum izin habis.',
            ],
            'gambar' => ['dashboard.png' => 'Dashboard: kesehatan semua proyek, kegiatan lewat jadwal, milestone dan izin yang segera habis.'],
        ],

        'dashboard' => [
            'judul' => 'Dashboard perizinan',
            'routes' => ['dashboard'],
            'ringkas' => 'Ringkasan kesehatan semua proyek, kegiatan bermasalah, milestone dekat, izin hampir habis, dan respons memo per bagian.',
            'konsep' => [
                'Titik warna di kolom pertama adalah kesehatan proyek: hijau sesuai rencana, kuning mulai bergeser atau ada kegiatan terhambat, merah melewati tenggat.',
                '"Proyeksi P50 / P80" adalah perkiraan tanggal selesai berdasarkan histori: P50 = 50% kemungkinan selesai pada/sebelum tanggal itu, P80 = 80%. Risiko "tinggi" berarti P50 sudah melewati tenggat.',
                'Grafik "Kegiatan terbuka per bagian" menunjukkan bagian mana yang sedang paling banyak memegang pekerjaan dan yang paling banyak lewat jadwal.',
            ],
            'langkah' => [
                'Klik kode proyek untuk membuka detailnya.',
                'Klik kegiatan di tabel "Kegiatan lewat jadwal & terhambat" untuk langsung menindaklanjuti.',
                'Gunakan tombol "Gantt portofolio" untuk melihat semua proyek dalam satu garis waktu.',
            ],
            'gambar' => ['dashboard.png' => 'Dashboard dengan KPI, tabel kesehatan proyek, dan panel samping.'],
        ],

        'tugas-saya' => [
            'judul' => 'Tugas saya',
            'routes' => ['tasks.mine'],
            'ringkas' => 'Daftar kegiatan yang ditugaskan kepada Anda, diurutkan menurut tenggat.',
            'konsep' => [
                'Kegiatan muncul di sini bila Anda menjadi PIC (assignee). Kegiatan jalur kritis diberi tanda khusus karena keterlambatannya langsung memundurkan proyek.',
            ],
            'langkah' => [
                'Buka kegiatan, baca "Petunjuk & hasil" dan peraturan yang dirujuk.',
                'Ubah status ke "Berjalan" saat mulai, isi progres %, unggah dokumen, lalu ubah ke "Selesai".',
                'Bila tertahan pihak lain, pilih status "Menunggu" atau "Terhambat" dan isi catatan hambatan — manajer akan melihatnya di dashboard.',
            ],
            'gambar' => ['tugas-saya.png' => 'Daftar tugas pribadi dengan tenggat, status, dan tanda jalur kritis.'],
        ],

        'proyek-daftar' => [
            'judul' => 'Daftar proyek perizinan',
            'routes' => ['projects.index'],
            'ringkas' => 'Semua proyek dengan status, progres, tenggat, dan kesehatannya.',
            'konsep' => [
                'Status proyek: Rencana (belum dimulai), Berjalan, Tertahan (menunggu keputusan/pihak luar), Selesai, Batal.',
                'Pemicu kebutuhan mencatat asal proyek: PO impor baru, produk baru, perpanjangan izin, perubahan regulasi, atau sertifikasi halal.',
            ],
            'langkah' => ['Gunakan filter status/kelompok di atas tabel.', 'Klik "＋ Proyek baru" (khusus manajer/admin) untuk membuat proyek.'],
            'gambar' => ['proyek-daftar.png' => 'Daftar proyek demo Indoguna lintas kelompok izin.'],
        ],

        'proyek-baru' => [
            'judul' => 'Membuat proyek baru & salin dari template',
            'routes' => ['projects.create', 'projects.edit'],
            'ringkas' => 'Isi kebutuhan, pilih kelompok izin, lalu pilih sumber WBS: salin dari template, susun otomatis, atau salin dari proyek lain.',
            'konsep' => [
                'Template WBS adalah struktur proyek yang pernah berhasil (paket, kegiatan, durasi, dependensi, milestone, dokumen wajib, peraturan). Dengan "Salin dari template WBS" Anda tidak perlu mengetik ulang WBS — cukup isi data kebutuhan dan tanggal.',
                '"Susun otomatis dari item code" membangun WBS dari langkah standar setiap jenis izin yang dibutuhkan item terpilih. Izin yang masih berlaku di register bisa dilewati (dipakai ulang).',
                'Bila ETA diisi, kegiatan karantina dan PIB otomatis tidak dijadwalkan sebelum barang tiba.',
            ],
            'langkah' => [
                'Isi Nama proyek, Pemicu kebutuhan, dan Uraian kebutuhan (mengapa izin dibutuhkan).',
                'Pilih Kelompok izin, Eksportir/unit usaha asal, No. PO, Tanggal mulai, ETA (bila pengapalan) dan Tenggat semua izin terbit.',
                'Pilih Prioritas dan Manajer proyek.',
                'Pada "Sumber WBS" pilih "Salin dari template WBS" lalu pilih templatenya. Alternatif: susun otomatis dari item code, atau salin dari proyek lain.',
                'Centang item code yang diimpor beserta perkiraan kg, lalu pilih PIC per bagian.',
                'Klik "Simpan & hitung jadwal". Izinara membuat WBS, menugaskan PIC sesuai bagian, menghitung jadwal CPM, dan menyimpan baseline.',
            ],
            'isian' => [
                'Tenggat semua izin terbit' => 'Tanggal paling lambat semua izin harus terbit, mis. 7 hari sebelum ETA. Proyek berwarna merah bila rencana melewati tanggal ini.',
                'ETA' => 'Perkiraan barang tiba. Dipakai sebagai batasan paling awal kegiatan karantina dan PIB.',
                'Lewati izin yang masih berlaku' => 'Bila dicentang, jenis izin yang masih aktif di register tidak dibuatkan paket lagi.',
            ],
            'gambar' => [
                'proyek-baru.png' => 'Formulir proyek baru.',
                'proyek-baru-sumber.png' => 'Pilihan sumber WBS: salin dari template, susun otomatis, atau salin dari proyek lain.',
            ],
        ],

        'proyek-detail' => [
            'judul' => 'Detail proyek',
            'routes' => ['projects.show'],
            'ringkas' => 'Satu proyek dalam tab: Ringkasan, Gantt, WBS, PIC, Dokumen, Diskusi, Peraturan, Riwayat.',
            'konsep' => [
                'Ringkasan menampilkan kebutuhan, item, paket izin, milestone, izin yang dipakai ulang dan izin yang terbit dari proyek ini.',
                'Baseline adalah foto rencana awal. Selisih "rencana selesai" terhadap baseline menunjukkan pergeseran jadwal.',
                '"↻ Jadwal ulang" menghitung ulang jadwal dari hari ini untuk kegiatan yang belum selesai. "⚑ Simpan baseline" menimpa baseline dengan rencana saat ini (lakukan hanya setelah rencana disetujui).',
                '"⧉ Simpan sebagai template" menyimpan struktur proyek ini agar proyek sejenis berikutnya cukup disalin. "⧉ Salin dari template" mengganti WBS proyek rencana dengan template.',
            ],
            'langkah' => [
                'Tab WBS: klik kegiatan untuk mengubah, atau tambah kegiatan baru dengan pendahulu dan durasi.',
                'Tab PIC: tetapkan PIC dan cadangan per bagian, lalu "Simpan PIC" — kegiatan bagian tersebut ikut ditugaskan.',
                'Tab Dokumen: lihat semua dokumen wajib dan lampiran di proyek.',
                'Tab Peraturan: daftar seluruh peraturan yang dirujuk proyek, beserta pasalnya.',
            ],
            'gambar' => [
                'proyek-ringkasan.png' => 'Tab Ringkasan proyek berjalan.',
                'proyek-wbs.png' => 'Tab WBS: hierarki paket → kegiatan → milestone, dengan slack dan jalur kritis.',
                'proyek-pic.png' => 'Tab PIC: PIC per bagian dan beban tim di proyek.',
            ],
        ],

        'gantt' => [
            'judul' => 'Gantt chart',
            'routes' => ['gantt.portfolio'],
            'ringkas' => 'Garis waktu kegiatan dengan dependensi, milestone, jalur kritis, dan baseline.',
            'konsep' => [
                'Batang merah = jalur kritis (slack 0). Belah ketupat = milestone. Panah = dependensi selesai→mulai.',
                'Gantt proyek ada di tab "Gantt" pada detail proyek. Gantt portofolio menampilkan semua proyek aktif dan milestone-nya.',
            ],
            'langkah' => [
                'Pilih tampilan Hari / Minggu / Bulan.',
                'Manajer dapat menggeser atau memperpanjang batang; jadwal penerus dihitung ulang otomatis.',
                'Arahkan kursor ke batang untuk melihat PIC, baseline, dan slack; klik "Buka" untuk masuk ke kegiatan.',
            ],
            'gambar' => [
                'gantt-proyek.png' => 'Gantt satu proyek dengan jalur kritis.',
                'gantt-portofolio.png' => 'Gantt portofolio semua proyek.',
            ],
        ],

        'kegiatan' => [
            'judul' => 'Kegiatan, dokumen wajib & referensi peraturan',
            'routes' => ['tasks.show'],
            'ringkas' => 'Halaman kerja PIC: petunjuk, peraturan yang dirujuk, dokumen wajib, lampiran, diskusi, dan pembaruan progres.',
            'konsep' => [
                'Setiap kegiatan mewarisi referensi peraturan dari langkah standar jenis izinnya (mis. Permentan 19/2025 untuk rekomendasi pemasukan). Referensi bisa ditambah atau dikurangi per kegiatan, lengkap dengan pasal.',
                'Dokumen wajib berstatus: Belum → Diajukan → Terverifikasi / Ditolak. Kegiatan tidak bisa ditandai selesai selama ada dokumen wajib yang belum terverifikasi.',
                'Kegiatan pengajuan (mis. submit di INSW/OSS) yang selesai bisa langsung dicatat sebagai izin terbit ke register.',
            ],
            'langkah' => [
                'Perbarui progres: pilih Status, isi Progres %, tanggal mulai/selesai aktual, dan catatan hambatan bila ada. Klik Simpan.',
                'Dokumen wajib: unggah file pada dokumen terkait, klik "Ajukan verifikasi". Verifikator klik "✓ Terverifikasi" atau "✕ Tolak" dengan alasan.',
                'Referensi peraturan: centang peraturan yang relevan, isi pasal, klik "Simpan peraturan".',
                'Izin terbit: isi nomor, tanggal terbit dan berlaku sampai, klik "Catat izin terbit".',
            ],
            'gambar' => [
                'kegiatan.png' => 'Halaman kegiatan.',
                'kegiatan-peraturan.png' => 'Referensi peraturan perundang-undangan pada kegiatan.',
                'kegiatan-dokumen.png' => 'Dokumen wajib dan status verifikasinya.',
            ],
        ],

        'lampiran' => [
            'judul' => 'Lampiran dokumen digital',
            'routes' => ['attachments.show'],
            'ringkas' => 'Setiap kegiatan dan dokumen wajib dapat diberi lampiran: PDF, Excel, Word, gambar, ZIP, dan lainnya.',
            'konsep' => [
                'Jenis yang diterima: pdf, doc, docx, xls, xlsx, csv, ppt, pptx, jpg, jpeg, png, gif, webp, tif, txt, rtf, odt, ods, zip, rar, 7z, msg, eml, xml. Maksimal 25 MB per file, hingga 20 file sekali unggah.',
                'File disimpan di folder storage aplikasi (tidak bisa diakses publik); hanya pengguna yang login dapat membuka atau mengunduh.',
                'PDF dan gambar dapat dipratinjau langsung di browser.',
            ],
            'langkah' => [
                'Buka kegiatan → "📎 Unggah file", atau seret file ke kotak unggah.',
                'Opsional: pilih dokumen wajib yang dilampiri, isi keterangan dan tanggal kedaluwarsa dokumen (mis. CoA atau sertifikat).',
                'Semua lampiran proyek bisa dilihat di tab "Dokumen" pada detail proyek.',
            ],
            'gambar' => ['lampiran.png' => 'Daftar lampiran pada kegiatan dengan ikon jenis file.'],
        ],

        'peraturan' => [
            'judul' => 'Peraturan perundang-undangan',
            'routes' => ['regulations.index'],
            'ringkas' => 'Pustaka peraturan yang menjadi dasar setiap jenis izin, langkah, dan kegiatan — dengan status keberlakuan dan riwayat penggantian.',
            'konsep' => [
                'Hierarki rujukan: Undang-Undang (mis. UU 18/2012 Pangan, UU 33/2014 Jaminan Produk Halal jo. UU 6/2023 Cipta Kerja) → Peraturan Pemerintah (mis. PP 28/2025 Perizinan Berusaha Berbasis Risiko, PP 42/2024 Penyelenggaraan JPH) → Peraturan Menteri/Kepala Badan (mis. Permendag, Permentan, Per BPOM, PMK) → keputusan teknis.',
                'Referensi dipasang di tiga tingkat: jenis izin (dasar hukum izin), langkah standar (pasal yang mengatur langkah itu), dan kegiatan proyek (bisa ditambah khusus). Kegiatan menampilkan gabungan ketiganya.',
                'Status: Berlaku; Berlaku dengan perubahan (dibaca bersama perubahannya, "jo."); Perlu konfirmasi versi terbaru (periksa ke JDIH instansi sebelum dipakai); Dicabut/diganti (otomatis diarahkan ke peraturan pengganti).',
                'Kolom "Tanggal dicek" mencatat kapan status terakhir diperiksa. Data awal diperiksa per 1 November 2026; tetap lakukan pengecekan berkala karena regulasi sering berubah.',
            ],
            'langkah' => [
                'Peraturan baru terbit: klik tambah, isi Kode unik, Nomor, Tentang, Penerbit, Tahun, Status, Tautan JDIH, dan Tanggal dicek.',
                'Peraturan lama dicabut: buka peraturan lama, ubah Status menjadi "Dicabut / diganti", pilih "Diganti oleh", lalu centang "Ganti referensi di kegiatan terbuka, langkah & jenis izin ke peraturan pengganti". Semua rujukan aktif pindah otomatis; proyek yang sudah selesai tetap menyimpan rujukan lama sebagai jejak audit.',
                'Lihat kolom Jenis izin / Langkah / Kegiatan untuk mengetahui dampak perubahan sebuah peraturan.',
            ],
            'isian' => [
                'Kode unik' => 'Kode singkat tanpa spasi, mis. PERMENTAN_19_2025.',
                'Nomor' => 'Penulisan singkat, mis. "Permentan 19/2025".',
                'Tautan' => 'Alamat dokumen resmi di JDIH/peraturan.go.id.',
            ],
            'gambar' => [
                'peraturan.png' => 'Pustaka peraturan dengan status dan jumlah rujukan.',
                'proyek-peraturan.png' => 'Tab Peraturan di proyek: semua peraturan yang dirujuk beserta pasal.',
            ],
        ],

        'jenis-izin' => [
            'judul' => 'Jenis izin & langkah standar',
            'routes' => ['permit-types.index', 'permit-types.show'],
            'ringkas' => 'Katalog izin (mis. Rekomendasi Pemasukan Kementan, PI Kemendag, SKI BPOM, SHLN BPJPH) beserta langkah, durasi standar, dokumen wajib, dan dasar hukumnya.',
            'konsep' => [
                'Langkah standar menjadi bahan WBS otomatis. Ubah durasi atau pasal di sini, maka proyek baru akan memakai nilai terbaru (proyek lama tidak berubah).',
                'Prasyarat antar izin (mis. PI menunggu rekomendasi teknis) menentukan dependensi antarpaket.',
            ],
            'langkah' => ['Klik jenis izin untuk melihat langkah.', 'Ubah durasi, bagian pelaksana, instruksi, dan peraturan per langkah, lalu simpan.'],
            'gambar' => ['jenis-izin.png' => 'Detail jenis izin dengan langkah dan peraturan.'],
        ],

        'kelompok-izin' => [
            'judul' => 'Kelompok izin',
            'routes' => ['templates.index'],
            'ringkas' => 'Kelompok produk (daging, hasil perikanan, hortikultura, olahan ML/MD, halal) dan jenis izin yang wajib, kondisional, atau opsional untuk tiap kelompok.',
            'konsep' => ['Kelompok izin menentukan paket izin apa saja yang dibuat saat proyek disusun otomatis.'],
            'langkah' => ['Pilih kelompok untuk melihat daftar jenis izin dan sifatnya (W = wajib, K = kondisional, O = opsional).'],
            'gambar' => ['kelompok-izin.png' => 'Kelompok izin dan jenis izin di dalamnya.'],
        ],

        'template-wbs' => [
            'judul' => 'Template WBS',
            'routes' => ['wbs-templates.index', 'wbs-templates.show'],
            'ringkas' => 'Struktur proyek siap pakai yang bisa disalin ke proyek baru, sehingga WBS tidak perlu diketik ulang.',
            'konsep' => [
                'Template dibuat dari proyek yang sudah berjalan baik (tombol "⧉ Simpan sebagai template" di detail proyek). Isinya: paket, kegiatan, durasi, dependensi, milestone, dokumen wajib, dan referensi peraturan.',
                'Kolom "Dipakai" menghitung berapa kali template disalin — template yang sering dipakai menjadi standar kerja.',
            ],
            'langkah' => [
                'Klik "⧉ Pakai untuk proyek baru" pada template, atau pilih template di formulir proyek baru.',
                'Untuk proyek rencana yang sudah ada, buka detail proyek → "⧉ Salin dari template".',
            ],
            'gambar' => ['template-wbs.png' => 'Daftar template WBS hasil proyek demo.', 'template-wbs-detail.png' => 'Isi satu template WBS.'],
        ],

        'item-pemasok' => [
            'judul' => 'Item code & pemasok',
            'routes' => ['items.index', 'suppliers.index'],
            'ringkas' => 'Master produk yang diimpor/dijual dan eksportir/unit usaha asalnya.',
            'konsep' => ['Item code menentukan kelompok izin dan HS code. Pemasok mencatat negara asal dan nomor registrasi unit usaha (mis. Est. number) yang dibutuhkan rekomendasi.'],
            'langkah' => ['Impor item lewat CSV dengan kolom: ITEMCODE; ITEMNAME; Country; Category; Group; SubGroup; HS; Non-halal; Jenis produk; Kode kelompok izin. Atau klik muat item master bawaan Indoguna.', 'Tambah pemasok dengan negara dan nomor registrasinya.'],
            'gambar' => ['item-code.png' => 'Master item code.'],
        ],

        'diskusi' => [
            'judul' => 'Diskusi & @mention antar bagian',
            'routes' => [],
            'ringkas' => 'Komentar langsung pada proyek, kegiatan, atau dokumen. Ketik @ untuk menarik perhatian orang atau seluruh bagian.',
            'konsep' => [
                'Ketik @nama.pengguna untuk satu orang, atau @KODEBAGIAN (mis. @QA, @IMPOR) untuk semua anggota bagian. Yang di-mention menerima notifikasi di Kotak masuk.',
                'Jenis komentar: Komentar biasa, Perlu tindakan (muncul sebagai tugas terbuka sampai ditandai selesai), dan Info.',
                'Komentar pada dokumen wajib dipakai untuk menyampaikan kekurangan, mis. "CoA belum mencantumkan nomor lot @QA".',
            ],
            'langkah' => [
                'Buka tab Diskusi pada proyek, atau bagian Diskusi pada kegiatan/dokumen.',
                'Tulis komentar, ketik @ lalu pilih nama dari daftar yang muncul.',
                'Pilih jenis komentar dan kirim. Penerima "Perlu tindakan" menekan "Selesai" setelah menindaklanjuti.',
            ],
            'gambar' => ['diskusi.png' => 'Diskusi dengan @mention dan komentar perlu tindakan.'],
        ],

        'memo' => [
            'judul' => 'Memo antar bagian',
            'routes' => ['memos.index', 'memos.show'],
            'ringkas' => 'Permintaan resmi antarbagian dengan tenggat, prioritas, dan pelacakan waktu respons.',
            'konsep' => [
                'Memo cocok untuk permintaan yang butuh jawaban terukur, mis. Purchasing meminta Impor menyiapkan dokumen, atau Perizinan meminta QA menerbitkan CoA.',
                'Izinara mencatat waktu respons pertama dan ketepatan waktu, ditampilkan di dashboard "Respons memo per bagian".',
            ],
            'langkah' => [
                'Klik "＋ Memo", pilih bagian (dan orang) tujuan, subjek, isi, prioritas, tenggat, serta proyek/kegiatan terkait.',
                'Penerima membalas di halaman memo; centang "tutup memo" bila selesai.',
            ],
            'gambar' => ['memo.png' => 'Daftar memo masuk/keluar.', 'memo-detail.png' => 'Percakapan dalam satu memo.'],
        ],

        'kotak-masuk' => [
            'judul' => 'Kotak masuk (notifikasi)',
            'routes' => ['notices.index'],
            'ringkas' => 'Semua notifikasi: mention, komentar, memo, dokumen ditolak/terverifikasi, penugasan.',
            'konsep' => ['Angka merah di lonceng menunjukkan notifikasi yang belum dibaca. Klik notifikasi untuk langsung ke kegiatan, dokumen, atau memo terkait.'],
            'langkah' => ['Klik notifikasi untuk membuka.', 'Klik "Tandai semua dibaca" untuk membersihkan.'],
            'gambar' => ['kotak-masuk.png' => 'Kotak masuk notifikasi.'],
        ],

        'beban-kerja' => [
            'judul' => 'Perhitungan beban kerja PIC',
            'routes' => ['workload'],
            'ringkas' => 'Perkiraan hari-orang kerja per bagian dan per PIC per minggu, dibandingkan dengan kapasitas.',
            'konsep' => [
                'Beban dihitung dari kegiatan terjadwal: kegiatan aktif (menyusun, mengajukan) dihitung 0,5 hari kerja per hari kalender, kegiatan menunggu instansi (verifikasi, evaluasi) dihitung 0,2.',
                'Beban dibagi dengan kapasitas mingguan bagian/pengguna (hari-orang per minggu) menjadi % utilisasi. Di atas 100% berarti kelebihan beban: pertimbangkan menggeser jadwal atau menambah PIC.',
            ],
            'langkah' => ['Lihat grafik beban per bagian per minggu dan tabel utilisasi per PIC.', 'Perhatikan sel berwarna merah (di atas 100%) dan geser jadwal atau tambah PIC cadangan di tab PIC proyek.', 'Pilih rentang 8, 12 atau 26 minggu di kanan atas. Kapasitas (hari-orang per minggu) diatur di menu Pengguna & bagian.'],
            'gambar' => ['beban-kerja.png' => 'Beban kerja per bagian per minggu.'],
        ],

        'proyeksi' => [
            'judul' => 'Proyeksi & skenario',
            'routes' => ['forecast'],
            'ringkas' => 'Perkiraan selesai proyek, izin terbit/habis per bulan, dan simulasi skenario penambahan proyek atau PIC.',
            'konsep' => [
                'Faktor keterlambatan dihitung dari proyek selesai: rasio durasi realisasi terhadap baseline per jenis izin (P50 dan P80). Faktor ini dikalikan ke sisa kegiatan proyek berjalan.',
                'Proyeksi bulanan menggabungkan izin yang diperkirakan terbit, izin di register yang akan habis, perpanjangan yang harus dimulai, dan proyek baru yang direncanakan.',
                'Skenario: isi tambahan beban (%) dan tambahan PIC untuk melihat dampaknya pada utilisasi.',
            ],
            'langkah' => ['Lihat tabel faktor per jenis izin.', 'Isi parameter skenario lalu klik Hitung.'],
            'gambar' => ['proyeksi.png' => 'Proyeksi bulanan dan faktor keterlambatan per jenis izin.'],
        ],

        'register-izin' => [
            'judul' => 'Register izin',
            'routes' => ['licenses.index'],
            'ringkas' => 'Daftar izin yang dimiliki beserta masa berlaku; sumber pemicu proyek perpanjangan.',
            'konsep' => ['Izin tercatat otomatis saat kegiatan pengajuan selesai dan "Catat izin terbit" diisi, atau ditambahkan manual. Status warna: aktif, segera habis (≤ 90 hari), kedaluwarsa.'],
            'langkah' => ['Klik "Perpanjang" pada izin yang hampir habis — formulir proyek baru terisi otomatis.', 'Tambah izin lama secara manual lewat formulir di bawah tabel.'],
            'gambar' => ['register-izin.png' => 'Register izin dengan masa berlaku.'],
        ],

        'pengguna' => [
            'judul' => 'Pengguna & bagian',
            'routes' => ['users.index'],
            'ringkas' => 'Akun, peran, bagian, dan kapasitas kerja.',
            'konsep' => [
                'Peran: Admin (semua termasuk master data), Manajer (membuat/menjadwal proyek, verifikasi), PIC (mengerjakan kegiatan yang ditugaskan), Viewer (hanya melihat, mis. direksi).',
                'Bagian: Perizinan, Impor, QA, Purchasing, Halal, dll. Kode bagian dipakai untuk @mention seluruh bagian.',
            ],
            'langkah' => ['Tambah pengguna: nama, username, email, peran, bagian, jabatan, kapasitas hari-orang/minggu, kata sandi.', 'Ubah kapasitas bagian untuk perhitungan beban kerja.'],
            'gambar' => ['pengguna.png' => 'Pengguna dan bagian.'],
        ],
    ],
];
