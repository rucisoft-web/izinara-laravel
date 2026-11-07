@extends('layouts.public')
@section('title', 'Informasi aplikasi')
@section('content')
@php $shot = fn ($f) => file_exists(public_path('img/bantuan/'.$f)) ? asset('img/bantuan/'.$f) : null; @endphp

<section class="hero">
  <div class="hero-in">
    <div class="hero-txt">
      <span class="eyebrow">Aplikasi internal</span>
      <h1>Izinara: pengurusan izin impor & izin edar pangan</h1>
      <p>Tempat semua bagian (Perizinan, Impor, Purchasing, QA/Pangan, Halal) mencatat dan memantau pengurusan izin sebagai proyek: dari kebutuhan muncul sampai izin terbit. Setiap kegiatan dilengkapi rujukan peraturan perundang-undangan yang berlaku, dokumen wajib, lampiran, dan diskusi antarbagian.</p>
      <div class="hero-cta">
        <a class="btn lg" href="{{ route('login') }}">Masuk</a>
        <a class="btn ghost lg" href="{{ route('help.index') }}">Panduan pengguna</a>
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
    <h2 class="sec-h">Untuk apa Izinara dipakai</h2>
    <div class="grid g3">
      <div class="feat"><div class="ic">▤</div><h3>Memantau jadwal izin</h3><p>Rekomendasi, PI, SKI, karantina, dan PIB saling bergantung. Jadwal dan jalur kritis menunjukkan kegiatan mana yang menentukan kapan semua izin terbit.</p></div>
      <div class="feat"><div class="ic">§</div><h3>Mengetahui dasar hukum</h3><p>Setiap kegiatan menampilkan peraturan dan pasal yang mendasarinya, beserta status keberlakuannya.</p></div>
      <div class="feat"><div class="ic">💬</div><h3>Koordinasi antarbagian</h3><p>Diskusi, @mention, komentar pada dokumen, dan memo bertenggat tercatat langsung di kegiatan terkait.</p></div>
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
    <h2 class="sec-h">Isi aplikasi</h2>
    <p class="sec-lead">Ringkasan layar utama. Penjelasan lengkap dan cara mengisi data ada di <a href="{{ route('help.index') }}">Panduan</a> dan tombol <b>？ Bantuan</b> di setiap layar.</p>
    @php
      $features = [
        ['gantt-proyek.png', 'Gantt chart & jalur kritis', 'Dependensi antar izin, milestone (rekomendasi teknis → PI & izin edar → semua izin terbit), baseline vs rencana vs realisasi. Manajer dapat menggeser batang; jadwal penerus ikut dihitung ulang.'],
        ['proyek-baru-sumber.png', 'Salin dari template', 'Proyek sejenis cukup disalin dari Template WBS: paket, kegiatan, durasi, dependensi, dokumen wajib, dan peraturan ikut tersalin. PIC ditugaskan otomatis per bagian.'],
        ['kegiatan-peraturan.png', 'Rujukan peraturan per kegiatan', 'Setiap kegiatan menampilkan undang-undang, PP, dan peraturan menteri/badan yang mendasarinya, lengkap dengan pasal dan status keberlakuannya.'],
        ['lampiran.png', 'Lampiran dokumen digital', 'PDF, Excel, Word, JPG, ZIP, email, dan lainnya hingga 25 MB per file, langsung di kegiatan atau dokumen wajib. PDF dan gambar bisa dipratinjau.'],
        ['diskusi.png', 'Komunikasi antar bagian', '@mention orang atau seluruh bagian, komentar pada dokumen ("CoA belum lengkap"), komentar perlu tindakan, dan memo bertenggat dengan statistik waktu respons.'],
        ['beban-kerja.png', 'Beban kerja PIC', 'Hari-orang per minggu per bagian dan per orang dibanding kapasitas. Membantu membagi pekerjaan antar-PIC.'],
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
    <p class="sec-lead">Perizinan pangan impor diatur berlapis oleh banyak instansi. Izinara menyimpan pustaka peraturan dan memasangkannya ke setiap izin, langkah, dan kegiatan, sehingga PIC tahu mengapa sebuah dokumen diminta dan pasal mana yang menjadi dasarnya.</p>

    <div class="grid g3 reg-explain">
      <div class="feat"><div class="ic">①</div><h3>Tiga tingkat rujukan</h3><p><b>Jenis izin</b> memuat dasar hukum izin itu; <b>langkah standar</b> memuat pasal yang mengatur langkah tersebut; <b>kegiatan proyek</b> mewarisi keduanya dan dapat ditambah rujukan khusus. Di halaman kegiatan, semuanya tampil sebagai satu daftar.</p></div>
      <div class="feat"><div class="ic">②</div><h3>Status keberlakuan</h3><p>{!! badge('berlaku', 'Berlaku') !!} {!! badge('berlaku_diubah', 'Berlaku dengan perubahan') !!} {!! badge('perlu_konfirmasi', 'Perlu konfirmasi') !!} {!! badge('dicabut', 'Dicabut/diganti') !!}. Peraturan yang perlu konfirmasi diberi tanda kuning di kegiatan agar dicek ke JDIH sebelum dipakai.</p></div>
      <div class="feat"><div class="ic">③</div><h3>Bila peraturan berganti</h3><p>Saat peraturan dicabut, tandai "Diganti oleh" dan centang <i>ganti referensi</i>. Semua jenis izin, langkah, dan kegiatan yang masih terbuka beralih ke peraturan pengganti; proyek selesai tetap menyimpan rujukan lama sebagai jejak audit.</p></div>
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
    <h2 class="sec-h">Kelompok izin</h2>
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
    <h2 class="sec-h">Akun latihan</h2>
    <p class="sec-lead">Untuk latihan, tersedia data contoh {{ $projectCount }} proyek perizinan: tiga proyek per kelompok izin (selesai, berjalan/tertahan, dan rencana yang disalin dari template), lengkap dengan lampiran contoh, diskusi, memo, dan register izin. Kata sandi akun latihan: <code>izinara</code>.</p>
    <div class="grid g4 demo-acc">
      <div class="feat small"><h3>sari.manajer</h3><p>Manajer perizinan: membuat proyek, menjadwal, verifikasi.</p></div>
      <div class="feat small"><h3>dewi.izin</h3><p>PIC Perizinan: rekomendasi Kementan & PI Kemendag.</p></div>
      <div class="feat small"><h3>hasan.halal</h3><p>PIC Halal: SHLN & sertifikasi halal BPJPH.</p></div>
      <div class="feat small"><h3>tono.direksi</h3><p>Direksi (hanya melihat): dashboard & proyeksi.</p></div>
    </div>
    <p style="margin-top:16px"><a class="btn lg" href="{{ route('login') }}">Masuk</a> <a class="btn ghost lg" href="{{ route('help.index') }}">Panduan pengguna</a></p>
  </div>
</section>
@endif

<section class="pub-sec">
  <div class="pub-wrap">
    <h2 class="sec-h">Butuh bantuan?</h2>
    <div class="grid g3">
      <div class="feat"><div class="ic">？</div><h3>Tombol Bantuan</h3><p>Ada di kanan atas setiap layar: konsep, cara mengisi data, dan contoh tampilan layar.</p></div>
      <div class="feat"><div class="ic">▤</div><h3>Panduan pengguna</h3><p>Semua topik dalam satu halaman: <a href="{{ route('help.index') }}">buka panduan</a>.</p></div>
      <div class="feat"><div class="ic">☺</div><h3>Akun & hak akses</h3><p>Permintaan akun baru atau perubahan peran diajukan ke admin Izinara di bagian Perizinan.</p></div>
    </div>
  </div>
</section>
@endsection
