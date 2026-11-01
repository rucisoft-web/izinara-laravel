<?php

namespace App\Services;

use App\Models\License;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Proyeksi Izinara.
 *
 * 1. Faktor realisasi: rasio durasi aktual ÷ durasi rencana (baseline) dari tugas yang sudah selesai, per jenis izin.
 *    Median (P50) dan persentil 80 (P80) dipakai untuk memproyeksikan sisa tugas.
 * 2. Proyeksi tanggal izin terbit per proyek (P50/P80) dibanding tenggat.
 * 3. Proyeksi beban kerja per bagian & PIC per minggu dibanding kapasitas.
 * 4. Proyeksi bulanan: izin terbit, izin habis masa berlaku (kebutuhan perpanjangan) dan proyek baru.
 */
class Forecaster
{
    /** Bobot beban: tugas aktif membutuhkan kerja; tugas menunggu instansi hanya pemantauan. */
    public const EFFORT_ACTIVE = 0.5;
    public const EFFORT_WAITING = 0.2;
    public const WAITING_PATTERN = '/(tunggu|menunggu|evaluasi|verifikasi instansi|proses di|pemeriksaan)/i';

    public function __construct(private Scheduler $scheduler) {}

    /**
     * Faktor realisasi per jenis izin = lama paket izin aktual ÷ lama paket di baseline, dari paket yang sudah selesai.
     *
     * @return array{types: array<int,array>, overall: array{p50:float,p80:float,n:int}}
     */
    public function slipFactors(): array
    {
        $done = Task::where('kind', 'package')->where('status', 'selesai')
            ->whereNotNull('actual_start')->whereNotNull('actual_end')->whereNotNull('baseline_start')->whereNotNull('baseline_end')
            ->get(['id', 'permit_type_id', 'actual_start', 'actual_end', 'baseline_start', 'baseline_end']);
        $ratio = fn ($t) => ($t->actual_start->diffInDays($t->actual_end) + 1) / max(1, $t->baseline_start->diffInDays($t->baseline_end) + 1);

        $types = [];
        foreach ($done->groupBy('permit_type_id') as $typeId => $rows) {
            $r = $rows->map($ratio)->sort()->values();
            $types[(int) $typeId] = ['p50' => $this->pct($r, 0.5), 'p80' => $this->pct($r, 0.8), 'n' => $r->count()];
        }
        $all = $done->map($ratio)->sort()->values();

        return ['types' => $types, 'overall' => [
            'p50' => $all->count() ? $this->pct($all, 0.5) : 1.0,
            'p80' => $all->count() ? $this->pct($all, 0.8) : 1.25,
            'n' => $all->count(),
        ]];
    }

    private function pct(Collection $sorted, float $p): float
    {
        if ($sorted->isEmpty()) return 1.0;
        $idx = ($sorted->count() - 1) * $p;
        $lo = (int) floor($idx);
        $hi = (int) ceil($idx);
        return round($sorted[$lo] + ($sorted[$hi] - $sorted[$lo]) * ($idx - $lo), 2);
    }

    /** Proyeksi satu proyek. $extra = tambahan faktor skenario (mis. 0.1 = semua tugas 10% lebih lama). */
    public function projectForecast(Project $project, ?array $factors = null, float $extra = 0.0): array
    {
        $factors = $factors ?? $this->slipFactors();
        $tasks = $project->tasks()->with('predecessors')->get();
        $out = ['plan' => $project->planned_end, 'baseline' => $project->baseline_end, 'target' => $project->target_date];
        foreach (['p50', 'p80'] as $p) {
            $f = [];
            foreach ($tasks as $t) {
                if ($t->kind !== 'task' || $t->status === 'selesai') continue;
                $base = $factors['types'][$t->permit_type_id][$p] ?? $factors['overall'][$p];
                if (($factors['types'][$t->permit_type_id]['n'] ?? 0) < 2) {
                    $base = max($base, $factors['overall'][$p]);
                }
                $f[$t->id] = max(1.0, $base) + $extra;
            }
            $res = $this->scheduler->compute($project, $tasks, true, $f);
            $end = collect($res)->max(fn ($r) => $r['end']->timestamp);
            $out[$p] = $end ? CarbonImmutable::createFromTimestamp($end)->startOfDay() : null;
        }
        $out['late_p50'] = $out['p50'] && $project->target_date ? (int) $project->target_date->diffInDays($out['p50'], false) : 0;
        $out['late_p80'] = $out['p80'] && $project->target_date ? (int) $project->target_date->diffInDays($out['p80'], false) : 0;
        $out['risk'] = $out['late_p50'] > 0 ? 'tinggi' : ($out['late_p80'] > 0 ? 'sedang' : 'rendah');

        return $out;
    }

    public function portfolio(float $extra = 0.0): Collection
    {
        $factors = $this->slipFactors();
        return Project::with('manager', 'template')->whereIn('status', ['rencana', 'berjalan', 'tertahan'])
            ->orderBy('target_date')->get()
            ->map(fn ($p) => ['project' => $p] + $this->projectForecast($p, $factors, $extra));
    }

    /**
     * Beban kerja mingguan (hari-orang).
     *
     * @return array{weeks: array<int,CarbonImmutable>, parties: array, users: array}
     */
    public function workload(int $weeks = 12, float $extra = 0.0, int $extraCapacity = 0): array
    {
        $start = CarbonImmutable::today()->startOfWeek();
        $weekStarts = collect(range(0, $weeks - 1))->map(fn ($i) => $start->addWeeks($i));
        $end = $start->addWeeks($weeks);
        $tasks = Task::with('assignee', 'party')->where('kind', 'task')->where('status', '!=', 'selesai')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['rencana', 'berjalan', 'tertahan']))
            ->whereNotNull('planned_start')->where('planned_start', '<', $end)->get();

        $byParty = [];
        $byUser = [];
        foreach ($tasks as $t) {
            $s = CarbonImmutable::parse($t->planned_start);
            $e = CarbonImmutable::parse($t->planned_end);
            if ($e->lt(CarbonImmutable::today())) $e = CarbonImmutable::today(); // terlambat: beban digeser ke minggu ini
            if ($s->lt(CarbonImmutable::today())) $s = CarbonImmutable::today();
            $span = $s->diffInDays($e) + 1;
            $weight = (preg_match(self::WAITING_PATTERN, $t->name) || $t->status === 'menunggu') ? self::EFFORT_WAITING : self::EFFORT_ACTIVE;
            $effort = $t->duration_days * (1 + $extra) * $weight * (100 - $t->progress) / 100;
            $perDay = $effort / max(1, $span);
            for ($d = $s; $d->lte($e) && $d->lt($end); $d = $d->addDay()) {
                if ($d->isWeekend()) continue;
                $w = (int) floor($start->diffInDays($d) / 7);
                $byParty[$t->party_id][$w] = ($byParty[$t->party_id][$w] ?? 0) + $perDay * 7 / 5;
                $key = $t->assignee_id ?: 0;
                $byUser[$key][$w] = ($byUser[$key][$w] ?? 0) + $perDay * 7 / 5;
            }
        }

        $parties = Party::with('users')->orderBy('sequence')->get()->map(function ($p) use ($byParty, $weeks, $extraCapacity) {
            $cap = max(1, $p->users->where('active', true)->sum('weekly_capacity')) + $extraCapacity * 5;
            $load = array_map(fn ($w) => round($byParty[$p->id][$w] ?? 0, 1), range(0, $weeks - 1));
            return ['party' => $p, 'capacity' => $cap, 'load' => $load,
                'util' => array_map(fn ($l) => (int) round($l / $cap * 100), $load)];
        });
        $users = User::with('party')->where('active', true)->whereIn('role', ['pic', 'manajer'])->orderBy('party_id')->get()
            ->map(function ($u) use ($byUser, $weeks) {
                $load = array_map(fn ($w) => round($byUser[$u->id][$w] ?? 0, 1), range(0, $weeks - 1));
                $cap = max(1, $u->weekly_capacity);
                return ['user' => $u, 'capacity' => $cap, 'load' => $load,
                    'util' => array_map(fn ($l) => (int) round($l / $cap * 100), $load)];
            });
        $unassigned = array_map(fn ($w) => round($byUser[0][$w] ?? 0, 1), range(0, $weeks - 1));

        return ['weeks' => $weekStarts->all(), 'parties' => $parties, 'users' => $users, 'unassigned' => $unassigned];
    }

    /** Proyeksi bulanan 12 bulan ke depan. */
    public function monthly(int $months = 12, float $extra = 0.0): array
    {
        $start = CarbonImmutable::today()->startOfMonth();
        $labels = [];
        $issued = $expiring = $newProjects = $renewalStarts = array_fill(0, $months, 0);
        for ($i = 0; $i < $months; $i++) $labels[] = $start->addMonths($i)->locale('id')->translatedFormat('M Y');
        $idx = function ($date) use ($start, $months) {
            if (! $date) return null;
            $d = CarbonImmutable::parse($date);
            $i = ($d->year - $start->year) * 12 + $d->month - $start->month;
            return $i >= 0 && $i < $months ? $i : null;
        };

        foreach ($this->portfolio($extra) as $row) {
            $p = $row['project'];
            if (($i = $idx($row['p50'])) !== null) $issued[$i] += $p->packages()->where('status', '!=', 'selesai')->count();
            if ($p->status === 'rencana' && ($i = $idx($p->start_date)) !== null) $newProjects[$i]++;
        }
        $renewals = [];
        foreach (License::with('permitType')->whereNotNull('expires_at')->get() as $lic) {
            if (($i = $idx($lic->expires_at)) !== null) {
                $expiring[$i]++;
                $startBy = $lic->expires_at->copy()->subDays(($lic->permitType->lead_days ?? 14) + 30);
                $renewals[] = ['license' => $lic, 'start_by' => $startBy];
                if (($j = $idx($startBy)) !== null) $renewalStarts[$j]++;
                elseif ($startBy->lt($start)) $renewalStarts[0]++;
            }
        }
        usort($renewals, fn ($a, $b) => $a['start_by'] <=> $b['start_by']);

        return compact('labels', 'issued', 'expiring', 'newProjects', 'renewalStarts', 'renewals');
    }
}
