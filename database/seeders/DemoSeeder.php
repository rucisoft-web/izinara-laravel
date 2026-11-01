<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Item;
use App\Models\License;
use App\Models\Party;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\ProjectPic;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Template;
use App\Models\User;
use App\Services\Communication;
use App\Services\Forecaster;
use App\Services\ProjectBuilder;
use App\Services\Scheduler;
use App\Services\WbsTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data demo kegiatan perizinan Indoguna Group.
 *
 * - 6 kelompok (PSAH, PSAI, PSAT, BPOM ML, BPOM MD, Sertifikasi Halal) × 3 proyek: selesai, berjalan/tertahan, rencana.
 * - Proyek selesai menjadi riwayat realisasi (dasar proyeksi) dan disimpan sebagai template WBS.
 * - Proyek rencana dibuat dengan "Salin dari template" sehingga fitur salin WBS terlihat.
 * - Lampiran contoh (PDF, Excel, Word, JPG), verifikasi lolos/ditolak, @mention, memo antar bagian, notifikasi.
 * - Hanya data Indoguna: pemasok luar negeri ditulis sebagai keterangan umum, bukan nama perusahaan.
 * Semua tanggal relatif terhadap hari seeding.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'izinara';

    private CarbonImmutable $today;
    private array $users = [];
    private array $suppliers = [];
    private array $projects = [];
    private array $wbs = [];
    private Communication $comm;

    /** Faktor realisasi simulasi per jenis izin [min, maks]. */
    private const SLIP = [
        'ML_BPOM' => [1.1, 1.6], 'MD_BPOM' => [1.0, 1.4], 'RP_KEMENTAN' => [1.0, 1.5], 'PI_KEMENDAG' => [1.0, 1.4],
        'SHLN' => [1.0, 1.35], 'RPHP_KKP' => [1.0, 1.3], 'RIPH' => [1.1, 1.5], 'PSAT' => [1.0, 1.4],
        'EST_APPROVAL' => [1.0, 1.2], 'HALAL_SH' => [1.0, 1.3],
    ];

    /** Item contoh dari item master Indoguna. */
    private const ITEMS = ['AAU1AMH005', 'AID2LOC055', 'ADK2DAN001', 'AAU1JBS050', 'AAU1MUL095', 'DNO1STL004', 'DNO1STL002',
        'DCN2ZHE001', 'DCN2KIN002', 'DNZ2HIF002', 'GFR3DAU003', 'GFR3DAU019', 'GCN2XJR011', 'EIT3CIA001', 'EIT3DEC028',
        'EIT3BAL001', 'EIT3DEC014', 'HAU2SAP002', 'BID2IGU013', 'BID2IGU054', 'BID2IGU034', 'AAU12GR001', 'AAU1AMH003',
        'AID2LOC043', 'HAU1WBO001', 'AAU2COM001'];

    public function run(): void
    {
        mt_srand(20261101);
        $this->today = CarbonImmutable::today();
        $this->comm = app(Communication::class);
        (new ItemMasterSeeder)->run(self::ITEMS);
        $this->users();
        $this->suppliers();
        $this->licenses();
        $this->projects();
        $this->communication();
    }

    private function users(): void
    {
        $p = Party::pluck('id', 'code');
        $rows = [
            ['sari.manajer', 'Sari Wijayanti', null, 'manajer', 'Manajer Perizinan & Regulasi', 3],
            ['rina.ml', 'Rina Wulandari', 'pangan', 'pic', 'Staf Registrasi BPOM', 5],
            ['maya.ml', 'Maya Kusuma', 'pangan', 'pic', 'Staf Registrasi BPOM', 5],
            ['hasan.halal', 'Hasan Basri', 'halal', 'pic', 'Koordinator Sertifikasi Halal', 5],
            ['nur.halal', 'Nur Aini', 'halal', 'pic', 'Staf Sistem Jaminan Produk Halal', 5],
            ['dewi.izin', 'Dewi Lestari', 'perizinan', 'pic', 'Staf Perizinan Kementan/KKP', 5],
            ['fajar.izin', 'Fajar Nugroho', 'perizinan', 'pic', 'Staf Karantina & Perizinan', 5],
            ['budi.purchasing', 'Budi Santoso', 'purchasing', 'pic', 'Import Purchasing', 5],
            ['andi.impor', 'Andi Pratama', 'impor', 'pic', 'Staf Impor & Kepabeanan', 5],
            ['tono.direksi', 'Tono Hartanto', null, 'viewer', 'Direktur Operasional', 1],
        ];
        foreach ($rows as [$login, $name, $party, $role, $title, $cap]) {
            $this->users[$login] = User::updateOrCreate(['username' => $login], [
                'name' => $name, 'email' => "$login@indoguna.test", 'password' => self::PASSWORD,
                'party_id' => $party ? $p[$party] : null, 'role' => $role, 'job_title' => $title, 'weekly_capacity' => $cap,
            ]);
        }
    }

    private function suppliers(): void
    {
        $rows = [
            'au' => ['Eksportir daging sapi – Australia', 'exporter', 'Australia', null],
            'au_est' => ['Unit usaha asal Est. 2243 – Australia', 'establishment', 'Australia', '2243'],
            'au2' => ['Eksportir daging domba & veal – Australia', 'exporter', 'Australia', null],
            'au2_est' => ['Unit usaha asal Est. 1855 – Australia', 'establishment', 'Australia', '1855'],
            'dk' => ['Eksportir daging babi – Denmark', 'exporter', 'Denmark', null],
            'dk_est' => ['Unit usaha asal Est. DK-1327 – Denmark', 'establishment', 'Denmark', 'DK-1327'],
            'no' => ['Eksportir salmon – Norwegia', 'exporter', 'Norwegia', null],
            'cn' => ['Eksportir seafood beku – Tiongkok', 'exporter', 'Tiongkok', null],
            'nz' => ['Eksportir kerang – Selandia Baru', 'exporter', 'Selandia Baru', null],
            'fr' => ['Eksportir sayuran beku – Prancis', 'exporter', 'Prancis', null],
            'cn2' => ['Eksportir buah beku – Tiongkok', 'exporter', 'Tiongkok', null],
            'it' => ['Prinsipal pangan olahan – Italia', 'exporter', 'Italia', null],
            'ar' => ['Eksportir produk susu – Argentina', 'exporter', 'Argentina', null],
            'igu' => ['Indoguna – unit pengolahan daging Cikarang', 'local', 'Indonesia', null],
        ];
        foreach ($rows as $k => [$name, $kind, $country, $est]) {
            $this->suppliers[$k] = Supplier::firstOrCreate(['name' => $name], ['kind' => $kind, 'country' => $country, 'establishment_no' => $est]);
        }
        foreach (['au_est' => 'au', 'au2_est' => 'au2', 'dk_est' => 'dk'] as $c => $parent) {
            $this->suppliers[$c]->update(['parent_id' => $this->suppliers[$parent]->id]);
        }
    }

    private function license(string $type, string $no, ?int $issued, ?int $expires, array $items = [], ?string $sup = null, ?float $quota = null): void
    {
        $lic = License::create([
            'permit_type_id' => PermitType::where('code', $type)->value('id'), 'number' => $no,
            'issued_at' => $issued !== null ? $this->today->addDays($issued) : null,
            'expires_at' => $expires !== null ? $this->today->addDays($expires) : null,
            'supplier_id' => $sup ? $this->suppliers[$sup]->id : null, 'quota_kg' => $quota,
        ]);
        if ($items) $lic->items()->sync(Item::whereIn('code', $items)->pluck('id'));
    }

    private function licenses(): void
    {
        $this->license('NIB_API', '9120001234567 (NIB/API-U Indoguna)', -1500, null);
        $this->license('NKV', 'NKV-CS-3216-0042 (cold storage Cikarang)', -700, 760);
        $this->license('EST_APPROVAL', 'Persetujuan unit usaha Est. DK-1327', -300, 65, [], 'dk_est');
        $this->license('SHLN', 'SHLN-AR-2025-0077', -280, 85, ['HAU2SAP002']);
        $this->license('SHLN', 'SHLN-AU-2025-0193', -330, 35, ['AAU1AMH005', 'AID2LOC055']);
        $this->license('RP_KEMENTAN', 'RP-PKH/2026/05/0331', -170, 12, ['AAU1AMH005', 'AID2LOC055'], null, 48000);
        $this->license('PI_KEMENDAG', 'PI-04.PI-05.26.0112', -160, 20, ['AAU1AMH005', 'AID2LOC055'], null, 48000);
        $this->license('HALAL_SH', 'ID00410000123450824', -420, 1040, []);
        $this->license('LOCAL_SUPPLIER', 'Verifikasi unit pengolahan Cikarang', -200, 165, [], 'igu');
    }

    private function projects(): void
    {
        // [kode, nama, pemicu, item=>kg, pemasok, mulai (hari), cadangan sebelum ETA (null = tanpa ETA), prioritas, opsi]
        $defs = [
            // PSAH – Pangan Segar Asal Hewan
            ['PRJ-2026-001', 'Impor ribeye & paru sapi chilled Australia – PO 4500-0412', 'pengapalan', ['AAU1AMH005' => 18000, 'AID2LOC055' => 6000], 'au_est', -170, 4, 'tinggi', ['wbs' => 'WBS standar PSAH – daging merah impor (halal)']],
            ['PRJ-2026-002', 'Impor pork belly Denmark – PO 4500-0471', 'pengapalan', ['ADK2DAN001' => 22000], 'dk_est', -45, 3, 'tinggi', []],
            ['PRJ-2026-003', 'Impor lamb leg & veal hindshank Australia – rencana Q1 2027', 'pengapalan', ['AAU1JBS050' => 9000, 'AAU1MUL095' => 4000], 'au2_est', 21, 7, 'normal', ['copy' => 'PRJ-2026-001']],
            // PSAI – Pangan Segar Asal Ikan
            ['PRJ-2026-004', 'Impor salmon segar Norwegia – PO 4500-0420', 'pengapalan', ['DNO1STL004' => 3500, 'DNO1STL002' => 2000], 'no', -140, 2, 'tinggi', ['wbs' => 'WBS standar PSAI – ikan & seafood impor']],
            ['PRJ-2026-005', 'Impor squid ring & tube Tiongkok – PO 4500-0476', 'pengapalan', ['DCN2ZHE001' => 12000, 'DCN2KIN002' => 6000], 'cn', -26, 1, 'kritis', ['block' => true]],
            ['PRJ-2026-006', 'Impor green mussel Selandia Baru – rencana Januari 2027', 'pengapalan', ['DNZ2HIF002' => 5000], 'nz', 40, 6, 'normal', ['copy' => 'PRJ-2026-004']],
            // PSAT – Pangan Segar Asal Tumbuhan
            ['PRJ-2026-007', 'Impor green peas & carrots beku Prancis – PO 4500-0402', 'pengapalan', ['GFR3DAU003' => 6000], 'fr', -150, 3, 'normal', ['wbs' => 'WBS standar PSAT – sayur & buah beku impor']],
            ['PRJ-2026-008', 'Impor sweet corn beku Prancis – PO 4500-0483', 'pengapalan', ['GFR3DAU019' => 8000], 'fr', -12, 4, 'normal', ['contingency' => 14]],
            ['PRJ-2026-009', 'Impor strawberry IQF Tiongkok – rencana Februari 2027', 'pengapalan', ['GCN2XJR011' => 5000], 'cn2', 55, 5, 'normal', ['copy' => 'PRJ-2026-007']],
            // BPOM ML – pangan olahan impor
            ['PRJ-2026-010', 'Produk baru: pasta & baked beans Italia (izin edar ML)', 'produk_baru', ['EIT3CIA001' => 4800, 'EIT3DEC028' => 6000], 'it', -230, 6, 'normal', ['wbs' => 'WBS standar BPOM ML – pangan olahan impor']],
            ['PRJ-2026-011', 'Impor butter Argentina – PO 4500-0480', 'pengapalan', ['HAU2SAP002' => 25000], 'ar', -36, 6, 'normal', ['contingency' => 9]],
            ['PRJ-2026-012', 'Produk baru: wafer & fusilli organik Italia', 'produk_baru', ['EIT3BAL001' => 3000, 'EIT3DEC014' => 4000], 'it', 30, 8, 'normal', ['copy' => 'PRJ-2026-010']],
            // BPOM MD – pangan olahan dalam negeri
            ['PRJ-2026-013', 'Registrasi MD beef salami Indoguna', 'produk_baru', ['BID2IGU013' => 0], 'igu', -160, null, 'normal', ['target' => 75, 'wbs' => 'WBS standar BPOM MD – pangan olahan Indoguna']],
            ['PRJ-2026-014', 'Registrasi MD sosis bockwurst Indoguna', 'produk_baru', ['BID2IGU054' => 0], 'igu', -22, null, 'normal', ['target' => 70, 'hold' => 'Hasil uji laboratorium (cemaran mikroba) belum keluar dari lab terakreditasi.']],
            ['PRJ-2026-015', 'Perpanjangan izin edar MD bakso super Indoguna', 'perpanjangan', ['BID2IGU034' => 0], 'igu', 6, null, 'tinggi', ['target' => 55, 'copy' => 'PRJ-2026-013']],
            // Sertifikasi halal
            ['PRJ-2026-016', 'Registrasi SHLN wagyu Australia', 'produk_baru', ['AAU12GR001' => 0], 'au', -120, null, 'tinggi', ['target' => 35, 'types' => ['SHLN'], 'halal' => true, 'wbs' => 'WBS standar Sertifikasi Halal – registrasi SHLN']],
            ['PRJ-2026-017', 'Sertifikasi halal BPJPH daging potong & kemas ulang Indoguna', 'produk_baru', ['AAU1AMH003' => 0], 'igu', -20, null, 'tinggi', ['target' => 50, 'types' => ['HALAL_SH'], 'halal' => true]],
            ['PRJ-2026-018', 'Perpanjangan SHLN butter Argentina', 'perpanjangan', ['HAU2SAP002' => 0], 'ar', 10, null, 'tinggi', ['target' => 30, 'halal' => true, 'copy' => 'PRJ-2026-016']],
        ];

        $builder = app(ProjectBuilder::class);
        $scheduler = app(Scheduler::class);
        $wbsSvc = app(WbsTemplateService::class);
        $parties = Party::pluck('id', 'code');
        $halalTpl = Template::where('code', 'T_HALAL')->value('id');
        $picPlan = [
            'pangan' => ['rina.ml', 'maya.ml'], 'halal' => ['hasan.halal', 'nur.halal'], 'perizinan' => ['dewi.izin', 'fajar.izin'],
            'purchasing' => ['budi.purchasing'], 'impor' => ['andi.impor'],
        ];

        foreach ($defs as $n => $d) {
            [$code, $name, $trigger, $items, $sup, $startOff, $buffer, $prio, $opt] = $d;
            $start = $this->today->addDays($startOff);
            $project = Project::create([
                'code' => $code, 'name' => $name, 'trigger' => $trigger, 'supplier_id' => $this->suppliers[$sup]->id,
                'manager_id' => $this->users['sari.manajer']->id, 'status' => 'rencana', 'priority' => $prio,
                'po_ref' => preg_match('/PO (\S+)/', $name, $m) ? $m[1] : null,
                'start_date' => $start, 'target_date' => $start->addDays($opt['target'] ?? 90),
                'need_description' => $this->needText($trigger, $name),
            ]);
            $project->items()->sync(Item::whereIn('code', array_keys($items))->get()
                ->mapWithKeys(fn ($i) => [$i->id => ['qty_kg' => $items[$i->code]]])->all());
            $project->update(['template_id' => ! empty($opt['halal']) ? $halalTpl : $project->items()->first()?->template_id]);
            foreach ($picPlan as $party => $logins) {
                ProjectPic::create(['project_id' => $project->id, 'party_id' => $parties[$party],
                    'user_id' => $this->users[$logins[$n % count($logins)]]->id,
                    'backup_user_id' => count($logins) > 1 ? $this->users[$logins[($n + 1) % count($logins)]]->id : null]);
            }
            $types = isset($opt['types']) ? PermitType::whereIn('code', $opt['types'])->get() : null;
            $make = function () use ($project, $opt, $types, $builder, $wbsSvc) {
                if (isset($opt['copy'])) {
                    $wbsSvc->applyTo($project->fresh(), $this->wbs[$opt['copy']], true);
                } else {
                    $builder->build($project->fresh(), $types);
                }
                return $project->fresh();
            };
            $project = $make();
            if ($buffer !== null) {
                // ETA = akhir izin pra-kedatangan + cadangan; karantina & PIB mengikuti ETA.
                $arrival = PermitType::whereIn('code', array_keys(ProjectBuilder::AT_ARRIVAL))->pluck('id');
                $ready = $project->tasks()->where('kind', 'task')->where(fn ($q) => $q->whereNull('permit_type_id')
                    ->orWhereNotIn('permit_type_id', $arrival))->max('planned_end');
                $eta = CarbonImmutable::parse($ready)->addDays($buffer);
                $project->update(['eta' => $eta, 'etd' => $eta->subDays(18)]);
                $project = $make();
            }
            // Tenggat = baseline + cadangan waktu (proyek rencana diberi cadangan ±20% durasi, seperti praktik manajer).
            $baselineEnd = CarbonImmutable::parse($project->baseline_end);
            $contingency = $opt['contingency'] ?? ($startOff > 0 ? (int) ceil($start->diffInDays($baselineEnd) * 0.2) : 2);
            $project->update(['target_date' => $baselineEnd->addDays($contingency)]);

            $this->simulate($project, $opt);
            $scheduler->apply($project->fresh(), true);
            $project = $project->fresh();
            $this->issueLicenses($project);
            $this->projects[$code] = $project;

            if (isset($opt['wbs'])) {
                $tpl = $wbsSvc->saveFromProject($project, $opt['wbs'], 'Disusun dari proyek '.$code.' yang sudah selesai: '.$name.'.');
                $tpl->update(['created_by' => $this->users['sari.manajer']->id]);
                $this->wbs[$code] = $tpl;
            }
        }
    }

    private function needText(string $trigger, string $name): string
    {
        return match ($trigger) {
            'pengapalan' => "Rencana pengapalan dari Purchasing: $name. Semua izin wajib harus terbit sebelum barang tiba agar bisa langsung clearance tanpa biaya demurrage.",
            'produk_baru' => "Produk/kebutuhan baru Indoguna: $name. Izin dan sertifikat harus terbit sebelum produk diedarkan.",
            'perpanjangan' => "Izin akan habis masa berlakunya: $name. Pengurusan dimulai lebih awal agar impor/penjualan tidak terhenti.",
            default => "Perubahan data/pemasok: $name.",
        };
    }

    /** Simulasikan realisasi sampai hari ini. */
    private function simulate(Project $project, array $opt): void
    {
        $tasks = $project->tasks()->with('predecessors', 'permitType', 'documents')->get()->keyBy('id');
        $order = $this->topo($tasks);
        $actualEnd = [];
        $blocked = false;
        foreach ($order as $id) {
            $t = $tasks[$id];
            if (! in_array($t->kind, ['task', 'milestone'])) continue;
            $predEnds = $t->predecessors->map(fn ($p) => $actualEnd[$p->id] ?? null);
            if ($t->predecessors->isNotEmpty() && $predEnds->contains(null)) continue;
            $start = $t->predecessors->isEmpty() ? CarbonImmutable::parse($project->start_date)
                : $predEnds->max()->addDays($t->kind === 'milestone' ? 0 : 1);
            if ($t->not_before && CarbonImmutable::parse($t->not_before)->gt($start)) $start = CarbonImmutable::parse($t->not_before);
            if ($start->gt($this->today)) continue;

            if ($t->kind === 'milestone') {
                $t->update(['status' => 'selesai', 'progress' => 100, 'actual_start' => $start, 'actual_end' => $start]);
                $actualEnd[$id] = $start;
                continue;
            }
            [$lo, $hi] = self::SLIP[$t->permitType?->code] ?? [0.9, 1.2];
            $factor = $lo + (mt_rand(0, 100) / 100) * ($hi - $lo);
            $dur = max(1, (int) round($t->duration_days * $factor) + (mt_rand(0, 100) < 30 ? mt_rand(1, 3) : 0));
            $end = $start->addDays($dur - 1);

            if ($end->lt($this->today)) {
                $t->update(['status' => 'selesai', 'progress' => 100, 'actual_start' => $start, 'actual_end' => $end]);
                foreach ($t->documents as $doc) {
                    $this->demoFile($t, $doc->name, $doc->id, $end);
                    $doc->update(['status' => 'terverifikasi', 'verified_at' => $end->setTime(15, 0), 'verified_by' => $this->users['sari.manajer']->id,
                        'uploaded_by' => $t->assignee_id]);
                }
                $actualEnd[$id] = $end;
            } else {
                $elapsed = $start->diffInDays($this->today) + 1;
                $progress = min(90, (int) round($elapsed / $dur * 100));
                $status = preg_match(Forecaster::WAITING_PATTERN, $t->name) ? 'menunggu' : 'berjalan';
                $note = null;
                if (! empty($opt['block']) && ! $blocked && $t->documents->isNotEmpty()) {
                    $status = 'terhambat';
                    $blocked = true;
                    $note = 'Health certificate dari eksportir belum mencantumkan nomor kontainer; menunggu revisi dari prinsipal melalui Purchasing.';
                }
                $t->update(['status' => $status, 'progress' => $progress, 'actual_start' => $start, 'blocker_note' => $note]);
                foreach ($t->documents as $i => $doc) {
                    if ($i > 0) continue;
                    $this->demoFile($t, $doc->name, $doc->id, $this->today->subDay());
                    $doc->update(['status' => $status === 'terhambat' ? 'ditolak' : 'diajukan', 'uploaded_by' => $t->assignee_id,
                        'note' => $status === 'terhambat' ? 'Nomor kontainer tidak tercantum di health certificate; minta revisi ke eksportir.' : null,
                        'verified_by' => $status === 'terhambat' ? $this->users['fajar.izin']->id : null,
                        'verified_at' => $status === 'terhambat' ? now()->subDay() : null]);
                }
                $this->demoFile($t, 'Bukti pengajuan '.$t->name, null, $this->today, 'jpg');
                $c = TaskComment::create(['project_id' => $project->id, 'task_id' => $t->id, 'user_id' => $t->assignee_id,
                    'body' => $status === 'menunggu' ? 'Berkas sudah diajukan, menunggu evaluasi instansi. Bukti pengajuan terlampir.' : 'Sedang dikerjakan, target selesai sesuai jadwal.']);
                $c->created_at = now()->subHours(mt_rand(3, 40));
                $c->save();
            }
        }

        if (! empty($opt['hold'])) {
            $project->update(['status' => 'tertahan']);
            $t = $project->workTasks()->where('status', '!=', 'selesai')->orderBy('sequence')->first();
            $t?->update(['status' => 'terhambat', 'actual_start' => $t->actual_start ?? $this->today->subDays(3), 'blocker_note' => $opt['hold']]);
        }
    }

    private function topo($tasks): array
    {
        $in = [];
        $succ = [];
        foreach ($tasks as $t) {
            $in[$t->id] = $in[$t->id] ?? 0;
            foreach ($t->predecessors as $p) {
                $in[$t->id]++;
                $succ[$p->id][] = $t->id;
            }
        }
        $q = array_keys(array_filter($in, fn ($v) => $v === 0));
        sort($q);
        $out = [];
        while ($q) {
            $id = array_shift($q);
            $out[] = $id;
            foreach ($succ[$id] ?? [] as $s) if (--$in[$s] === 0) $q[] = $s;
        }
        return $out;
    }

    private function issueLicenses(Project $project): void
    {
        foreach ($project->packages()->with('permitType')->where('status', 'selesai')->get() as $pkg) {
            $type = $pkg->permitType;
            if (! $type || $type->scope === 'shipment') continue;
            $issued = CarbonImmutable::parse($pkg->actual_end);
            $lic = License::create([
                'permit_type_id' => $type->id, 'project_id' => $project->id,
                'number' => sprintf('%s/%s/%04d', $type->code, $issued->format('Y/m'), $pkg->id),
                'issued_at' => $issued, 'supplier_id' => $type->scope === 'supplier' ? $project->supplier_id : null,
                'expires_at' => $type->validity_months ? $issued->addMonths($type->validity_months) : null,
            ]);
            if ($type->scope === 'product') $lic->items()->sync($project->items->pluck('id'));
        }
    }

    /** Komunikasi antar bagian: @mention, komentar dokumen, memo bertenggat dengan riwayat respons. */
    private function communication(): void
    {
        $u = $this->users;
        $p = Party::pluck('id', 'code');
        $squid = $this->projects['PRJ-2026-005'];
        $blocked = $squid->workTasks()->where('status', 'terhambat')->with('documents')->first();
        if (! $blocked) {
            // Pastikan ada contoh dokumen yang ditolak karena belum lengkap (untuk demo komentar dokumen).
            $blocked = $squid->workTasks()->where('status', '!=', 'selesai')->whereHas('documents')->where('name', 'like', '%health certificate%')->first()
                ?? $squid->workTasks()->where('status', '!=', 'selesai')->whereHas('documents')->orderBy('sequence')->first();
            if ($blocked) {
                $blocked->update(['status' => 'terhambat', 'progress' => 30, 'actual_start' => $this->today->subDays(2),
                    'blocker_note' => 'Health certificate dari eksportir belum mencantumkan nomor kontainer; menunggu revisi dari prinsipal melalui Purchasing.']);
                $d = $blocked->documents()->first();
                $this->demoFile($blocked, $d->name, $d->id, $this->today->subDay());
                $d->update(['status' => 'ditolak', 'uploaded_by' => $blocked->assignee_id, 'verified_by' => $u['fajar.izin']->id, 'verified_at' => now()->subDay(),
                    'note' => 'Nomor kontainer dan seal tidak tercantum di health certificate; minta revisi ke eksportir.']);
                $blocked->load('documents');
            }
        }
        if ($blocked) {
            $doc = $blocked->documents->firstWhere('status', 'ditolak');
            $this->comm->comment($u['fajar.izin'], '@purchasing @budi.purchasing mohon minta eksportir merevisi health certificate: nomor kontainer dan seal belum tercantum. Tanpa itu pengajuan karantina ikan akan ditolak.',
                ['task_id' => $blocked->id, 'task_document_id' => $doc?->id], [], [], 'perlu_tindakan');
            $this->comm->comment($u['budi.purchasing'], 'Sudah diminta ke eksportir via email, revisi dijanjikan besok. @fajar.izin nanti saya unggah begitu diterima.',
                ['task_id' => $blocked->id, 'task_document_id' => $doc?->id]);
            $memo = $this->comm->memo($u['fajar.izin'], ['to_party_id' => $p['purchasing'], 'to_user_id' => $u['budi.purchasing']->id,
                'project_id' => $squid->id, 'task_id' => $blocked->id, 'task_document_id' => $doc?->id, 'priority' => 'kritis',
                'subject' => 'Revisi health certificate squid – nomor kontainer belum ada', 'due_date' => $this->today->addDay(),
                'body' => "Mohon dimintakan ke eksportir revisi health certificate untuk PO 4500-0476:\n1. Cantumkan nomor kontainer & seal.\n2. Tanggal produksi sesuai packing list.\nKarantina ikan tidak bisa diajukan sebelum dokumen ini benar."]);
            $this->comm->replyMemo($u['budi.purchasing'], $memo, 'Sudah diteruskan ke eksportir, estimasi revisi diterima besok pagi.');
            $memo->update(['created_at' => now()->subHours(20), 'first_response_at' => now()->subHours(17)]);
        }

        // Memo lain untuk statistik respons per bagian: [dari, ke bagian, proyek, perihal, hari lalu, tenggat (hari), jam respons, status]
        $samples = [
            ['dewi.izin', 'purchasing', 'PRJ-2026-002', 'Data establishment & approval number Est. DK-1327', -30, 2, 5, 'selesai'],
            ['rina.ml', 'purchasing', 'PRJ-2026-011', 'Spesifikasi produk & CoA butter untuk ML BPOM', -25, 3, 30, 'selesai'],
            ['andi.impor', 'perizinan', 'PRJ-2026-002', 'Nomor RP & PI untuk dicantumkan di PIB', -10, 2, 70, 'selesai'],
            ['hasan.halal', 'purchasing', 'PRJ-2026-017', 'Sertifikat halal RPH pemasok daging potong', -6, 2, null, 'terbuka'],
            ['budi.purchasing', 'pangan', 'PRJ-2026-008', 'Konfirmasi kebutuhan label Bahasa Indonesia sweet corn', -4, 3, 20, 'dijawab'],
            ['sari.manajer', 'halal', 'PRJ-2026-018', 'Rencana perpanjangan SHLN butter – mulai minggu depan', -2, 5, 4, 'dijawab'],
            ['fajar.izin', 'impor', 'PRJ-2026-008', 'Jadwal pemeriksaan karantina tumbuhan saat kedatangan', -1, 3, null, 'terbuka'],
        ];
        foreach ($samples as [$from, $to, $prj, $subject, $ago, $due, $respH, $status]) {
            $created = now()->addDays($ago)->setTime(9, 0);
            $memo = $this->comm->memo($u[$from], ['to_party_id' => $p[$to], 'project_id' => $this->projects[$prj]->id, 'priority' => 'normal',
                'subject' => $subject, 'due_date' => $created->copy()->addDays($due)->toDateString(),
                'body' => 'Mohon bantuannya: '.lcfirst($subject).'. Terima kasih.']);
            if ($respH !== null) {
                $toUser = User::where('party_id', $p[$to])->where('id', '!=', $u[$from]->id)->first();
                $this->comm->replyMemo($toUser, $memo, 'Sudah kami tindak lanjuti, dokumen terlampir di kegiatan terkait.', $status === 'selesai');
            }
            $memo->update(['created_at' => $created, 'first_response_at' => $respH !== null ? $created->copy()->addHours($respH) : null,
                'status' => $status, 'closed_at' => $status === 'selesai' ? $created->copy()->addHours($respH + 2) : null]);
        }

        // Diskusi proyek dengan attention ke bagian.
        $pork = $this->projects['PRJ-2026-002'];
        $this->comm->comment($u['sari.manajer'], 'Kuota RP Kementan untuk daging babi tinggal sedikit. @perizinan tolong pastikan pengajuan RP masuk minggu ini; @impor siapkan PI segera setelah RP terbit.',
            ['project_id' => $pork->id], [], [], 'perlu_tindakan');
        $this->comm->comment($u['tono.direksi'], 'Mohon update mingguan proyek ini disampaikan di rapat Senin. @sari.manajer', ['project_id' => $pork->id]);
    }

    /** Buat file contoh sungguhan (PDF/XLSX/DOCX/JPG) sebagai lampiran. */
    private function demoFile(Task $task, string $title, ?int $docId, CarbonImmutable $at, ?string $ext = null): void
    {
        $lower = strtolower($title);
        $ext = $ext ?? match (true) {
            str_contains($lower, 'packing') || str_contains($lower, 'daftar') || str_contains($lower, 'rencana') || str_contains($lower, 'invoice') => 'xlsx',
            str_contains($lower, 'surat') || str_contains($lower, 'permohonan') || str_contains($lower, 'rancangan') || str_contains($lower, 'loa') => 'docx',
            str_contains($lower, 'foto') || str_contains($lower, 'label') => 'jpg',
            default => 'pdf',
        };
        $content = match ($ext) {
            'xlsx' => DemoFiles::xlsx($title, $task),
            'docx' => DemoFiles::docx($title, $task),
            'jpg' => DemoFiles::jpg($title),
            default => DemoFiles::pdf($title, $task),
        };
        if ($content === null) {
            $ext = 'pdf';
            $content = DemoFiles::pdf($title, $task);
        }
        $name = trim(preg_replace('/[^A-Za-z0-9]+/', '-', Str::limit(Str::ascii($title), 50, '')), '-').'.'.$ext;
        $path = 'lampiran/'.$task->project_id.'/'.$task->id.'/'.uniqid().'-'.$name;
        Storage::disk('local')->put($path, $content);
        $a = Attachment::create(['task_id' => $task->id, 'task_document_id' => $docId, 'path' => $path, 'original_name' => $name,
            'mime' => ['pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'jpg' => 'image/jpeg'][$ext],
            'extension' => $ext, 'size' => strlen($content), 'uploaded_by' => $task->assignee_id, 'description' => 'Contoh dokumen demo']);
        $a->created_at = $at->setTime(10, 0);
        $a->save();
    }
}
