<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RatingConfig;
use Illuminate\Http\Request;

class RatingConfigController extends Controller
{
    public function index()
    {
        $configs = RatingConfig::all()->keyBy('key');
        return view('admin.rating.index', compact('configs'));
    }

    public function update(Request $request, string $key)
    {
        $config = RatingConfig::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'value' => 'required|numeric|between:-1000,100000',
        ]);

        $config->update(['value' => $validated['value']]);

        return response()->json(['success' => true, 'value' => (float) $config->fresh()->value]);
    }
}