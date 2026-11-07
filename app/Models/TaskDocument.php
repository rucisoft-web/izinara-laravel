<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDocument extends Model
{
    protected $guarded = [];
    protected $casts = ['expiry_date' => 'date', 'verified_at' => 'datetime'];

    public const STATUSES = [
        'belum' => 'Belum diunggah',
        'diajukan' => 'Menunggu verifikasi',
        'terverifikasi' => 'Terverifikasi',
        'ditolak' => 'Ditolak',
    ];

    public function task() { return $this->belongsTo(Task::class); }
    public function spec() { return $this->belongsTo(DocSpec::class, 'doc_spec_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function attachments() { return $this->hasMany(Attachment::class); }
    public function comments() { return $this->hasMany(TaskComment::class)->latest(); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
}
