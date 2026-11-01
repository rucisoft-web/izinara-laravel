<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DocSpec;
use App\Models\Party;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Regulation;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\TaskDocument;
use App\Models\WbsTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Template WBS: simpan struktur kerja proyek (paket, tugas, milestone, durasi, dependensi, PIC per bagian,
 * referensi peraturan, dokumen wajib) lalu salin ke proyek baru tanpa mengetik ulang.
 */
class WbsTemplateService
{
    public function __construct(private Scheduler $scheduler, private ProjectBuilder $builder) {}

    /** Buat payload template dari proyek yang sudah ada. */
    public function payloadFromProject(Project $project): array
    {
        $tasks = $project->tasks()->with('predecessors:id', 'regulations', 'documents', 'permitType', 'party')->get();
        $start = CarbonImmutable::parse($project->start_date);
        $eta = $project->eta ? CarbonImmutable::parse($project->eta) : null;
        $out = [];
        foreach ($tasks as $t) {
            $base = $t->baseline_start ?? $t->planned_start;
            $out[] = [
                'key' => $t->id,
                'parent' => $t->parent_id,
                'kind' => $t->kind,
                'wbs' => $t->wbs,
                'name' => $t->name,
                'permit_type' => $t->permitType?->code,
                'step_id' => $t->permit_type_step_id,
                'party' => $t->party?->code,
                'duration_days' => (int) $t->duration_days,
                'offset' => $base ? (int) $start->diffInDays($base, false) : 0,
                'eta_offset' => $t->not_before && $eta ? (int) $eta->diffInDays($t->not_before, false) : null,
                'instructions' => $t->instructions,
                'deliverable' => $t->deliverable,
                'regulations' => $t->regulations->map(fn ($r) => ['code' => $r->code, 'article' => $r->pivot->article])->values()->all(),
                'docs' => $t->documents->map(fn ($d) => ['name' => $d->name, 'spec' => $d->doc_spec_id])->values()->all(),
                'deps' => $t->predecessors->pluck('id')->all(),
            ];
        }
        return $out;
    }

    public function saveFromProject(Project $project, string $name, ?string $description = null): WbsTemplate
    {
        $tpl = WbsTemplate::create([
            'code' => 'WBS-'.strtoupper(Str::random(6)),
            'name' => $name,
            'food_group' => $project->template?->food_group ?? $project->items->first()?->template?->food_group,
            'template_id' => $project->template_id ?? $project->items->first()?->template_id,
            'description' => $description,
            'payload' => $this->payloadFromProject($project),
            'source_project_id' => $project->id,
            'created_by' => auth()->id(),
        ]);
        ActivityLog::record('template_disimpan', 'Template WBS "'.$name.'" dibuat dari proyek ini', $project);
        return $tpl;
    }

    /**
     * Salin template ke proyek (menggantikan WBS yang ada). PIC diisi dari PIC proyek per bagian.
     * $skipCovered: paket izin yang sudah tercakup izin berlaku di register tidak disalin.
     */
    public function applyTo(Project $project, WbsTemplate $tpl, bool $skipCovered = true): array
    {
        $project->tasks()->delete();
        $project->load('pics', 'items.template.lines');
        $parties = Party::pluck('id', 'code');
        $types = PermitType::all()->keyBy('code');
        $regs = Regulation::pluck('id', 'code');
        $specs = DocSpec::pluck('id', 'id');
        $pic = fn ($partyId) => $partyId ? $project->pics->firstWhere('party_id', $partyId)?->user_id : null;

        $skip = [];
        $covered = [];
        if ($skipCovered) {
            $refDate = $project->eta ?? $project->target_date;
            foreach ($tpl->payload as $n) {
                if ($n['kind'] !== 'package' || ! $n['permit_type'] || ! isset($types[$n['permit_type']])) continue;
                $lic = $this->builder->coveringLicense($types[$n['permit_type']], $project, $refDate);
                if ($lic && $project->trigger !== 'perpanjangan') {
                    $skip[$n['key']] = true;
                    $covered[] = ['type' => $types[$n['permit_type']]->name, 'license' => $lic->number, 'expires' => $lic->expires_at?->toDateString()];
                }
            }
            foreach ($tpl->payload as $n) if ($n['parent'] && isset($skip[$n['parent']])) $skip[$n['key']] = true;
        }

        $map = [];
        $seq = 0;
        $start = CarbonImmutable::parse($project->start_date);
        $eta = $project->eta ? CarbonImmutable::parse($project->eta) : null;
        // Induk dulu agar parent_id bisa dipetakan.
        $nodes = collect($tpl->payload)->sortBy(fn ($n) => $n['parent'] ? 1 : 0)->values();
        $ordered = collect($tpl->payload); // urutan asli dipakai untuk sequence
        foreach ($nodes as $n) {
            if (isset($skip[$n['key']])) continue;
            $partyId = $n['party'] ? ($parties[$n['party']] ?? null) : null;
            $task = Task::create([
                'project_id' => $project->id,
                'parent_id' => $n['parent'] ? ($map[$n['parent']] ?? null) : null,
                'kind' => $n['kind'], 'wbs' => $n['wbs'], 'name' => $n['name'],
                'sequence' => ($ordered->search(fn ($o) => $o['key'] === $n['key']) + 1) * 10,
                'permit_type_id' => $n['permit_type'] ? ($types[$n['permit_type']]->id ?? null) : null,
                'permit_type_step_id' => $n['step_id'],
                'party_id' => $partyId, 'assignee_id' => $pic($partyId),
                'duration_days' => $n['duration_days'],
                'not_before' => $n['eta_offset'] !== null && $eta ? $eta->addDays($n['eta_offset']) : null,
                'instructions' => $n['instructions'], 'deliverable' => $n['deliverable'], 'status' => 'belum',
            ]);
            $map[$n['key']] = $task->id;
            $task->regulations()->sync(collect($n['regulations'])->filter(fn ($r) => isset($regs[$r['code']]))
                ->mapWithKeys(fn ($r) => [$regs[$r['code']] => ['article' => $r['article']]])->all());
            foreach ($n['docs'] as $d) {
                TaskDocument::create(['task_id' => $task->id, 'doc_spec_id' => isset($specs[$d['spec']]) ? $d['spec'] : null,
                    'name' => $d['name'], 'status' => 'belum']);
            }
        }
        foreach ($tpl->payload as $n) {
            if (! isset($map[$n['key']])) continue;
            $deps = collect($n['deps'])->map(fn ($k) => $map[$k] ?? null)->filter();
            // Bila pendahulu dilewati (izin sudah dimiliki), sambungkan ke milestone M1 agar urutan tetap benar.
            if ($deps->isEmpty() && $n['deps'] && $n['kind'] !== 'package') {
                $m1 = collect($tpl->payload)->first(fn ($x) => $x['kind'] === 'milestone' && isset($map[$x['key']]));
                if ($m1 && $m1['key'] !== $n['key']) $deps = collect([$map[$m1['key']]]);
            }
            foreach ($deps as $d) TaskDependency::firstOrCreate(['task_id' => $map[$n['key']], 'depends_on_id' => $d]);
        }

        $project->update(['wbs_template_id' => $tpl->id, 'covered_permits' => $covered,
            'template_id' => $project->template_id ?? $tpl->template_id]);
        $tpl->increment('times_used');
        // Hapus paket kosong.
        Task::where('project_id', $project->id)->where('kind', 'package')->whereDoesntHave('children')->delete();
        $this->scheduler->apply($project->fresh(), false, true);
        ActivityLog::record('wbs_disalin', 'WBS disalin dari template "'.$tpl->name.'"'.($covered ? ', '.count($covered).' izin dipakai ulang' : ''), $project);

        return ['tasks' => count($map), 'skipped' => count($covered)];
    }
}
