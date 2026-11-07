<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentMention extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function comment() { return $this->belongsTo(TaskComment::class, 'task_comment_id'); }
    public function user() { return $this->belongsTo(User::class); }
    public function party() { return $this->belongsTo(Party::class); }

    public function label(): string
    {
        return $this->user ? '@'.$this->user->username : '@'.$this->party?->code;
    }
}
