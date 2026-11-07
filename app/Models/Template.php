<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $guarded = [];

    public const GROUPS = [
        'psah' => 'PSAH – Pangan Segar Asal Hewan',
        'psai' => 'PSAI – Pangan Segar Asal Ikan',
        'psat' => 'PSAT – Pangan Segar Asal Tumbuhan',
        'ml' => 'BPOM ML – pangan olahan impor',
        'md' => 'BPOM MD – pangan olahan dalam negeri',
        'halal' => 'Sertifikasi Halal – SHLN & sertifikat halal BPJPH',
        'non_food' => 'Non-pangan',
    ];

    public function lines() { return $this->hasMany(TemplateLine::class)->orderBy('sequence'); }
    public function items() { return $this->hasMany(Item::class); }
    public function groupLabel(): string { return self::GROUPS[$this->food_group] ?? $this->food_group; }
}
