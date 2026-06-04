<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $fillable = ['filename', 'original_name', 'path', 'type', 'mime_type', 'size', 'title', 'alt_text'];

    private static array $videoMimes = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo'];

    public static function createFromUpload(UploadedFile $file, string $folder = 'images/media'): self
    {
        $mime     = $file->getMimeType();
        $type     = in_array($mime, self::$videoMimes) ? 'video' : 'image';
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs($folder, $filename, 'public');

        return self::create([
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'type'          => $type,
            'mime_type'     => $mime,
            'size'          => $file->getSize(),
        ]);
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}