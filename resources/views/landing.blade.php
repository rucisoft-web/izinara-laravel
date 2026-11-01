@extends('layouts.public')
@section('title', 'Manajemen proyek perizinan pangan')
@section('content')
@php $shot = fn ($f) => file_exists(public_path('img/bantuan/'.$f)) ? asset('img/bantuan/'.$f) : null; @endphp

<section class="hero">
  <div class="hero-in">
    <div class="hero-txt">
      <span class="eyebrow">Perizinan impor & izin edar pangan</span>
      <h1>Kelola setiap izin sebagai proyek — dari kebutuhan sampai izin terbit.</h1>
      <p>Izinara menyusun WBS, jadwal Gantt, PIC, dokumen wajib, dan <b>rujukan peraturan perundang-undangan terbaru</b> untuk setiap kegiatan perizinan: rekomendasi Kementan, PI Kemendag, SKI & izin edar BPOM, karantina, PIB, hingga sertifikasi halal BPJPH.</p>
      <div class="hero-cta">
        <a class="btn lg" href="{{ route('login') }}">Coba demo</a>
        <a class="btn ghost lg" href="#peraturan">Lihat rujukan peraturan</a>
      </div>
      <div class="hero-stats">
        <div><b>{{ $regCount }}</b><span>peraturan dirujuk</span></div>
        <div><b>{{ $permitTypes->count() }}</b><span>jenis izin & langkah standar</span></div>
        <div><b>{{ $groups->count() }}</b><span>kelompok izin produk</span></div>
      </div>
    </div>
    @if($s = $shot('dashboard.png'))<div class="hero-img"><img src="{{ $s }}" alt="Dashboard Izinara"></div>@endif
  </div>
</section>

<section class="pub-sec">
  <div class="pub-wrap">
    <h2 class="sec-h">Masalah yang diselesaikan</h2>
    <div class="grid g3">
      <div class="feat"><div class="ic">⏱</div><h3>Izin terlambat, barang tertahan</h3><p>Rekomendasi, PI, dan SKI saling bergantung. Satu dokumen terlambat membuat kontainer menunggu di pelabuhan. Izinara menghitung jalur kritis dan memperingatkan sebelum tenggat terlewati.</p></div>
      <div class="feat"><div class="ic">§</div><h3>Dasar hukum tercecer</h3><p>Peraturan sering berganti. Setiap kegiatan di Izinara menampilkan pasal dan peraturan yang berlaku, dan pergantian peraturan dapat dialihkan ke semua kegiatan aktif sekaligus.</p></div>
      <div class="feat"><div class="ic">💬</div><h3>Koordinasi lewat chat pribadi</h3><p>Purchasing, Impor, QA, Perizinan, dan Halal berdiskusi langsung di kegiatan atau dokumen, dengan @mention dan memo bertenggat — semua tercatat sebagai jejak audit.</p></div>
    </div>
  </div>
</section>

<section class="pub-sec alt" id="alur">
  <div class="pub-wrap">
    <h2 class="sec-h">Alur kerja: kebutuhan → izin terbit</h2>
    <ol class="flow">
      <li><b>Kebutuhan</b><span>PO impor, produk baru, izin habis, atau sertifikasi halal dicatat sebagai proyek.</span></li>
      <li><b>WBS otomatis</b><span>Salin dari template WBS, susun dari item code, atau salin proyek lain — tanpa mengetik ulang.</span></li>
      <li><b>Jadwal & PIC</b><span>CPM menghitung tanggal, slack, jalur kritis; PIC per bagian ditugaskan; baseline disimpan.</span></li>
      <li><b>Dokumen & verifikasi</b><span>Lampiran PDF/Excel/Word/foto, dokumen wajib diajukan dan diverifikasi.</span></li>
      <li><b>Izin terbit</b><span>Nomor dan masa berlaku dicatat ke register, milestone tercapai.</span></li>
      <li><b>Perpanjangan</b><span>Register memicu proyek perpanjangan sebelum izin habis.</span></li>
    </ol>
  </div>
</section>

<section class="pub-sec" id="fitur">
  <div class="pub-wrap">
    <h2 class="sec-h">Fitur utama</h2>
    @php
      $features = [
        ['gantt-proyek.png', 'Gantt chart & jalur kritis', 'Dependensi antar izin, milestone (rekomendasi teknis → PI & izin edar → semua izin terbit), baseline vs rencana vs realisasi. Manajer cukup menggeser batang, jadwal penerus ikut dihitung ulang.'],
        ['proyek-baru-sumber.png', 'Copy from template', 'Proyek sejenis cukup disalin dari Template WBS: paket, kegiatan, durasi, dependensi, dokumen wajib, dan peraturan ikut tersalin. PIC ditugaskan otomatis per bagian.'],
        ['kegiatan-peraturan.png', 'Rujukan peraturan per kegiatan', 'Setiap kegiatan menampilkan undang-undang, PP, dan peraturan menteri/badan yang mendasarinya, lengkap dengan pasal dan status keberlakuannya.'],
        ['lampiran.png', 'Lampiran dokumen digital', 'PDF, Excel, Word, JPG, ZIP, email, dan lainnya hingga 25 MB per file, langsung di kegiatan atau dokumen wajib. PDF dan gambar bisa dipratinjau.'],
        ['diskusi.png', 'Komunikasi antar bagian', '@mention orang atau seluruh bagian, komentar pada dokumen ("CoA belum lengkap"), komentar perlu tindakan, dan memo bertenggat dengan statistik waktu respons.'],
        ['beban-kerja.png', 'Beban kerja PIC', 'Hari-orang per minggu per bagian dan per orang dibanding kapasitas. Kelebihan beban terlihat sebelum terjadi.'],
        ['proyeksi.png', 'Proyeksi & skenario', 'Perkiraan selesai P50/P80 dari histori realisasi, izin terbit dan habis per bulan, dan simulasi tambahan proyek atau PIC.'],
        ['proyek-ringkasan.png', 'Sertifikasi halal', 'Proyek registrasi SHLN dan sertifikasi halal BPJPH dengan rujukan UU JPH, PP 42/2024, dan ketentuan BPJPH.'],
      ];
    @endphp
    <div class="feat-list">
      @foreach($features as [$img, $title, $desc])
      <div class="feat-row">
        <div class="feat-txt"><h3>{{ $title }}</h3><p>{{ $desc }}</p></div>
        @if($s = $shot($img))<a class="feat-img" href="{{ $s }}" target="_blank" rel="noopener"><img src="{{ $s }}" alt="{{ $title }}" loading="lazy"></a>@endif
      </div>
      @endforeach
    </div>
  </div>
</section>

<section class="pub-sec alt" id="peraturan">
  <div class="pub-wrap">
    <h2 class="sec-h">Peraturan perundang-undangan di Izinara</h2>
    <p class="sec-lead">Perizinan pangan impor diatur berlapis oleh banyak instansi. Izinara menyimpan pustaka peraturan dan memasangkannya ke setiap izin, langkah, dan kegiatan — sehingga PIC selalu tahu <i>mengapa</i> sebuah dokumen diminta dan <i>pasal mana</i> yang menjadi dasarnya.</p>

    <div class="grid g3 reg-explain">
      <div class="feat"><div class="ic">①</div><h3>Tiga tingkat rujukan</h3><p><b>Jenis izin</b> memuat dasar hukum izin itu; <b>langkah standar</b> memuat pasal yang mengatur langkah tersebut; <b>kegiatan proyek</b> mewarisi keduanya dan dapat ditambah rujukan khusus. Di halaman kegiatan, semuanya tampil sebagai satu daftar.</p></div>
      <div class="feat"><div class="ic">②</div><h3>Status keberlakuan</h3><p>{!! badge('berlaku', 'Berlaku') !!} {!! badge('berlaku_diubah', 'Berlaku dengan perubahan') !!} {!! badge('perlu_konfirmasi', 'Perlu konfirmasi') !!} {!! badge('dicabut', 'Dicabut/diganti') !!}. Peraturan yang perlu konfirmasi diberi tanda kuning di kegiatan agar dicek ke JDIH sebelum dipakai.</p></div>
      <div class="feat"><div class="ic">③</div><h3>Peraturan baru? Satu klik</h3><p>Saat peraturan dicabut, tandai "Diganti oleh" dan centang <i>ganti referensi</i>. Semua jenis izin, langkah, dan kegiatan yang masih terbuka beralih ke peraturan pengganti; proyek selesai tetap menyimpan rujukan lama sebagai jejak audit.</p></div>
    </div>

    <div class="card reg-table">
      <div class="card-h"><h3>Pustaka peraturan bawaan</h3><div class="sp"></div>@if($checkedAt)<small>Status diperiksa per {{ tgl($checkedAt) }}</small>@endif</div>
      @foreach($regs as $lvl => $list)
        <div class="reg-lvl">{{ \App\Http\Controllers\HelpController::LEVELS[$lvl] }}</div>
        <table class="tbl small-table">
          <tbody>
          @foreach($list as $r)
            <tr>
              <td style="width:210px"><b>{{ $r->name }}</b></td>
              <td>{{ $r->title }}@if($r->note)<br><small>{{ $r->note }}</small>@endif</td>
              <td style="width:170px"><small>{{ $r->issuer }}</small></td>
              <td style="width:190px">{!! badge($r->status, $r->statusLabel()) !!}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endforeach
      <p class="help">Peraturan dapat ditambah, diperbarui statusnya, dan diberi tautan JDIH oleh admin di menu <b>§ Peraturan</b>. Daftar di atas adalah data awal; tetap lakukan pengecekan berkala ke sumber resmi.</p>
    </div>

    <div class="card">
      <div class="card-h"><h3>Dasar hukum per jenis izin</h3></div>
      <div class="scroll-x">
      <table class="tbl small-table">
        <thead><tr><th>Jenis izin</th><th>Instansi / sistem</th><th>Peraturan yang dirujuk</th></tr></thead>
        <tbody>
        @foreach($permitTypes as $pt)
          <tr><td><b>{{ $pt->name }}</b></td><td><small>{{ $pt->authority }}{{ $pt->system ? ' · '.$pt->system : '' }}</small></td>
            <td>@foreach($pt->regulations as $r)<span class="tag" title="{{ $r->title }}">{{ $r->name }}</span> @endforeach</td></tr>
        @endforeach
        </tbody>
      </table>
      </div>
    </div>

    @if($s = $shot('kegiatan-peraturan.png'))
    <figure class="help-shot wide"><img src="{{ $s }}" alt="Rujukan peraturan pada kegiatan" loading="lazy"><figcaption>Contoh: rujukan peraturan pada kegiatan pengajuan rekomendasi pemasukan, lengkap dengan pasal dan status.</figcaption></figure>
    @endif
  </div>
</section>

<section class="pub-sec">
  <div class="pub-wrap">
    <h2 class="sec-h">Kelompok izin yang didukung</h2>
    <div class="grid g3">
      @foreach($groups as $g)
        <div class="feat small"><h3>{{ $g->name }}</h3><p>{{ $g->groupLabel() }}</p></div>
      @endforeach
    </div>
  </div>
</section>

@if($hasDemo)
<section class="pub-sec alt" id="demo">
  <div class="pub-wrap">
    <h2 class="sec-h">Coba dengan data demo</h2>
    <p class="sec-lead">Data demo berisi {{ $projectCount }} proyek perizinan PT Indoguna Utama: tiga proyek untuk setiap kelompok izin (satu selesai, satu berjalan atau tertahan, satu rencana yang disalin dari template), lengkap dengan lampiran contoh, diskusi, memo, dan register izin. Kata sandi semua akun demo: <code>izinara</code>.</p>
    <div class="grid g4 demo-acc">
      <div class="feat small"><h3>sari.manajer</h3><p>Manajer perizinan: membuat proyek, menjadwal, verifikasi.</p></div>
      <div class="feat small"><h3>dewi.izin</h3><p>PIC Perizinan: rekomendasi Kementan & PI Kemendag.</p></div>
      <div class="feat small"><h3>hasan.halal</h3><p>PIC Halal: SHLN & sertifikasi halal BPJPH.</p></div>
      <div class="feat small"><h3>tono.direksi</h3><p>Direksi (hanya melihat): dashboard & proyeksi.</p></div>
    </div>
    <p style="margin-top:16px"><a class="btn lg" href="{{ route('login') }}">Masuk ke demo</a> <a class="btn ghost lg" href="{{ route('help.index') }}">Baca panduan</a></p>
  </div>
</section>
@endif

<section class="pub-sec">
  <div class="pub-wrap">
    <h2 class="sec-h">Mudah dipasang</h2>
    <div class="grid g3">
      <div class="feat"><div class="ic">⬆</div><h3>Upload ke cPanel</h3><p>Unggah ZIP, buat database MySQL, isi wizard instalasi. Tidak perlu Node.js atau build step.</p></div>
      <div class="feat"><div class="ic">🗄</div><h3>Laravel + MySQL</h3><p>PHP 8.2+, MySQL 5.7+/MariaDB 10.3+. Data dan lampiran sepenuhnya di server Anda.</p></div>
      <div class="feat"><div class="ic">？</div><h3>Bantuan di setiap layar</h3><p>Tombol Bantuan menjelaskan konsep dan cara mengisi data, lengkap dengan tangkapan layar.</p></div>
    </div>
  </div>
</section>
@endsection
