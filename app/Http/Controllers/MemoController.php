<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Services\Communication;
use Illuminate\Http\Request;

class MemoController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $box = $request->query('kotak', 'masuk');
        $q = Memo::with('fromParty', 'toParty', 'fromUser', 'toUser', 'project', 'task')->withCount('replies');
        if ($box === 'masuk') $q->where(fn ($w) => $w->where('to_user_id', $user->id)->orWhere('to_party_id', $user->party_id));
        elseif ($box === 'keluar') $q->where('from_user_id', $user->id);
        if ($s = $request->query('status')) $q->where('status', $s);
        $memos = $q->orderByRaw("CASE status WHEN 'terbuka' THEN 1 WHEN 'dijawab' THEN 2 ELSE 3 END")->orderBy('due_date')->latest()->get();
        return view('memos.index', ['memos' => $memos, 'box' => $box, 'parties' => Party::orderBy('sequence')->get(),
            'users' => User::where('active', true)->orderBy('name')->get(),
            'projects' => Project::whereIn('status', ['rencana', 'berjalan', 'tertahan'])->orderBy('code')->get()]);
    }

    public function store(Request $request, Communication $comm)
    {
        $this->requireNotViewer();
        $data = $request->validate([
            'to_party_id' => 'required|exists:parties,id', 'to_user_id' => 'nullable|exists:users,id',
            'subject' => 'required|string|max:255', 'body' => 'required|string', 'priority' => 'required|in:rendah,normal,tinggi,kritis',
            'due_date' => 'nullable|date', 'project_id' => 'nullable|exists:projects,id', 'task_id' => 'nullable|exists:tasks,id',
            'task_document_id' => 'nullable|exists:task_documents,id',
        ]);
        $memo = $comm->memo(auth()->user(), $data);
        return redirect()->route('memos.show', $memo)->with('ok', 'Memo '.$memo->code.' terkirim ke '.$memo->toParty->shortName().'.');
    }

    public function show(Memo $memo)
    {
        $memo->load('fromParty', 'toParty', 'fromUser', 'toUser', 'project', 'task', 'document', 'replies.user');
        return view('memos.show', compact('memo'));
    }

    public function reply(Request $request, Memo $memo, Communication $comm)
    {
        $this->requireNotViewer();
        $data = $request->validate(['body' => 'required|string']);
        $comm->replyMemo(auth()->user(), $memo, $data['body'], $request->boolean('close'));
        return back()->with('ok', $request->boolean('close') ? 'Balasan terkirim dan memo ditutup.' : 'Balasan terkirim.');
    }
}
