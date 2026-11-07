<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function parent() { return $this->belongsTo(Supplier::class, 'parent_id'); }
    public function projects() { return $this->hasMany(Project::class); }
}
