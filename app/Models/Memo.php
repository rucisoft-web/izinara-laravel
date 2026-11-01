<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    protected $guarded = [];
    protected $casts = ['due_date' => 'date', 'first_response_at' => 'datetime', 'closed_at' => 'datetime'];

    public const STATUSES = ['terbuka' => 'Terbuka', 'dijawab' => 'Dijawab', 'selesai' => 'Selesai'];

    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }
    public function document() { return $this->belongsTo(TaskDocument::class, 'task_document_id'); }
    public function fromUser() { return $this->belongsTo(User::class, 'from_user_id'); }
    public function fromParty() { return $this->belongsTo(Party::class, 'from_party_id'); }
    public function toParty() { return $this->belongsTo(Party::class, 'to_party_id'); }
    public function toUser() { return $this->belongsTo(User::class, 'to_user_id'); }
    public function replies() { return $this->hasMany(TaskComment::class)->oldest(); }

    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function isOverdue(): bool
    {
        return $this->status === 'terbuka' && $this->due_date && $this->due_date->lt(today());
    }

    /** Waktu respons dalam jam (null bila belum dijawab). */
    public function responseHours(): ?float
    {
        return $this->first_response_at ? round($this->created_at->diffInMinutes($this->first_response_at) / 60, 1) : null;
    }

    public function onTime(): ?bool
    {
        if (! $this->due_date) return null;
        $at = $this->first_response_at;
        return $at ? $at->startOfDay()->lte($this->due_date) : ($this->due_date->gte(today()) ? null : false);
    }
}
