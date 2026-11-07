<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $guarded = [];
    protected $casts = ['issued_at' => 'date', 'expires_at' => 'date'];

    public function permitType() { return $this->belongsTo(PermitType::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function items() { return $this->belongsToMany(Item::class); }

    public function daysLeft(): ?int
    {
        return $this->expires_at ? (int) now()->startOfDay()->diffInDays($this->expires_at, false) : null;
    }

    public function state(): string
    {
        $d = $this->daysLeft();
        if ($d === null) return 'aktif';
        if ($d < 0) return 'kedaluwarsa';
        if ($d <= 90) return 'segera_habis';
        return 'aktif';
    }
}
