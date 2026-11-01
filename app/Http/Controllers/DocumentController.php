<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskDocument;
use App\Services\Communication;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /** Tambah dokumen (non-wajib) ke kegiatan. */
    public function store(Request $request, Task $task)
    {
        $this->requireTaskEdit($task);
        $data = $request->validate(['name' => 'required|string|max:255', 'expiry_date' => 'nullable|date']);
        TaskDocument::create($data + ['task_id' => $task->id, 'status' => 'belum']);
        return back()->with('ok', 'Dokumen ditambahkan. Unggah file-nya lalu ajukan verifikasi.');
    }

    /** Ajukan verifikasi: cek otomatis ada file & masa berlaku. */
    public function submit(TaskDocument $document, Communication $comm)
    {
        $this->requireTaskEdit($document->task);
        $errors = [];
        if (! $document->attachments()->exists()) $errors[] = 'belum ada file yang diunggah';
        if ($document->attachments()->where('size', 0)->exists()) $errors[] = 'ada file kosong';
        $ref = $document->task->project->eta ?? $document->task->project->target_date;
        if ($document->spec?->check_expiry && (! $document->expiry_date || $document->expiry_date->lt($ref))) {
            $errors[] = 'masa berlaku dokumen harus diisi dan masih berlaku pada '.$ref->format('d/m/Y');
        }
        if ($errors) return back()->withErrors(['doc' => 'Belum bisa diajukan: '.implode('; ', $errors).'.']);
        $document->update(['status' => 'diajukan', 'note' => null]);
        $verifierParty = $document->spec?->verifier ?? $document->task->party;
        foreach ($verifierParty?->users()->where('active', true)->get() ?? [] as $u) {
            if ($u->id !== auth()->id()) {
                $comm->notify($u, auth()->user(), 'doc_submitted', 'Dokumen perlu verifikasi: '.$document->name,
                    $document->task->name, route('tasks.show', $document->task).'#doc-'.$document->id);
            }
        }
        ActivityLog::record('dokumen_diajukan', $document->name, null, $document->task);
        return back()->with('ok', 'Dokumen diajukan untuk verifikasi.');
    }

    public function verify(Request $request, TaskDocument $document, Communication $comm)
    {
        $user = auth()->user();
        $verifierParty = $document->spec?->verifier_party_id;
        abort_unless($user->isManager() || ($verifierParty && $user->party_id === $verifierParty)
            || (! $verifierParty && $user->party_id === $document->task->party_id), 403, 'Bukan bagian verifikator dokumen ini.');
        $data = $request->validate(['decision' => 'required|in:terverifikasi,ditolak', 'note' => 'required_if:decision,ditolak|nullable|string|max:2000']);
        $document->update(['status' => $data['decision'], 'note' => $data['note'] ?? null, 'verified_by' => $user->id, 'verified_at' => now()]);
        if ($data['decision'] === 'ditolak') {
            $task = $document->task;
            $comm->comment($user, 'Dokumen "'.$document->name.'" ditolak: '.$data['note'], ['task_document_id' => $document->id, 'task_id' => $task->id],
                array_filter([$task->assignee_id, $document->attachments()->latest()->value('uploaded_by')]), [], 'perlu_tindakan');
            $comm->notify($task->assignee, $user, 'doc_rejected', 'Dokumen ditolak: '.$document->name, $data['note'], route('tasks.show', $task).'#doc-'.$document->id);
        }
        ActivityLog::record('dokumen_'.$data['decision'], $document->name.($data['note'] ? ': '.$data['note'] : ''), null, $document->task);
        return back()->with('ok', 'Dokumen '.($data['decision'] === 'ditolak' ? 'ditolak, pengunggah mendapat notifikasi perbaikan.' : 'terverifikasi.'));
    }
}
