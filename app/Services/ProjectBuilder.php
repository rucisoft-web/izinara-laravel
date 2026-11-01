<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Party;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\TaskDocument;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menyusun struktur kerja (WBS) proyek perizinan dari kebutuhan:
 *
 *   1. Kebutuhan izin         – identifikasi item/HS, cek izin yang sudah dimiliki, penetapan PIC → M1
 *   2..n Paket per jenis izin – langkah standar jenis izin, berurutan, dengan prasyarat antar izin
 *   Milestone                 – rekomendasi teknis terbit, izin edar/PI terbit, semua izin terbit (siap clearance)
 *
 * Setiap tugas membawa referensi peraturan dari langkah standarnya (atau dari jenis izinnya).
 */
class ProjectBuilder
{
    /** Kode jenis izin yang menjadi prasyarat (bila ada di proyek yang sama). */
    public const PREREQUISITES = [
        'RP_KEMENTAN' => ['NIB_API', 'NKV', 'EST_APPROVAL'],
        'RPHP_KKP' => ['NIB_API'],
        'RIPH' => ['NIB_API'],
        'PI_KEMENDAG' => ['RP_KEMENTAN', 'RPHP_KKP', 'RIPH'],
        'LABEL_ID' => ['ML_BPOM'],
        'SKI_BPOM' => ['ML_BPOM', 'LARTAS_CHECK'],
        'QUARANTINE_ANIMAL' => ['RP_KEMENTAN', 'PI_KEMENDAG'],
        'QUARANTINE_FISH' => ['RPHP_KKP', 'PI_KEMENDAG'],
        'QUARANTINE_PLANT' => ['RIPH', 'PSAT', 'PI_KEMENDAG'],
        'CUSTOMS_PIB' => ['LARTAS_CHECK', 'PI_KEMENDAG', 'SKI_BPOM', 'QUARANTINE_ANIMAL', 'QUARANTINE_FISH',
            'QUARANTINE_PLANT', 'SHLN', 'NONHALAL', 'COO'],
        'MD_BPOM' => ['LOCAL_SUPPLIER'],
    ];

    /** Jenis izin yang dikerjakan saat barang tiba (tidak lebih awal dari ETA dikurangi n hari). */
    public const AT_ARRIVAL = ['QUARANTINE_ANIMAL' => 0, 'QUARANTINE_FISH' => 0, 'QUARANTINE_PLANT' => 0, 'CUSTOMS_PIB' => -2];

    public const TECHNICAL = ['NKV', 'EST_APPROVAL', 'RP_KEMENTAN', 'RPHP_KKP', 'RIPH'];
    public const MARKETING = ['PI_KEMENDAG', 'ML_BPOM', 'MD_BPOM', 'PSAT', 'SHLN', 'HALAL_SH'];

    public function __construct(private Scheduler $scheduler) {}

    /**
     * Tentukan jenis izin yang dibutuhkan dari item (via template) dan pisahkan yang sudah tercakup izin berlaku.
     *
     * @return array{needed: Collection<PermitType>, covered: array<int,array>, mandatory: array<int,bool>}
     */
    public function requirements(Project $project): array
    {
        $project->loadMissing('items.template.lines.permitType', 'template.lines.permitType');
        $lines = collect();
        foreach ($project->items as $item) {
            foreach ($item->template?->lines ?? [] as $line) $lines->push($line);
        }
        if ($lines->isEmpty() && $project->template) $lines = $project->template->lines;

        $mandatory = [];
        $types = collect();
        foreach ($lines as $line) {
            $types[$line->permit_type_id] = $line->permitType;
            $mandatory[$line->permit_type_id] = ($mandatory[$line->permit_type_id] ?? false) || $line->mandatory;
        }

        $refDate = $project->eta ?? $project->target_date ?? today();
        $covered = [];
        $needed = collect();
        foreach ($types->sortBy('sequence') as $type) {
            $license = $this->coveringLicense($type, $project, $refDate);
            if ($license && $project->trigger !== 'perpanjangan') {
                $covered[] = ['type' => $type->name, 'license' => $license->number,
                    'expires' => $license->expires_at?->toDateString()];
            } else {
                $needed->push($type);
            }
        }

        return ['needed' => $needed, 'covered' => $covered, 'mandatory' => $mandatory];
    }

    public function coveringLicense(PermitType $type, Project $project, $refDate): ?License
    {
        if ($type->scope === 'shipment') return null;
        $q = License::where('permit_type_id', $type->id)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $refDate));
        if ($type->scope === 'supplier') {
            if (! $project->supplier_id) return null;
            $q->where('supplier_id', $project->supplier_id);
        }
        if ($type->scope === 'product') {
            $itemIds = $project->items->filter(fn ($i) => $i->template?->lines->contains('permit_type_id', $type->id))->pluck('id');
            if ($itemIds->isEmpty()) return null;
            foreach ($q->with('items:id')->get() as $lic) {
                if ($itemIds->diff($lic->items->pluck('id'))->isEmpty()) return $lic;
            }
            return null;
        }
        return $q->first();
    }

    /** Bangun ulang seluruh WBS proyek dan jadwalkan. */
    public function build(Project $project, ?Collection $types = null): Project
    {
        $req = $this->requirements($project);
        $types = $types ?? $req['needed'];
        $project->covered_permits = $req['covered'];
        $project->save();
        $project->tasks()->delete();
        $project->load('pics');

        $seq = 0;
        $parties = Party::all()->keyBy('code');
        $pic = fn (?int $partyId) => $partyId ? $project->pics->firstWhere('party_id', $partyId)?->user_id : null;

        // 1. Kebutuhan izin
        $need = $this->task($project, ['kind' => 'package', 'wbs' => '1', 'sequence' => $seq += 10,
            'name' => 'Kebutuhan izin', 'party_id' => $parties['perizinan']->id ?? null]);
        $needSteps = [
            ['Identifikasi kebutuhan: item code, HS, negara & unit usaha asal', 'purchasing', 2,
                'Kumpulkan daftar item code, kode HS 8 digit, negara asal, eksportir dan establishment. Untuk pengapalan, lampirkan rencana PO, ETD dan ETA.', 'Daftar item & rencana impor', ['PERMENDAG_16_2025', 'PMK_BTKI']],
            ['Cek lartas HS di INSW & izin yang sudah dimiliki', 'impor', 1,
                'Cek lartas setiap HS di INSW dan bandingkan dengan register izin. Izin yang masih berlaku pada ETA dipakai ulang; sisanya menjadi paket izin di proyek ini.', 'Hasil cek lartas', ['PERMENDAG_16_2025', 'PMK_BTKI', 'PMK_IMPOR']],
            ['Tetapkan PIC, jadwal dan baseline proyek', 'perizinan', 1,
                'Manajer menetapkan PIC tiap bagian, memeriksa jadwal hasil hitung otomatis (Gantt) lalu menyimpan baseline.', 'Jadwal & PIC disetujui', ['PP_28_2025']],
        ];
        $prev = null;
        $i = 0;
        foreach ($needSteps as [$name, $party, $dur, $instr, $deliv, $regs]) {
            $t = $this->task($project, ['kind' => 'task', 'parent_id' => $need->id, 'wbs' => '1.'.(++$i), 'sequence' => $seq += 10,
                'name' => $name, 'party_id' => $parties[$party]->id ?? null, 'assignee_id' => $pic($parties[$party]->id ?? null),
                'duration_days' => $dur, 'instructions' => $instr, 'deliverable' => $deliv]);
            $t->regulations()->sync(\App\Models\Regulation::whereIn('code', $regs)->pluck('id'));
            if ($prev) $this->link($t, $prev);
            $prev = $t;
        }
        $m1 = $this->task($project, ['kind' => 'milestone', 'wbs' => 'M1', 'sequence' => $seq += 10,
            'name' => 'M1 · Kebutuhan izin disetujui', 'duration_days' => 0]);
        $this->link($m1, $prev);

        // 2. Paket per jenis izin
        $lastOf = [];
        $firstOf = [];
        $n = 1;
        $types = $types->sortBy('sequence')->values();
        foreach ($types as $type) {
            $type->loadMissing('steps.regulations', 'steps.docSpecs', 'regulations');
            $n++;
            $pkg = $this->task($project, ['kind' => 'package', 'wbs' => (string) $n, 'sequence' => $seq += 10,
                'name' => $type->name, 'permit_type_id' => $type->id, 'party_id' => $type->party_id,
                'assignee_id' => $pic($type->party_id), 'instructions' => $type->description]);
            $pkg->regulations()->sync($type->regulations->pluck('id'));
            $prev = null;
            $k = 0;
            foreach ($type->steps as $step) {
                $notBefore = null;
                if (array_key_exists($type->code, self::AT_ARRIVAL) && $project->eta && $k === 0) {
                    $notBefore = Carbon::parse($project->eta)->addDays(self::AT_ARRIVAL[$type->code])->toDateString();
                }
                $t = $this->task($project, ['kind' => 'task', 'parent_id' => $pkg->id, 'wbs' => $n.'.'.(++$k),
                    'sequence' => $seq += 10, 'name' => $step->name, 'permit_type_id' => $type->id,
                    'permit_type_step_id' => $step->id, 'party_id' => $step->party_id,
                    'assignee_id' => $pic($step->party_id), 'duration_days' => $step->duration_days,
                    'instructions' => $step->instructions, 'deliverable' => $step->deliverable, 'not_before' => $notBefore]);
                $regs = $step->regulations->isNotEmpty() ? $step->regulations : $type->regulations;
                $t->regulations()->sync($regs->mapWithKeys(fn ($r) => [$r->id => ['article' => $r->pivot->article ?? null]])->all());
                foreach ($step->docSpecs as $spec) {
                    TaskDocument::create(['task_id' => $t->id, 'doc_spec_id' => $spec->id, 'name' => $spec->name, 'status' => 'belum']);
                }
                if ($prev) $this->link($t, $prev);
                else {
                    $firstOf[$type->code] = $t;
                }
                $prev = $t;
            }
            if ($prev) $lastOf[$type->code] = $prev;
        }

        foreach ($firstOf as $code => $first) {
            $deps = array_filter(self::PREREQUISITES[$code] ?? [], fn ($c) => isset($lastOf[$c]));
            if (! $deps) $this->link($first, $m1);
            foreach ($deps as $c) $this->link($first, $lastOf[$c]);
        }

        // Milestone
        $ms = [];
        $tech = array_intersect_key($lastOf, array_flip(self::TECHNICAL));
        if ($tech) $ms[] = ['M2 · Rekomendasi teknis terbit', $tech];
        $mkt = array_intersect_key($lastOf, array_flip(self::MARKETING));
        if ($mkt) $ms[] = ['M3 · Persetujuan impor & izin edar terbit', $mkt];
        $ms[] = ['M4 · Semua izin terbit (siap clearance)', $lastOf ?: [$m1]];
        $mi = 1;
        foreach ($ms as [$name, $preds]) {
            $m = $this->task($project, ['kind' => 'milestone', 'wbs' => 'M'.(++$mi), 'sequence' => $seq += 10,
                'name' => preg_replace('/^M\d/', 'M'.$mi, $name), 'duration_days' => 0]);
            foreach ($preds as $p) $this->link($m, $p);
        }

        $this->scheduler->apply($project->fresh(), false, true);
        ActivityLog::record('wbs_dibuat', count($types).' paket izin, '.count($req['covered']).' izin dipakai ulang', $project);

        return $project->fresh();
    }

    private function task(Project $project, array $attrs): Task
    {
        return Task::create(['project_id' => $project->id, 'status' => 'belum'] + $attrs);
    }

    private function link(Task $task, Task $dependsOn, int $lag = 0): void
    {
        TaskDependency::firstOrCreate(['task_id' => $task->id, 'depends_on_id' => $dependsOn->id], ['lag_days' => $lag]);
    }
}
