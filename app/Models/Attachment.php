<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $guarded = [];

    /** Jenis file yang boleh diunggah sebagai lampiran kegiatan. */
    public const ALLOWED = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif',
        'webp', 'tif', 'tiff', 'txt', 'rtf', 'odt', 'ods', 'zip', 'rar', '7z', 'msg', 'eml', 'xml'];

    public const MAX_KB = 25600; // 25 MB per file

    public function task() { return $this->belongsTo(Task::class); }
    public function document() { return $this->belongsTo(TaskDocument::class, 'task_document_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function isImage(): bool { return in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']); }
    public function isPdf(): bool { return $this->extension === 'pdf'; }

    public function icon(): string
    {
        return match (true) {
            $this->isPdf() => 'PDF',
            in_array($this->extension, ['xls', 'xlsx', 'csv', 'ods']) => 'XLS',
            in_array($this->extension, ['doc', 'docx', 'odt', 'rtf', 'txt']) => 'DOC',
            in_array($this->extension, ['ppt', 'pptx']) => 'PPT',
            $this->isImage() || in_array($this->extension, ['tif', 'tiff']) => 'IMG',
            in_array($this->extension, ['zip', 'rar', '7z']) => 'ZIP',
            default => strtoupper($this->extension ?: 'FILE'),
        };
    }

    public function humanSize(): string
    {
        $b = $this->size;
        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }

    protected static function booted(): void
    {
        static::deleting(fn ($a) => Storage::disk('local')->delete($a->path));
    }
}
