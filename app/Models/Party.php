<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $guarded = [];

    public function users() { return $this->hasMany(User::class); }
    public function tasks() { return $this->hasMany(Task::class); }

    public function shortName(): string
    {
        return trim(preg_replace('/\s*\(.*\)$/', '', $this->name));
    }
}
