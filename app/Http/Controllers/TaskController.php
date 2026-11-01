<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\License;
use App\Models\Party;
use App\Models\Project;
use App\Models\Regulation;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Services\Communication;
use App\Services\Scheduler;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function mine(Request $request)
    {
        $user = auth()->user();
        $scope = $request->query('lingkup', 'saya');
        $q = Task::with('project', 'party', 'permitType', 'assignee')->where('kind', 'task')->where('status', '!=', 'selesai')
            ->whereHas('project', fn ($p) => $p->whereIn('status', ['rencana', 'berjalan', 'tertahan']));
        if ($scope === 'bagian' && $user->party_id) $q->where('party_id', $user->party_id);
        else $q->where('assignee_id', $user->id);
        $tasks = $q->orderBy('planned_start')->get();
        return view('tasks.mine', compact('tasks', 'scope'));
    }

    public function show(Task $task)
    {
        $task->load(['project.pics', 'parent', 'party', 'assignee', 'permitType.regulations', 'step.docSpecs', 'regulations',
            'predecessors', 'successors', 'documents.spec.verifier', 'documents.attachments.uploader', 'documents.verifier',
            'documents.comments.user', 'attachments.uploader', 'comments.user', 'comments.mentions.user', 'comments.mentions.party',
            'comments.document', 'memos.toParty', 'memos.fromParty']);
        $siblings = Task::where('project_id', $task->project_id)->whereIn('kind', ['task', 'milestone'])->where('id', '!=', $task->id)
            ->orderBy('sequence')->get(['id', 'wbs', 'name']);
        return view('tasks.show', [
            'task' => $task, 'siblings' => $siblings,
            'users' => User::where('active', true)->orderBy('name')->get(),
            'parties' => Party::orderBy('sequence')->get(),
            'regulations' => Regulation::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Project $project, Scheduler $scheduler)
    {
        $this->requireManager();
        $data = $request->validate([
            'name' => 'required|string|max:255', 'kind' => 'required|in:task,milestone,package',
            'parent_id' => 'nullable|exists:tasks,id', 'party_id' => 'nullable|exists:parties,id',
            'assignee_id' => 'nullable|exists:users,id', 'duration_days' => 'required|integer|min:0|max:720',
            'depends_on' => 'array', 'depends_on.*' => 'exists:tasks,id', 'instructions' => 'nullable|string',
            'regulations' => 'array', 'regulations.*' => 'exists:regulations,id',
        ]);
        $parent = isset($data['parent_id']) ? Task::find($data['parent_id']) : null;
        $seq = $parent ? (int) $project->tasks()->where('parent_id', $parent->id)->max('sequence') + 1 : (int) $project->tasks()->max('sequence') + 10;
        $wbs = $parent ? $parent->wbs.'.'.($project->tasks()->where('parent_id', $parent->id)->count() + 1)
            : ($data['kind'] === 'milestone' ? 'M'.($project->milestones()->count() + 1) : (string) ($project->packages()->count() + 2));
        $task = Task::create([
            'project_id' => $project->id, 'parent_id' => $parent?->id, 'kind' => $data['kind'], 'wbs' => $wbs,
            'sequence' => $seq, 'name' => $data['name'], 'party_id' => $data['party_id'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? ($data['party_id'] ?? null ? $project->picFor((int) $data['party_id'])?->id : null),
            'duration_days' => $data['kind'] === 'milestone' ? 0 : max(1, $data['duration_days']),
            'instructions' => $data['instructions'] ?? null, 'permit_type_id' => $parent?->permit_type_id, 'status' => 'belum',
        ]);
        foreach ($data['depends_on'] ?? [] as $d) TaskDependency::firstOrCreate(['task_id' => $task->id, 'depends_on_id' => $d]);
        $task->regulations()->sync($data['regulations'] ?? []);
        $scheduler->apply($project->fresh(), false);
        ActivityLog::record('tugas_ditambah', $task->wbs.' '.$task->name, $project, $task);
        return redirect()->route('tasks.show', $task)->with('ok', 'Kegiatan ditambahkan dan jadwal dihitung ulang.');
    }

    public function update(Request $request, Task $task, Scheduler $scheduler, Communication $comm)
    {
        $this->requireTaskEdit($task);
        $user = auth()->user();
        $rules = [
            'status' => 'required|in:'.implode(',', array_keys(Task::STATUSES)),
            'progress' => 'required|integer|min:0|max:100',
            'actual_start' => 'nullable|date', 'actual_end' => 'nullable|date|after_or_equal:actual_start',
            'blocker_note' => 'nullable|string',
        ];
        if ($user->isManager()) {
            $rules += ['name' => 'required|string|max:255', 'assignee_id' => 'nullable|exists:users,id',
                'party_id' => 'nullable|exists:parties,id', 'duration_days' => 'required|integer|min:0|max:720',
                'instructions' => 'nullable|string', 'deliverable' => 'nullable|string|max:255',
                'depends_on' => 'array', 'depends_on.*' => 'exists:tasks,id'];
        }
        $data = $request->validate($rules);
        if ($data['status'] === 'selesai') {
            $mandatoryPending = $task->documents()->whereNotNull('doc_spec_id')->where('status', '!=', 'terverifikasi')->count();
            if ($mandatoryPending) {
                return back()->withErrors(['status' => "Masih ada $mandatoryPending dokumen wajib yang belum terverifikasi."]);
            }
            $data['progress'] = 100;
            $data['actual_end'] = $data['actual_end'] ?? today()->toDateString();
        }
        if (in_array($data['status'], ['berjalan', 'menunggu', 'terhambat', 'selesai']) && empty($data['actual_start'])) {
            $data['actual_start'] = $task->actual_start?->toDateString() ?? today()->toDateString();
        }
        if ($data['status'] === 'belum') { $data['actual_start'] = null; $data['actual_end'] = null; $data['progress'] = 0; }
        $deps = $data['depends_on'] ?? null;
        unset($data['depends_on']);
        $oldAssignee = $task->assignee_id;
        $oldStatus = $task->status;
        $task->update($data);
        if ($deps !== null && $user->isManager()) {
            $task->dependencies()->delete();
            foreach ($deps as $d) if ((int) $d !== $task->id) TaskDependency::create(['task_id' => $task->id, 'depends_on_id' => $d]);
        }
        if ($task->assignee_id && $task->assignee_id !== $oldAssignee) {
            $comm->notify($task->assignee, $user, 'task_assigned', 'Anda ditunjuk sebagai PIC: '.$task->name, $task->project->name, route('tasks.show', $task));
        }
        $scheduler->apply($task->project->fresh(), false);
        ActivityLog::record('tugas_diubah', $task->wbs.' '.$task->name.': '.Task::STATUSES[$oldStatus].' → '.$task->statusLabel().' ('.$task->progress.'%)', null, $task);

        $msg = 'Kegiatan diperbarui.';
        if ($task->status === 'selesai' && $task->deliverable && $task->permitType && $task->parent
            && $task->parent->children()->where('status', '!=', 'selesai')->doesntExist() && $task->permitType->scope !== 'shipment') {
            $msg .= ' Paket izin selesai: catat nomor izin di bagian "Izin terbit" agar masuk register.';
        }
        return redirect()->route('tasks.show', $task)->with('ok', $msg);
    }

    /** Geser jadwal dari Gantt (seret batang). */
    public function updateDates(Request $request, Task $task, Scheduler $scheduler)
    {
        $this->requireManager();
        $data = $request->validate(['start' => 'required|date', 'end' => 'required|date|after_or_equal:start']);
        if ($task->kind === 'task') {
            $dur = (int) \Carbon\Carbon::parse($data['start'])->diffInDays($data['end']) + 1;
            $task->update(['duration_days' => $dur, 'not_before' => $data['start']]);
            $scheduler->apply($task->project->fresh(), false);
            ActivityLog::record('jadwal_digeser', $task->wbs.' digeser ke '.$data['start'].' s/d '.$data['end'].' dari Gantt', null, $task);
        }
        return response()->json(['ok' => true]);
    }

    public function updateRegulations(Request $request, Task $task)
    {
        $this->requireTaskEdit($task);
        $data = $request->validate(['regulations' => 'array', 'regulations.*' => 'exists:regulations,id', 'article' => 'array']);
        $task->regulations()->sync(collect($data['regulations'] ?? [])->mapWithKeys(fn ($id) => [$id => ['article' => $data['article'][$id] ?? null]])->all());
        ActivityLog::record('peraturan_diubah', 'Referensi peraturan kegiatan diperbarui', null, $task);
        return back()->with('ok', 'Referensi peraturan diperbarui.');
    }

    public function destroy(Task $task, Scheduler $scheduler)
    {
        $this->requireManager();
        $project = $task->project;
        $task->delete();
        $scheduler->apply($project->fresh(), false);
        return redirect()->route('projects.show', [$project, 'tab' => 'wbs'])->with('ok', 'Kegiatan dihapus.');
    }

    /** Catat izin terbit dari paket izin → register izin. */
    public function issueLicense(Request $request, Task $task)
    {
        $this->requireTaskEdit($task);
        $pkg = $task->kind === 'package' ? $task : $task->parent;
        abort_unless($pkg && $pkg->permit_type_id, 422);
        $data = $request->validate(['number' => 'required|string|max:255', 'issued_at' => 'required|date',
            'expires_at' => 'nullable|date|after:issued_at', 'quota_kg' => 'nullable|numeric|min:0']);
        $project = $task->project;
        $type = $pkg->permitType;
        if (empty($data['expires_at']) && $type->validity_months) {
            $data['expires_at'] = \Carbon\Carbon::parse($data['issued_at'])->addMonths($type->validity_months)->toDateString();
        }
        $lic = License::create($data + ['permit_type_id' => $type->id, 'project_id' => $project->id,
            'supplier_id' => $type->scope === 'supplier' ? $project->supplier_id : null]);
        if ($type->scope === 'product') $lic->items()->sync($project->items->pluck('id'));
        ActivityLog::record('izin_terbit', $type->name.' nomor '.$lic->number, $project, $pkg);
        return back()->with('ok', 'Izin dicatat di register izin.');
    }
}
