<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskDependency extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function task() { return $this->belongsTo(Task::class); }
    public function dependsOn() { return $this->belongsTo(Task::class, 'depends_on_id'); }
}
