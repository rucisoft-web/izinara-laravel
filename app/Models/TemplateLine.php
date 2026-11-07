<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateLine extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['mandatory' => 'boolean'];

    public function template() { return $this->belongsTo(Template::class); }
    public function permitType() { return $this->belongsTo(PermitType::class); }
}
