<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $folders      = MediaFolder::orderBy('name')->get();
        $activeFolder = $request->query('folder');
        $folderSlugs  = $folders->pluck('slug');

        if ($activeFolder === '__uncategorised__') {
            $media = Media::where(fn($q) => $q->whereNull('category')->orWhereNotIn('category', $folderSlugs))->latest()->get();
        } elseif ($activeFolder) {
            if (!$folders->firstWhere('slug', $activeFolder)) {
                $activeFolder = null;
            }
            $media = $activeFolder
                ? Media::where('category', $activeFolder)->latest()->get()
                : collect();
        } else {
            $media = collect();
        }

        return view('admin.media.index', compact('media', 'folders', 'activeFolder'));
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        $slug = Str::slug($data['name']);

        $folder = MediaFolder::firstOrCreate(['slug' => $slug], ['name' => $data['name']]);

        return response()->json(['slug' => $folder->slug, 'name' => $folder->name]);
    }

    public function destroyFolder(MediaFolder $folder)
    {
        Media::where('category', $folder->slug)->update(['category' => null]);
        $folder->delete();

        return redirect()->route('admin.media.index')->with('success', 'Folder "' . $folder->name . '" deleted. Media moved to uncategorised.');
    }

    public function list()
    {
        $media = Media::latest()->get()->map(fn (Media $m) => [
            'id'            => $m->id,
            'url'           => $m->url,
            'path'          => $m->path,
            'type'          => $m->type,
            'original_name' => $m->original_name,
            'title'         => $m->title,
            'size'          => $m->formatted_size,
        ]);

        return response()->json($media);
    }

    public function store(Request $request)
    {
        $source = $request->input('source', 'upload');

        if ($source === 'youtube') {
            $request->validate([
                'youtube_url' => ['required', 'string', function ($attr, $val, $fail) {
                    if (!Media::extractYoutubeId($val)) {
                        $fail('Please enter a valid YouTube URL.');
                    }
                }],
                'title'    => 'nullable|string|max:255',
                'category' => 'nullable|string|max:100',
            ]);

            $media = Media::createFromYoutube(
                $request->youtube_url,
                $request->title,
                $request->category,
            );
        } else {
            $request->validate([
                'file'     => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov,mkv|max:204800',
                'title'    => 'nullable|string|max:255',
                'type'     => 'nullable|in:image,icon,video',
                'category' => 'nullable|string|max:100',
            ]);

            $media = Media::createFromUpload(
                $request->file('file'),
                $request->input('type'),
                $request->input('category'),
            );

            if ($request->filled('title')) {
                $media->update(['title' => $request->title]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id'            => $media->id,
                'url'           => $media->url,
                'path'          => $media->path,
                'type'          => $media->type,
                'original_name' => $media->original_name,
            ]);
        }

        $folder = $request->query('folder');
        $redirect = redirect()->route('admin.media.index', $folder ? ['folder' => $folder] : []);
        return $redirect->with('success', 'Media added successfully.');
    }

    public function migrateStorage()
    {
        $moved = 0;

        foreach (['images', 'videos'] as $folder) {
            $oldBase = storage_path('app/public/' . $folder);
            $newBase = public_path('uploads/' . $folder);

            if (!is_dir($oldBase)) continue;

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($oldBase, \FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;

                $relative  = substr($file->getPathname(), strlen(storage_path('app/public')) + 1);
                $relative  = str_replace('\\', '/', $relative);
                $dest      = public_path('uploads/' . $relative);

                if (file_exists($dest)) continue;

                @mkdir(dirname($dest), 0755, true);

                if (copy($file->getPathname(), $dest)) {
                    $moved++;
                }
            }
        }

        return redirect()->route('admin.media.index')
            ->with('success', "Storage migrated: {$moved} file(s) moved to public/uploads.");
    }

    public function destroy(Media $media)
    {
        if ($media->path) {
            Storage::disk('media')->delete($media->path);
        }
        $media->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.media.index')->with('success', 'File deleted.');
    }
}