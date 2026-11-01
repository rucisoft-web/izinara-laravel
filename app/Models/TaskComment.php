<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $guarded = [];
    protected $casts = ['resolved_at' => 'datetime'];

    public const KINDS = ['komentar' => 'Komentar', 'perlu_tindakan' => 'Perlu tindakan', 'info' => 'Info'];

    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }
    public function document() { return $this->belongsTo(TaskDocument::class, 'task_document_id'); }
    public function memo() { return $this->belongsTo(Memo::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function mentions() { return $this->hasMany(CommentMention::class); }

    /** Teks dengan @mention disorot (aman dari HTML). */
    public function bodyHtml(): string
    {
        $html = nl2br(e($this->body));
        return preg_replace('/(^|\s)@([a-z0-9._-]+)/i', '$1<span class="mention">@$2</span>', $html);
    }
}
