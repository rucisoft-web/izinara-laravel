<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = [];
    protected $casts = ['non_halal' => 'boolean'];

    public function template() { return $this->belongsTo(Template::class); }
    public function projects() { return $this->belongsToMany(Project::class)->withPivot('qty_kg'); }
    public function licenses() { return $this->belongsToMany(License::class); }
}
