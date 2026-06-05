<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index()
    {
        $media = Media::latest()->get();
        return view('admin.media.index', compact('media'));
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

        return redirect()->route('admin.media.index')->with('success', 'Media added successfully.');
    }

    public function destroy(Media $media)
    {
        Storage::disk('media')->delete($media->path);
        $media->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.media.index')->with('success', 'File deleted.');
    }
}