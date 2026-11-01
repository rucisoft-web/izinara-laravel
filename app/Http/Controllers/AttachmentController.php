<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Lampiran digital per kegiatan / dokumen: PDF, Excel, Word, gambar, ZIP, email, dll. Banyak file sekaligus. */
class AttachmentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->requireTaskEdit($task);
        $request->validate([
            'files' => 'required|array|max:20',
            'files.*' => 'file|max:'.Attachment::MAX_KB.'|mimes:'.implode(',', Attachment::ALLOWED),
            'task_document_id' => 'nullable|exists:task_documents,id',
            'description' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
        ], ['files.*.mimes' => 'Jenis file tidak diizinkan. Boleh: '.implode(', ', Attachment::ALLOWED).'.']);

        $doc = $request->task_document_id ? TaskDocument::where('task_id', $task->id)->findOrFail($request->task_document_id) : null;
        $n = 0;
        foreach ($request->file('files') as $file) {
            $path = $file->store('lampiran/'.$task->project_id.'/'.$task->id, 'local');
            Attachment::create([
                'task_id' => $task->id, 'task_document_id' => $doc?->id, 'path' => $path,
                'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(),
                'extension' => strtolower($file->getClientOriginalExtension()), 'size' => $file->getSize(),
                'description' => $request->description, 'uploaded_by' => auth()->id(),
            ]);
            $n++;
        }
        if ($doc) {
            $doc->update(['uploaded_by' => auth()->id(), 'original_name' => $file->getClientOriginalName(),
                'expiry_date' => $request->expiry_date ?? $doc->expiry_date,
                'status' => $doc->status === 'ditolak' || $doc->status === 'belum' ? 'belum' : $doc->status]);
        }
        if ($task->status === 'belum') $task->update(['status' => 'berjalan', 'actual_start' => today()]);
        ActivityLog::record('lampiran_diunggah', $n.' file'.($doc ? ' untuk '.$doc->name : ''), null, $task);
        return back()->with('ok', $n.' file diunggah.'.($doc ? ' Tekan "Ajukan verifikasi" bila dokumen sudah lengkap.' : ''));
    }

    public function show(Attachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return response()->file(Storage::disk('local')->path($attachment->path), [
            'Content-Type' => $attachment->mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
        ]);
    }

    public function download(Attachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Attachment $attachment)
    {
        $this->requireTaskEdit($attachment->task);
        $attachment->delete();
        return back()->with('ok', 'Lampiran dihapus.');
    }
}
