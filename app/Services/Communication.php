<?php

namespace App\Services;

use App\Models\CommentMention;
use App\Models\Memo;
use App\Models\Notice;
use App\Models\Party;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskDocument;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Komunikasi antar bagian: komentar/diskusi di kegiatan & dokumen dengan @mention (orang atau bagian),
 * memo antar bagian bertenggat, dan notifikasi kotak masuk.
 *
 * Penulisan mention: @username (mis. @budi.purchasing) atau @kode-bagian (@purchasing, @pangan, @halal,
 * @perizinan, @impor). Mention ke bagian memberi notifikasi ke semua anggota aktif bagian itu.
 */
class Communication
{
    public function comment(User $author, string $body, array $context, array $userIds = [], array $partyIds = [], string $kind = 'komentar'): TaskComment
    {
        $task = isset($context['task_id']) ? Task::find($context['task_id']) : null;
        $doc = isset($context['task_document_id']) ? TaskDocument::find($context['task_document_id']) : null;
        if ($doc && ! $task) $task = $doc->task;
        $comment = TaskComment::create([
            'project_id' => $context['project_id'] ?? $task?->project_id,
            'task_id' => $task?->id,
            'task_document_id' => $doc?->id,
            'memo_id' => $context['memo_id'] ?? null,
            'user_id' => $author->id,
            'kind' => $kind,
            'body' => $body,
        ]);

        [$users, $parties] = $this->parseMentions($body);
        $users = $users->merge(User::whereIn('id', $userIds)->get())->unique('id');
        $parties = $parties->merge(Party::whereIn('id', $partyIds)->get())->unique('id');
        foreach ($users as $u) CommentMention::create(['task_comment_id' => $comment->id, 'user_id' => $u->id]);
        foreach ($parties as $p) CommentMention::create(['task_comment_id' => $comment->id, 'party_id' => $p->id]);

        $where = $doc ? 'dokumen "'.$doc->name.'"' : ($task ? 'kegiatan "'.$task->name.'"' : 'proyek');
        $url = $task ? route('tasks.show', $task).($doc ? '#doc-'.$doc->id : '#diskusi') : ($comment->project_id ? route('projects.show', $comment->project_id) : null);
        $recipients = $users->merge($parties->flatMap(fn ($p) => $p->users()->where('active', true)->get()))->unique('id')
            ->reject(fn ($u) => $u->id === $author->id);
        foreach ($recipients as $u) {
            $this->notify($u, $author, 'mention', $author->name.' menyebut Anda di '.$where, $body, $url);
        }
        // Penanggung jawab kegiatan juga diberi tahu bila ada komentar baru.
        if ($task && $task->assignee_id && $task->assignee_id !== $author->id && ! $recipients->contains('id', $task->assignee_id)) {
            $this->notify($task->assignee, $author, 'mention', 'Komentar baru di '.$where, $body, $url);
        }
        return $comment;
    }

    /** @return array{0: Collection<User>, 1: Collection<Party>} */
    public function parseMentions(string $body): array
    {
        preg_match_all('/(?:^|\s)@([a-z0-9._-]+)/i', $body, $m);
        $handles = collect($m[1])->map(fn ($h) => strtolower(rtrim($h, '.,')))->unique();
        if ($handles->isEmpty()) return [collect(), collect()];
        return [User::whereIn('username', $handles)->get(), Party::whereIn('code', $handles)->get()];
    }

    public function memo(User $author, array $data): Memo
    {
        $memo = Memo::create($data + [
            'code' => 'MEMO-'.now()->format('ym').'-'.str_pad((string) (Memo::count() + 1), 4, '0', STR_PAD_LEFT),
            'from_user_id' => $author->id,
            'from_party_id' => $author->party_id,
            'status' => 'terbuka',
        ]);
        $to = $memo->to_user_id ? collect([$memo->toUser]) : $memo->toParty->users()->where('active', true)->get();
        foreach ($to as $u) {
            if ($u->id === $author->id) continue;
            $this->notify($u, $author, 'memo', 'Memo dari '.($author->party?->shortName() ?? $author->name).': '.$memo->subject,
                $memo->body, route('memos.show', $memo));
        }
        return $memo;
    }

    public function replyMemo(User $author, Memo $memo, string $body, bool $close = false): TaskComment
    {
        $reply = $this->comment($author, $body, ['memo_id' => $memo->id, 'project_id' => $memo->project_id]);
        $isRecipient = $author->id === $memo->to_user_id || $author->party_id === $memo->to_party_id;
        if ($isRecipient && ! $memo->first_response_at) {
            $memo->first_response_at = now();
            $memo->status = 'dijawab';
        }
        if ($close) {
            $memo->status = 'selesai';
            $memo->closed_at = now();
        }
        $memo->save();
        $notify = $isRecipient ? collect([$memo->fromUser]) : ($memo->to_user_id ? collect([$memo->toUser]) : $memo->toParty->users);
        foreach ($notify->filter() as $u) {
            if ($u->id !== $author->id) {
                $this->notify($u, $author, 'memo_reply', 'Balasan memo '.$memo->code.': '.$memo->subject, $body, route('memos.show', $memo));
            }
        }
        return $reply;
    }

    public function notify(?User $user, ?User $actor, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! $user) return;
        Notice::create(['user_id' => $user->id, 'actor_id' => $actor?->id, 'type' => $type, 'title' => mb_substr($title, 0, 250),
            'body' => $body ? mb_substr($body, 0, 500) : null, 'url' => self::relative($url)]);
    }

    /** Simpan URL relatif supaya tetap benar bila domain/APP_URL berubah (mis. setelah upload ke cPanel). */
    public static function relative(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $parts = parse_url($url);
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '').(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        $base = rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?? '', '/');
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        return $path;
    }
}
