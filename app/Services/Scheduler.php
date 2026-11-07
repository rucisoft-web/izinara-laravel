<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Penjadwalan metode jalur kritis (CPM) untuk satu proyek perizinan.
 *
 * - Hari kalender (instansi menghitung SLA dalam hari kalender/hari kerja; Izinara memakai hari kalender agar
 *   sederhana dan konservatif).
 * - Tugas selesai memakai tanggal aktual. Tugas berjalan memakai sisa durasi dari hari ini bila $fromToday.
 * - Paket izin (summary) mengikuti anak-anaknya; milestone jatuh pada akhir pendahulunya.
 * - compute() murni (tidak menyimpan) sehingga bisa dipakai untuk proyeksi dengan faktor durasi berbeda.
 */
class Scheduler
{
    /**
     * @param  Collection<Task>  $tasks  tugas proyek beserta relasi predecessors
     * @param  array<int,float>  $durationFactor  task_id => pengali durasi (untuk proyeksi)
     * @return array<int,array{start:CarbonImmutable,end:CarbonImmutable,critical:bool,slack:int}>
     */
    public function compute(Project $project, Collection $tasks, bool $fromToday = false, array $durationFactor = [], ?CarbonImmutable $today = null): array
    {
        $today = $today ?? CarbonImmutable::today();
        $projectStart = CarbonImmutable::parse($project->start_date);
        $work = $tasks->whereIn('kind', ['task', 'milestone'])->keyBy('id');
        $preds = [];
        foreach ($work as $t) {
            $preds[$t->id] = $t->predecessors->filter(fn ($p) => $work->has($p->id))
                ->map(fn ($p) => ['id' => $p->id, 'lag' => (int) $p->pivot->lag_days])->values()->all();
        }

        $order = $this->topoSort(array_keys($preds), $preds);
        $res = [];
        foreach ($order as $id) {
            $t = $work[$id];
            $isMs = $t->kind === 'milestone';
            $dur = $isMs ? 0 : max(1, (int) round($t->duration_days * ($durationFactor[$id] ?? 1)));

            $earliest = $projectStart;
            $maxPredEnd = null;
            foreach ($preds[$id] as $p) {
                $pe = $res[$p['id']]['end'];
                $maxPredEnd = $maxPredEnd === null || $pe->gt($maxPredEnd) ? $pe : $maxPredEnd;
                $cand = $pe->addDays(1 + $p['lag']);
                if ($cand->gt($earliest)) $earliest = $cand;
            }
            if ($t->not_before && CarbonImmutable::parse($t->not_before)->gt($earliest)) {
                $earliest = CarbonImmutable::parse($t->not_before);
            }

            if ($isMs) {
                if ($t->status === 'selesai' && $t->actual_end) {
                    $start = $end = CarbonImmutable::parse($t->actual_end);
                } else {
                    $start = $end = $maxPredEnd ?? $earliest;
                    if ($fromToday && $end->lt($today)) $start = $end = $today;
                }
            } elseif ($t->status === 'selesai' && $t->actual_start && $t->actual_end) {
                $start = CarbonImmutable::parse($t->actual_start);
                $end = CarbonImmutable::parse($t->actual_end);
            } elseif (in_array($t->status, ['berjalan', 'menunggu', 'terhambat']) && $t->actual_start) {
                $start = CarbonImmutable::parse($t->actual_start);
                $end = $start->addDays($dur - 1);
                if ($fromToday) {
                    $remaining = max(1, (int) ceil($dur * (100 - $t->progress) / 100));
                    $endFromToday = $today->addDays($remaining - 1);
                    if ($endFromToday->gt($end)) $end = $endFromToday;
                }
            } else {
                $start = $earliest;
                if ($fromToday && $start->lt($today)) $start = $today;
                $end = $start->addDays($dur - 1);
            }
            $res[$id] = ['start' => $start, 'end' => $end, 'milestone' => $isMs, 'dur' => $dur];
        }

        // Backward pass untuk slack & jalur kritis.
        $projectEnd = collect($res)->max(fn ($r) => $r['end']->timestamp);
        $projectEnd = CarbonImmutable::createFromTimestamp($projectEnd)->startOfDay();
        $succ = [];
        foreach ($preds as $id => $ps) foreach ($ps as $p) $succ[$p['id']][] = ['id' => $id, 'lag' => $p['lag']];
        $lateFinish = [];
        foreach (array_reverse($order) as $id) {
            $lf = $projectEnd;
            foreach ($succ[$id] ?? [] as $s) {
                $ls = $lateFinish[$s['id']]->subDays($res[$s['id']]['milestone'] ? 0 : max(0, $res[$s['id']]['dur'] - 1));
                $cand = $res[$s['id']]['milestone'] ? $ls->subDays($s['lag']) : $ls->subDays(1 + $s['lag']);
                if ($cand->lt($lf)) $lf = $cand;
            }
            $lateFinish[$id] = $lf;
            $slack = (int) $res[$id]['end']->diffInDays($lf, false);
            $res[$id]['slack'] = max(0, $slack);
            $res[$id]['critical'] = $slack <= 0 && $work[$id]->status !== 'selesai';
        }

        return $res;
    }

    /** Hitung & simpan jadwal proyek, termasuk ringkasan paket dan proyek. */
    public function apply(Project $project, bool $fromToday = false, bool $setBaseline = false): void
    {
        $tasks = $project->tasks()->with('predecessors')->get();
        $res = $this->compute($project, $tasks, $fromToday);

        foreach ($tasks as $t) {
            if (! isset($res[$t->id])) continue;
            $t->planned_start = $res[$t->id]['start']->toDateString();
            $t->planned_end = $res[$t->id]['end']->toDateString();
            $t->is_critical = $res[$t->id]['critical'];
            $t->slack_days = $res[$t->id]['slack'];
            if ($setBaseline || ! $t->baseline_start) {
                $t->baseline_start = $t->planned_start;
                $t->baseline_end = $t->planned_end;
            }
            $t->save();
        }
        $this->rollup($project, $tasks, $setBaseline);
    }

    /** Paket izin = ringkasan dari tugas-tugasnya. */
    public function rollup(Project $project, ?Collection $tasks = null, bool $setBaseline = false): void
    {
        $tasks = $tasks ?? $project->tasks()->get();
        foreach ($tasks->where('kind', 'package') as $pkg) {
            $kids = $tasks->where('parent_id', $pkg->id)->where('kind', 'task');
            if ($kids->isEmpty()) continue;
            $pkg->planned_start = $kids->min(fn ($k) => $k->planned_start);
            $pkg->planned_end = $kids->max(fn ($k) => $k->planned_end);
            if ($setBaseline || ! $pkg->baseline_start) {
                $pkg->baseline_start = $kids->min(fn ($k) => $k->baseline_start);
                $pkg->baseline_end = $kids->max(fn ($k) => $k->baseline_end);
            }
            $dur = max(1, $kids->sum('duration_days'));
            $pkg->duration_days = (int) Carbon::parse($pkg->planned_start)->diffInDays($pkg->planned_end) + 1;
            $pkg->progress = (int) round($kids->sum(fn ($k) => $k->duration_days * $k->progress) / $dur);
            $pkg->is_critical = $kids->contains('is_critical', true);
            $pkg->status = match (true) {
                $kids->every(fn ($k) => $k->status === 'selesai') => 'selesai',
                $kids->contains('status', 'terhambat') => 'terhambat',
                $kids->contains('status', 'menunggu') => 'menunggu',
                $kids->contains(fn ($k) => in_array($k->status, ['berjalan', 'selesai'])) => 'berjalan',
                default => 'belum',
            };
            $pkg->actual_start = $kids->whereNotNull('actual_start')->min(fn ($k) => $k->actual_start);
            $pkg->actual_end = $pkg->status === 'selesai' ? $kids->max(fn ($k) => $k->actual_end) : null;
            $pkg->save();
        }
        if ($setBaseline || ! $project->baseline_end) {
            $project->baseline_end = $tasks->max(fn ($t) => $t->baseline_end);
        }
        $project->save();
        $project->recalcProgress();
    }

    private function topoSort(array $ids, array $preds): array
    {
        $in = array_fill_keys($ids, 0);
        $succ = [];
        foreach ($preds as $id => $ps) {
            foreach ($ps as $p) {
                $in[$id]++;
                $succ[$p['id']][] = $id;
            }
        }
        $queue = array_keys(array_filter($in, fn ($n) => $n === 0));
        sort($queue);
        $out = [];
        while ($queue) {
            $id = array_shift($queue);
            $out[] = $id;
            foreach ($succ[$id] ?? [] as $s) {
                if (--$in[$s] === 0) $queue[] = $s;
            }
        }
        // Siklus (seharusnya tidak terjadi): tambahkan sisanya agar tidak hilang.
        foreach ($ids as $id) if (! in_array($id, $out, true)) $out[] = $id;
        return $out;
    }
}
