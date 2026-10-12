<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Admin > XP & Achievements > Games: the titles XP is earned in. A title can be added here
// without a deploy; its key is what an achievement's game scope and the ledger refer to,
// so it is never changed or removed afterwards.
class XpGameController extends Controller
{
    public function index()
    {
        $games = Game::orderBy('sort_order')->orderBy('name')->get();
        $achievements = Achievement::active()->selectRaw('game_scope, COUNT(*) as n, SUM(xp) as xp')
            ->groupBy('game_scope')->get()->keyBy('game_scope');

        return view('admin.xp.games', compact('games', 'achievements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_]+$/', 'unique:games,key', Rule::notIn(['all'])],
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $game = Game::create(['sort_order' => (int) ($data['sort_order'] ?? 0), 'is_active' => true] + $data);

        return back()->with('success', $game->name.' added.');
    }

    public function update(Request $request, Game $game)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $game->update(['sort_order' => (int) ($data['sort_order'] ?? 0), 'is_active' => $request->boolean('is_active')] + $data);

        return back()->with('success', $game->name.' saved.');
    }
}
