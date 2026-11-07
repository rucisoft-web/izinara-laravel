<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];
    protected $casts = [
        'planned_start' => 'date', 'planned_end' => 'date', 'baseline_start' => 'date', 'baseline_end' => 'date',
        'actual_start' => 'date', 'actual_end' => 'date', 'is_critical' => 'boolean',
    ];

    public const STATUSES = [
        'belum' => 'Belum mulai',
        'berjalan' => 'Berjalan',
        'menunggu' => 'Menunggu instansi',
        'terhambat' => 'Terhambat',
        'selesai' => 'Selesai',
    ];

    public const KINDS = ['phase' => 'Tahap', 'package' => 'Paket izin', 'task' => 'Tugas', 'milestone' => 'Milestone'];

    public function project() { return $this->belongsTo(Project::class); }
    public function parent() { return $this->belongsTo(Task::class, 'parent_id'); }
    public function children() { return $this->hasMany(Task::class, 'parent_id')->orderBy('sequence'); }
    public function permitType() { return $this->belongsTo(PermitType::class); }
    public function step() { return $this->belongsTo(PermitTypeStep::class, 'permit_type_step_id'); }
    public function party() { return $this->belongsTo(Party::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assignee_id'); }
    public function regulations() { return $this->belongsToMany(Regulation::class)->withPivot('article'); }
    public function comments() { return $this->hasMany(TaskComment::class)->whereNull('memo_id')->latest(); }
    public function memos() { return $this->hasMany(Memo::class)->latest(); }
    public function documents() { return $this->hasMany(TaskDocument::class); }
    public function attachments() { return $this->hasMany(Attachment::class)->latest(); }
    public function dependencies() { return $this->hasMany(TaskDependency::class); }
    public function predecessors() { return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_id')->withPivot('lag_days'); }
    public function successors() { return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_id', 'task_id'); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function isOverdue(): bool
    {
        return $this->status !== 'selesai' && $this->planned_end && $this->planned_end->lt(today());
    }

    /** Referensi peraturan untuk kegiatan ini: milik tugas, atau warisan dari jenis izin. */
    public function effectiveRegulations()
    {
        if ($this->relationLoaded('regulations') ? $this->regulations->isNotEmpty() : $this->regulations()->exists()) {
            return $this->regulations;
        }
        return $this->permitType?->regulations ?? collect();
    }
}
