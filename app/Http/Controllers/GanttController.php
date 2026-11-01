<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
use Illuminate\Http\Request;

class GanttController extends Controller
{
    /** Data Gantt satu proyek (frappe-gantt). */
    public function project(Project $project)
    {
        $tasks = $project->tasks()->with('assignee', 'party', 'predecessors:id')->get();
        return response()->json($tasks->filter(fn ($t) => $t->planned_start)->map(fn ($t) => [
            'id' => 't'.$t->id,
            'name' => trim($t->wbs.' '.$t->name),
            'start' => $t->planned_start->toDateString(),
            'end' => ($t->kind === 'milestone' ? $t->planned_end : $t->planned_end)->toDateString(),
            'progress' => $t->progress,
            'dependencies' => $t->predecessors->map(fn ($p) => 't'.$p->id)->implode(', '),
            'custom_class' => implode(' ', array_filter([
                'k-'.$t->kind, 's-'.$t->status, $t->is_critical ? 'kritis' : null, $t->isOverdue() ? 'lewat' : null,
            ])),
            'url' => route('tasks.show', $t),
            'pic' => $t->assignee?->name,
            'party' => $t->party?->shortName(),
            'status' => $t->statusLabel(),
            'baseline' => $t->baseline_start ? $t->baseline_start->format('d/m/Y').' – '.$t->baseline_end->format('d/m/Y') : null,
            'slack' => $t->slack_days,
            'kind' => $t->kind,
        ])->values());
    }

    /** Gantt portofolio: semua proyek + milestone. */
    public function portfolio(Request $request)
    {
        $q = Project::with(['milestones', 'template', 'manager']);
        if (! $request->boolean('semua')) $q->whereIn('status', ['rencana', 'berjalan', 'tertahan']);
        if ($g = $request->query('kelompok')) $q->whereHas('template', fn ($t) => $t->where('food_group', $g));
        $projects = $q->orderBy('start_date')->get();
        $rows = [];
        foreach ($projects as $p) {
            $rows[] = ['id' => 'p'.$p->id, 'name' => $p->code.' · '.$p->name, 'start' => $p->start_date->toDateString(),
                'end' => ($p->actual_end ?? $p->planned_end ?? $p->target_date)->toDateString(), 'progress' => $p->progress,
                'dependencies' => '', 'custom_class' => 'k-project h-'.$p->health(), 'url' => route('projects.show', [$p, 'tab' => 'gantt']),
                'pic' => $p->manager?->name, 'party' => $p->template?->name, 'status' => $p->statusLabel(),
                'baseline' => 'Tenggat '.$p->target_date->format('d/m/Y'), 'slack' => -$p->delayDays(), 'kind' => 'project'];
            foreach ($p->milestones as $m) {
                $rows[] = ['id' => 'm'.$m->id, 'name' => '   ◆ '.$m->name, 'start' => $m->planned_end->toDateString(),
                    'end' => $m->planned_end->toDateString(), 'progress' => $m->status === 'selesai' ? 100 : 0,
                    'dependencies' => '', 'custom_class' => 'k-milestone s-'.$m->status, 'url' => route('tasks.show', $m),
                    'pic' => null, 'party' => $p->code, 'status' => $m->statusLabel(), 'baseline' => null, 'slack' => $m->slack_days, 'kind' => 'milestone'];
            }
        }
        return view('gantt.portfolio', ['rows' => $rows, 'groups' => Template::GROUPS]);
    }
}
