<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WbsTemplate extends Model
{
    protected $guarded = [];
    protected $casts = ['payload' => 'array'];

    public function template() { return $this->belongsTo(Template::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function projects() { return $this->hasMany(Project::class); }

    public function packages(): array { return array_values(array_filter($this->payload, fn ($n) => $n['kind'] === 'package')); }
    public function taskCount(): int { return count(array_filter($this->payload, fn ($n) => $n['kind'] === 'task')); }
    public function milestoneCount(): int { return count(array_filter($this->payload, fn ($n) => $n['kind'] === 'milestone')); }
    public function totalDays(): int { return (int) ($this->payload ? max(array_map(fn ($n) => ($n['offset'] ?? 0) + ($n['duration_days'] ?? 0), $this->payload)) : 0); }
    public function groupLabel(): string { return Template::GROUPS[$this->food_group] ?? '-'; }
}
