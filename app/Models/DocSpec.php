<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocSpec extends Model
{
    protected $guarded = [];
    protected $casts = ['check_expiry' => 'boolean'];

    public function step() { return $this->belongsTo(PermitTypeStep::class, 'permit_type_step_id'); }
    public function verifier() { return $this->belongsTo(Party::class, 'verifier_party_id'); }
}
