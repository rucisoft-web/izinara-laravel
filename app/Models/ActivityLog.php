<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];

    public function user() { return $this->belongsTo(User::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }

    public static function record(string $action, ?string $detail = null, ?Project $project = null, ?Task $task = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'project_id' => $project?->id ?? $task?->project_id,
            'task_id' => $task?->id,
            'action' => $action,
            'detail' => $detail,
        ]);
    }
}
