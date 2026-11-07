<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = [];
    protected $casts = [
        'covered_permits' => 'array',
        'etd' => 'date', 'eta' => 'date', 'start_date' => 'date', 'target_date' => 'date',
        'baseline_end' => 'date', 'planned_end' => 'date', 'actual_end' => 'date',
    ];

    public const TRIGGERS = [
        'pengapalan' => 'Rencana pengapalan / impor',
        'produk_baru' => 'Produk baru',
        'perpanjangan' => 'Perpanjangan izin',
        'perubahan' => 'Perubahan data / izin',
    ];

    public const STATUSES = [
        'rencana' => 'Rencana',
        'berjalan' => 'Berjalan',
        'tertahan' => 'Tertahan',
        'selesai' => 'Selesai',
        'batal' => 'Batal',
    ];

    public const PRIORITIES = ['rendah' => 'Rendah', 'normal' => 'Normal', 'tinggi' => 'Tinggi', 'kritis' => 'Kritis'];

    public function template() { return $this->belongsTo(Template::class); }
    public function wbsTemplate() { return $this->belongsTo(WbsTemplate::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function manager() { return $this->belongsTo(User::class, 'manager_id'); }
    public function items() { return $this->belongsToMany(Item::class)->withPivot('qty_kg'); }
    public function pics() { return $this->hasMany(ProjectPic::class); }
    public function tasks() { return $this->hasMany(Task::class)->orderBy('sequence'); }
    public function workTasks() { return $this->hasMany(Task::class)->where('kind', 'task'); }
    public function milestones() { return $this->hasMany(Task::class)->where('kind', 'milestone')->orderBy('planned_end'); }
    public function packages() { return $this->hasMany(Task::class)->where('kind', 'package')->orderBy('sequence'); }
    public function licenses() { return $this->hasMany(License::class); }
    public function memos() { return $this->hasMany(Memo::class)->latest(); }
    public function comments() { return $this->hasMany(TaskComment::class)->whereNull('memo_id')->latest(); }
    public function logs() { return $this->hasMany(ActivityLog::class)->latest(); }

    public function triggerLabel(): string { return self::TRIGGERS[$this->trigger] ?? $this->trigger; }
    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }
    public function priorityLabel(): string { return self::PRIORITIES[$this->priority] ?? $this->priority; }

    public function isOpen(): bool { return in_array($this->status, ['rencana', 'berjalan', 'tertahan']); }

    /** Selisih hari rencana selesai terhadap tenggat (positif = terlambat). */
    public function delayDays(): int
    {
        $end = $this->actual_end ?? $this->planned_end;
        return $end && $this->target_date ? (int) $this->target_date->diffInDays($end, false) : 0;
    }

    /** Kesehatan proyek: hijau, kuning, merah. */
    public function health(): string
    {
        if ($this->status === 'selesai') return $this->delayDays() > 0 ? 'kuning' : 'hijau';
        if ($this->status === 'batal') return 'abu';
        $late = $this->delayDays();
        $overdue = $this->workTasks->where('status', '!=', 'selesai')
            ->filter(fn ($t) => $t->planned_end && $t->planned_end->lt(today()))->count();
        if ($late > 0 || $this->workTasks->where('status', 'terhambat')->count() > 0) return 'merah';
        if ($overdue > 0 || $late > -5) return 'kuning';
        return 'hijau';
    }

    public function picFor(int $partyId): ?User
    {
        return $this->pics->firstWhere('party_id', $partyId)?->user;
    }

    public function recalcProgress(): void
    {
        $tasks = $this->workTasks()->get();
        $total = max(1, $tasks->sum('duration_days'));
        $done = $tasks->sum(fn ($t) => $t->duration_days * $t->progress / 100);
        $this->progress = (int) round($done / $total * 100);
        $this->planned_end = $this->tasks()->max('planned_end');
        if ($tasks->count() && $tasks->every(fn ($t) => $t->status === 'selesai')) {
            $this->status = 'selesai';
            $this->actual_end = $this->actual_end ?? $tasks->max('actual_end') ?? today();
        } elseif ($this->status === 'rencana' && $tasks->contains(fn ($t) => in_array($t->status, ['berjalan', 'selesai']))) {
            $this->status = 'berjalan';
        }
        $this->save();
    }
}
