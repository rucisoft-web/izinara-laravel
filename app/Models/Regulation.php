<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regulation extends Model
{
    protected $guarded = [];
    protected $casts = ['checked_at' => 'date'];

    public const STATUSES = [
        'berlaku' => 'Berlaku',
        'berlaku_diubah' => 'Berlaku (dengan perubahan)',
        'perlu_konfirmasi' => 'Perlu konfirmasi versi terbaru',
        'dicabut' => 'Dicabut / diganti',
    ];

    public function replacedBy() { return $this->belongsTo(Regulation::class, 'replaced_by_id'); }
    public function permitTypes() { return $this->belongsToMany(PermitType::class); }
    public function steps() { return $this->belongsToMany(PermitTypeStep::class)->withPivot('article'); }
    public function tasks() { return $this->belongsToMany(Task::class)->withPivot('article'); }

    public function label(): string { return $this->name.' tentang '.$this->title; }
    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    /** Peraturan yang berlaku saat ini: bila dicabut, ikuti penggantinya. */
    public function current(): Regulation
    {
        $reg = $this;
        $guard = 0;
        while ($reg->status === 'dicabut' && $reg->replacedBy && $guard++ < 5) {
            $reg = $reg->replacedBy;
        }
        return $reg;
    }
}
