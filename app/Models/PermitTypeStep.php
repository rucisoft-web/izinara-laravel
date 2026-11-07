<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitTypeStep extends Model
{
    protected $guarded = [];
    protected $casts = ['is_submission' => 'boolean'];

    public function permitType() { return $this->belongsTo(PermitType::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function regulations() { return $this->belongsToMany(Regulation::class)->withPivot('article'); }
    public function docSpecs() { return $this->hasMany(DocSpec::class); }
}
