<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectPic;
use App\Models\Regulation;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Template;
use App\Models\User;
use App\Models\WbsTemplate;
use App\Services\Forecaster;
use App\Services\ProjectBuilder;
use App\Services\Scheduler;
use App\Services\WbsTemplateService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $q = Project::with('manager', 'template', 'supplier', 'workTasks')->withCount('workTasks');
        if ($s = $request->query('q')) $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        if ($st = $request->query('status')) $q->where('status', $st);
        if ($g = $request->query('kelompok')) $q->whereHas('template', fn ($t) => $t->where('food_group', $g));
        $projects = $q->orderByRaw("CASE status WHEN 'berjalan' THEN 1 WHEN 'tertahan' THEN 2 WHEN 'rencana' THEN 3 WHEN 'selesai' THEN 4 ELSE 5 END")->orderBy('target_date')->get();
        return view('projects.index', compact('projects'));
    }

    public function create(Request $request)
    {
        $this->requireManager();
        return view('projects.create', [
            'templates' => Template::orderBy('sequence')->get(),
            'wbsTemplates' => WbsTemplate::with('template')->orderBy('food_group')->orderBy('name')->get(),
            'projects' => Project::orderByDesc('id')->get(['id', 'code', 'name']),
            'suppliers' => Supplier::orderBy('name')->get(),
            'parties' => Party::with('users')->orderBy('sequence')->get(),
            'managers' => User::whereIn('role', ['admin', 'manajer'])->get(),
            'items' => Item::with('template')->orderBy('code')->limit(500)->get(),
            'selectedWbs' => $request->query('wbs'),
        ]);
    }

    public function store(Request $request, ProjectBuilder $builder, WbsTemplateService $wbs)
    {
        $this->requireManager();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:'.implode(',', array_keys(Project::TRIGGERS)),
            'need_description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'manager_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:'.implode(',', array_keys(Project::PRIORITIES)),
            'po_ref' => 'nullable|string|max:60',
            'eta' => 'nullable|date',
            'start_date' => 'required|date',
            'target_date' => 'required|date|after_or_equal:start_date',
            'items' => 'array', 'items.*' => 'exists:items,id',
            'qty' => 'array',
            'pic' => 'array',
            'source' => 'required|in:wbs,otomatis,proyek',
            'wbs_template_id' => 'required_if:source,wbs|nullable|exists:wbs_templates,id',
            'source_project_id' => 'required_if:source,proyek|nullable|exists:projects,id',
            'skip_covered' => 'nullable|boolean',
        ]);
        $year = date('Y', strtotime($data['start_date']));
        $next = Project::where('code', 'like', "PRJ-$year-%")->count() + 1;
        do {
            $code = sprintf('PRJ-%s-%03d', $year, $next++);
        } while (Project::where('code', $code)->exists());

        $project = Project::create([
            'code' => $code, 'name' => $data['name'], 'trigger' => $data['trigger'], 'need_description' => $data['need_description'] ?? null,
            'template_id' => $data['template_id'] ?? null, 'supplier_id' => $data['supplier_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? auth()->id(), 'priority' => $data['priority'], 'po_ref' => $data['po_ref'] ?? null,
            'eta' => $data['eta'] ?? null, 'etd' => isset($data['eta']) ? date('Y-m-d', strtotime($data['eta'].' -18 days')) : null,
            'start_date' => $data['start_date'], 'target_date' => $data['target_date'], 'status' => 'rencana',
        ]);
        $items = collect($data['items'] ?? [])->mapWithKeys(fn ($id) => [$id => ['qty_kg' => (float) ($data['qty'][$id] ?? 0)]]);
        $project->items()->sync($items->all());
        if (! $project->template_id && $project->items()->exists()) {
            $project->update(['template_id' => $project->items()->first()->template_id]);
        }
        foreach (Party::all() as $party) {
            $uid = $data['pic'][$party->id] ?? $party->users()->where('active', true)->value('id');
            ProjectPic::create(['project_id' => $project->id, 'party_id' => $party->id, 'user_id' => $uid ?: null]);
        }

        if ($data['source'] === 'wbs') {
            $wbs->applyTo($project, WbsTemplate::findOrFail($data['wbs_template_id']), $request->boolean('skip_covered', true));
        } elseif ($data['source'] === 'proyek') {
            $source = Project::findOrFail($data['source_project_id']);
            $tpl = new WbsTemplate(['name' => 'Salinan '.$source->code, 'payload' => $wbs->payloadFromProject($source)]);
            $tpl->id = null;
            $this->applyAdHoc($wbs, $project, $tpl, $request->boolean('skip_covered', true));
        } else {
            $builder->build($project);
        }
        ActivityLog::record('proyek_dibuat', 'Proyek dibuat ('.['wbs' => 'salin dari template WBS', 'otomatis' => 'disusun otomatis dari item code', 'proyek' => 'salin dari proyek lain'][$data['source']].')', $project);

        return redirect()->route('projects.show', $project)->with('ok', 'Proyek '.$project->code.' dibuat. Periksa jadwal di Gantt lalu simpan baseline.');
    }

    /** Salin dari proyek lain memakai jalur yang sama dengan template tanpa menyimpan template. */
    private function applyAdHoc(WbsTemplateService $wbs, Project $project, WbsTemplate $tpl, bool $skip): void
    {
        $tpl->code = 'TMP';
        $tpl->save();
        $wbs->applyTo($project, $tpl, $skip);
        $project->update(['wbs_template_id' => null]);
        $tpl->delete();
    }

    public function show(Request $request, Project $project, Forecaster $forecaster)
    {
        $project->load(['template', 'supplier', 'manager', 'items.template', 'pics.party', 'pics.user', 'pics.backup', 'licenses.permitType', 'wbsTemplate']);
        $tasks = $project->tasks()->with('assignee', 'party', 'permitType', 'regulations', 'predecessors:id,wbs,name', 'documents', 'attachments')
            ->withCount(['comments', 'attachments'])->get();
        $tab = $request->query('tab', 'ringkasan');
        $forecast = $forecaster->projectForecast($project);
        $comments = $project->comments()->with('user', 'task', 'document', 'mentions.user', 'mentions.party')->limit(40)->get();
        $memos = $project->memos()->with('fromParty', 'toParty', 'fromUser', 'toUser')->get();
        $logs = $project->logs()->with('user')->limit(40)->get();
        $docs = $tasks->flatMap->documents;
        $attachments = $tasks->flatMap->attachments;

        return view('projects.show', [
            'project' => $project, 'tasks' => $tasks, 'tab' => $tab, 'forecast' => $forecast, 'comments' => $comments,
            'memos' => $memos, 'logs' => $logs, 'docs' => $docs, 'attachments' => $attachments,
            'users' => User::where('active', true)->orderBy('name')->get(),
            'parties' => Party::orderBy('sequence')->get(),
            'wbsTemplates' => WbsTemplate::orderBy('name')->get(),
            'regulations' => Regulation::orderBy('name')->get(),
        ]);
    }

    public function edit(Project $project)
    {
        $this->requireManager();
        return view('projects.edit', ['project' => $project, 'suppliers' => Supplier::orderBy('name')->get(),
            'managers' => User::whereIn('role', ['admin', 'manajer'])->get(), 'templates' => Template::orderBy('sequence')->get()]);
    }

    public function update(Request $request, Project $project, Scheduler $scheduler)
    {
        $this->requireManager();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'need_description' => 'nullable|string',
            'status' => 'required|in:'.implode(',', array_keys(Project::STATUSES)),
            'priority' => 'required|in:'.implode(',', array_keys(Project::PRIORITIES)),
            'supplier_id' => 'nullable|exists:suppliers,id', 'manager_id' => 'nullable|exists:users,id',
            'template_id' => 'nullable|exists:templates,id',
            'po_ref' => 'nullable|string|max:60', 'eta' => 'nullable|date', 'start_date' => 'required|date',
            'target_date' => 'required|date|after_or_equal:start_date',
        ]);
        $dateChanged = $project->start_date->toDateString() !== $data['start_date'];
        $project->update($data);
        if ($dateChanged) $scheduler->apply($project->fresh(), false);
        ActivityLog::record('proyek_diubah', 'Data proyek diperbarui', $project);
        return redirect()->route('projects.show', $project)->with('ok', 'Proyek diperbarui.');
    }

    public function destroy(Project $project)
    {
        $this->requireAdmin();
        $project->delete();
        return redirect()->route('projects.index')->with('ok', 'Proyek dihapus.');
    }

    public function reschedule(Project $project, Scheduler $scheduler)
    {
        $this->requireManager();
        $scheduler->apply($project, true);
        ActivityLog::record('jadwal_ulang', 'Jadwal dihitung ulang dari hari ini (sisa durasi & dependensi)', $project);
        return back()->with('ok', 'Jadwal dihitung ulang dari kondisi hari ini. Baseline tidak berubah.');
    }

    public function baseline(Project $project, Scheduler $scheduler)
    {
        $this->requireManager();
        foreach ($project->tasks as $t) $t->update(['baseline_start' => $t->planned_start, 'baseline_end' => $t->planned_end]);
        $project->update(['baseline_end' => $project->planned_end]);
        ActivityLog::record('baseline', 'Baseline disimpan: selesai '.$project->planned_end?->format('d/m/Y'), $project);
        return back()->with('ok', 'Baseline disimpan. Perbandingan rencana vs baseline tampil di Gantt.');
    }

    public function updatePics(Request $request, Project $project)
    {
        $this->requireManager();
        $data = $request->validate(['pic' => 'array', 'backup' => 'array', 'reassign' => 'nullable|boolean']);
        foreach (Party::all() as $party) {
            $pic = ProjectPic::firstOrNew(['project_id' => $project->id, 'party_id' => $party->id]);
            $old = $pic->user_id;
            $pic->user_id = $data['pic'][$party->id] ?? null;
            $pic->backup_user_id = $data['backup'][$party->id] ?? null;
            $pic->save();
            if ($request->boolean('reassign') && $old != $pic->user_id) {
                Task::where('project_id', $project->id)->where('party_id', $party->id)->where('status', '!=', 'selesai')
                    ->update(['assignee_id' => $pic->user_id]);
            }
        }
        ActivityLog::record('pic_diubah', 'PIC per bagian diperbarui'.($request->boolean('reassign') ? ' dan tugas terbuka dialihkan' : ''), $project);
        return back()->with('ok', 'PIC diperbarui.');
    }

    public function copyTemplate(Request $request, Project $project, WbsTemplateService $wbs)
    {
        $this->requireManager();
        $data = $request->validate(['wbs_template_id' => 'required|exists:wbs_templates,id']);
        abort_if($project->workTasks()->where('status', '!=', 'belum')->exists(), 422,
            'WBS tidak bisa diganti karena sudah ada kegiatan yang berjalan/selesai.');
        $res = $wbs->applyTo($project, WbsTemplate::find($data['wbs_template_id']), $request->boolean('skip_covered', true));
        return redirect()->route('projects.show', [$project, 'tab' => 'gantt'])
            ->with('ok', "WBS disalin dari template: {$res['tasks']} baris, {$res['skipped']} izin dilewati karena sudah dimiliki.");
    }
}
