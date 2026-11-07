<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDocument;
use App\Services\Communication;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    private function validated(Request $request): array
    {
        return $request->validate([
            'body' => 'required|string|max:5000',
            'kind' => 'nullable|in:'.implode(',', array_keys(TaskComment::KINDS)),
            'mention_users' => 'array', 'mention_users.*' => 'exists:users,id',
            'mention_parties' => 'array', 'mention_parties.*' => 'exists:parties,id',
        ]);
    }

    private function post(Request $request, array $context, Communication $comm)
    {
        $this->requireNotViewer();
        $d = $this->validated($request);
        $comm->comment(auth()->user(), $d['body'], $context, $d['mention_users'] ?? [], $d['mention_parties'] ?? [], $d['kind'] ?? 'komentar');
        return back()->with('ok', 'Komentar terkirim. Pihak yang di-@mention mendapat notifikasi.');
    }

    public function storeTask(Request $request, Task $task, Communication $comm)
    {
        return $this->post($request, ['task_id' => $task->id], $comm);
    }

    public function storeDocument(Request $request, TaskDocument $document, Communication $comm)
    {
        return $this->post($request, ['task_document_id' => $document->id, 'task_id' => $document->task_id], $comm);
    }

    public function storeProject(Request $request, Project $project, Communication $comm)
    {
        return $this->post($request, ['project_id' => $project->id], $comm);
    }

    public function resolve(TaskComment $comment)
    {
        $this->requireNotViewer();
        $comment->update(['resolved_at' => $comment->resolved_at ? null : now()]);
        return back()->with('ok', $comment->resolved_at ? 'Ditandai selesai ditindaklanjuti.' : 'Dibuka kembali.');
    }
}
