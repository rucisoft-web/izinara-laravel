<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'username', 'email', 'password', 'party_id', 'role', 'job_title', 'phone', 'weekly_capacity', 'active',
    ];

    public const ROLES = [
        'admin' => 'Administrator',
        'manajer' => 'Manajer perizinan',
        'pic' => 'PIC / petugas bagian',
        'viewer' => 'Pemantau (baca saja)',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function tasks() { return $this->hasMany(Task::class, 'assignee_id'); }
    public function notices() { return $this->hasMany(Notice::class)->latest(); }
    public function managedProjects() { return $this->hasMany(Project::class, 'manager_id'); }

    public function isManager(): bool { return in_array($this->role, ['admin', 'manajer']); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function roleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    /** Boleh mengubah tugas: manajer, penanggung jawab tugas, atau anggota bagian tugas. */
    public function canEditTask(Task $task): bool
    {
        return $this->isManager() || $task->assignee_id === $this->id || ($task->party_id && $task->party_id === $this->party_id);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }
}
