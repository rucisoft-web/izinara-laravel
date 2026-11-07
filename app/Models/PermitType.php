<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermitType extends Model
{
    protected $guarded = [];
    protected $casts = ['prerequisites' => 'array'];

    public const SCOPES = [
        'company' => 'Per perusahaan',
        'supplier' => 'Per pemasok/unit usaha asal',
        'product' => 'Per barang (item code)',
        'shipment' => 'Per pengapalan',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function steps() { return $this->hasMany(PermitTypeStep::class)->orderBy('sequence'); }
    public function regulations() { return $this->belongsToMany(Regulation::class); }
    public function licenses() { return $this->hasMany(License::class); }

    public function scopeLabel(): string { return self::SCOPES[$this->scope] ?? $this->scope; }
    public function standardDays(): int { return (int) $this->steps->sum('duration_days'); }
}
