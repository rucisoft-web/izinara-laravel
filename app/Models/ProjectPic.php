<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPic extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function project() { return $this->belongsTo(Project::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function backup() { return $this->belongsTo(User::class, 'backup_user_id'); }
}
