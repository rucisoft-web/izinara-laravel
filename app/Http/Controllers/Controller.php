<?php

namespace App\Http\Controllers;

use App\Models\Task;

abstract class Controller
{
    protected function requireManager(): void
    {
        abort_unless(auth()->user()?->isManager(), 403, 'Hanya manajer perizinan atau administrator.');
    }

    protected function requireAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Hanya administrator.');
    }

    protected function requireTaskEdit(Task $task): void
    {
        abort_unless(auth()->user()?->canEditTask($task), 403, 'Anda bukan PIC kegiatan ini.');
    }

    protected function requireNotViewer(): void
    {
        abort_if(auth()->user()?->role === 'viewer', 403, 'Akun pemantau hanya bisa membaca.');
    }
}
