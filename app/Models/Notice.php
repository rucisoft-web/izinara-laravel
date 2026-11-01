<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $guarded = [];
    protected $casts = ['read_at' => 'datetime'];

    public const TYPES = [
        'mention' => 'Disebut',
        'memo' => 'Memo masuk',
        'memo_reply' => 'Balasan memo',
        'doc_rejected' => 'Dokumen ditolak',
        'doc_submitted' => 'Dokumen perlu verifikasi',
        'task_assigned' => 'Tugas baru',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
}
