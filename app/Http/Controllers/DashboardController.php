<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Memo;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Template;
use App\Services\Forecaster;

class DashboardController extends Controller
{
    public function index(Forecaster $forecaster)
    {
        $projects = Project::with('workTasks', 'manager', 'template')->orderBy('target_date')->get();
        $open = $projects->filter->isOpen();
        $tasksOpen = Task::with('project', 'assignee', 'party')->where('kind', 'task')->where('status', '!=', 'selesai')
            ->whereHas('project', fn ($q) => $q->whereIn('status', ['berjalan', 'tertahan', 'rencana']))->get();
        $overdue = $tasksOpen->filter->isOverdue()->sortBy('planned_end');
        $blocked = $tasksOpen->where('status', 'terhambat');
        $milestones = Task::with('project')->where('kind', 'milestone')->where('status', '!=', 'selesai')
            ->whereBetween('planned_end', [today(), today()->addDays(45)])->orderBy('planned_end')->limit(8)->get();
        $licenses = License::with('permitType')->whereNotNull('expires_at')->where('expires_at', '<=', today()->addDays(90))
            ->orderBy('expires_at')->get();
        $memosOpen = Memo::with('fromParty', 'toParty')->where('status', 'terbuka')->orderBy('due_date')->get();
        $forecast = $forecaster->portfolio();

        $byGroup = collect(Template::GROUPS)->map(fn ($label, $code) => [
            'label' => explode(' – ', $label)[0],
            'open' => $open->filter(fn ($p) => $p->template?->food_group === $code)->count(),
            'done' => $projects->where('status', 'selesai')->filter(fn ($p) => $p->template?->food_group === $code)->count(),
        ])->filter(fn ($g) => $g['open'] + $g['done'] > 0);

        $byParty = Party::orderBy('sequence')->get()->map(fn ($p) => [
            'label' => $p->shortName(),
            'open' => $tasksOpen->where('party_id', $p->id)->count(),
            'overdue' => $overdue->where('party_id', $p->id)->count(),
        ]);

        $memoStats = Party::orderBy('sequence')->get()->map(function ($p) {
            $memos = Memo::where('to_party_id', $p->id)->get();
            $answered = $memos->whereNotNull('first_response_at');
            $withDue = $memos->filter(fn ($m) => $m->due_date && ($m->first_response_at || $m->due_date->lt(today())));
            return ['party' => $p, 'count' => $memos->count(),
                'avg_hours' => $answered->count() ? round($answered->avg(fn ($m) => $m->responseHours()), 1) : null,
                'on_time' => $withDue->count() ? (int) round($withDue->filter(fn ($m) => $m->onTime())->count() / $withDue->count() * 100) : null];
        });

        return view('dashboard', compact('projects', 'open', 'overdue', 'blocked', 'milestones', 'licenses',
            'memosOpen', 'forecast', 'byGroup', 'byParty', 'memoStats', 'tasksOpen'));
    }
}
