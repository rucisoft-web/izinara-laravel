<?php

namespace App\Http\Controllers;

use App\Models\Notice;

class NoticeController extends Controller
{
    public function index()
    {
        $notices = auth()->user()->notices()->with('actor')->limit(200)->get();
        return view('notices.index', compact('notices'));
    }

    public function open(Notice $notice)
    {
        abort_unless($notice->user_id === auth()->id(), 403);
        $notice->update(['read_at' => $notice->read_at ?? now()]);
        return redirect($notice->url ? (str_starts_with($notice->url, 'http') ? $notice->url : url($notice->url)) : route('notices.index'));
    }

    public function readAll()
    {
        auth()->user()->notices()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('ok', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
