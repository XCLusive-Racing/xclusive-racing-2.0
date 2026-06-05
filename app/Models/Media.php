<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $fillable = [
        'filename', 'original_name', 'path', 'type', 'mime_type',
        'size', 'title', 'alt_text', 'youtube_id', 'category',
    ];

    private static array $videoMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-matroska', 'video/mkv'];

    private static array $folderMap = [
        'image' => 'images/media',
        'icon'  => 'images/icons',
        'video' => 'videos/media',
    ];

    public static function createFromUpload(UploadedFile $file, string $forcedType = null, string $category = null): self
    {
        $mime = $file->getMimeType();

        if ($forcedType && in_array($forcedType, ['image', 'icon', 'video'])) {
            $type = $forcedType;
        } else {
            $type = in_array($mime, self::$videoMimes) ? 'video' : 'image';
        }

        $folder   = self::$folderMap[$type] ?? 'images/media';
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs($folder, $filename, 'media');

        return self::create([
            'filename'      => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'type'          => $type,
            'mime_type'     => $mime,
            'size'          => $file->getSize(),
            'category'      => $category,
        ]);
    }

    public static function createFromYoutube(string $youtubeUrl, string $title = null, string $category = null): self
    {
        $id = self::extractYoutubeId($youtubeUrl);

        return self::create([
            'type'       => 'youtube',
            'youtube_id' => $id,
            'title'      => $title ?: $id,
            'size'       => 0,
            'category'   => $category,
        ]);
    }

    public static function extractYoutubeId(string $url): ?string
    {
        preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m);
        return $m[1] ?? null;
    }

    public function isVideo(): bool   { return $this->type === 'video'; }
    public function isImage(): bool   { return $this->type === 'image'; }
    public function isIcon(): bool    { return $this->type === 'icon'; }
    public function isYoutube(): bool { return $this->type === 'youtube'; }

    public function getUrlAttribute(): string
    {
        if ($this->isYoutube()) {
            return "https://www.youtube.com/embed/{$this->youtube_id}";
        }
        return Storage::disk('media')->url($this->path);
    }

    public function getYoutubeThumbnailAttribute(): string
    {
        return "https://img.youtube.com/vi/{$this->youtube_id}/mqdefault.jpg";
    }

    public function getFormattedSizeAttribute(): string
    {
        if ($this->isYoutube()) return 'YouTube';
        $bytes = $this->size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}