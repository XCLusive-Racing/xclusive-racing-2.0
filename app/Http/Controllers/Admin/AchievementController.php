<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Xp\MetricRegistry;
use App\Xp\RankCurve;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Admin > XP & Achievements: the catalogue and the rank curve's health
// (docs/xp-achievements/PLAN.md). An achievement is never deleted, only archived, and
// its metric can only be one the registry knows.
class AchievementController extends Controller
{
    public function __construct(private MetricRegistry $metrics, private RankCurve $curve) {}

    public function index(Request $request)
    {
        $filters = $request->only(['category', 'scope', 'family', 'archived']);
        $archived = $filters['archived'] ?? 'no';

        $achievements = Achievement::withCount('awards')
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['scope'] ?? null, fn ($q, $v) => $q->where('game_scope', $v))
            ->when($filters['family'] ?? null, fn ($q, $v) => $q->where('family', $v))
            ->when($archived !== 'all', fn ($q) => $q->where('is_archived', $archived === 'yes'))
            ->orderBy('category')->orderBy('sort_order')
            ->paginate(50)->withQueryString();

        return view('admin.xp.achievements.index', [
            'achievements' => $achievements,
            'filters' => $filters + ['archived' => $archived],
            'categories' => Achievement::distinct()->orderBy('category')->pluck('category'),
            'families' => Achievement::whereNotNull('family')->distinct()->orderBy('family')->pluck('family'),
            'scopes' => $this->scopes(),
            'metrics' => $this->metrics->all(),
            'health' => $this->curve->health(),
            'anchorRank' => RankCurve::ANCHOR_RANK,
        ]);
    }

    public function create()
    {
        return view('admin.xp.achievements.create', $this->formData(new Achievement(['game_scope' => 'all', 'comparison' => 'gte'])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $request->validate(['code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_:.]+$/', 'unique:achievements,code']])['code'];
        $data['family'] = $request->input('family') ?: null;

        $achievement = Achievement::create($data);

        return redirect()->route('admin.xp.achievements.index')->with('success', '"'.$achievement->name.'" added.');
    }

    public function edit(Achievement $achievement)
    {
        return view('admin.xp.achievements.edit', $this->formData($achievement) + ['holders' => $achievement->awards()->count()]);
    }

    public function update(Request $request, Achievement $achievement)
    {
        // Awards already made keep the XP they were made with (achievement_awards.xp_awarded).
        $achievement->update($this->validated($request));

        return redirect()->route('admin.xp.achievements.index')->with('success', '"'.$achievement->name.'" saved.');
    }

    // Archiving is the only removal: it comes off the list of things still to earn, and
    // everyone who holds it keeps it and its XP.
    public function archive(Achievement $achievement)
    {
        $achievement->update(['is_archived' => ! $achievement->is_archived]);

        return back()->with('success', '"'.$achievement->name.'" '.($achievement->is_archived ? 'archived.' : 'restored.'));
    }

    public function curve()
    {
        return view('admin.xp.curve', [
            'health' => $this->curve->health(),
            'preview' => $this->curve->refitPreview(),
            'anchorRank' => RankCurve::ANCHOR_RANK,
            'maxRank' => $this->curve->maxRank(),
        ]);
    }

    public function refitCurve()
    {
        if (! $this->curve->refit()) {
            return back()->with('error', 'The curve could not be refitted: '.$this->curve->refitPreview()['reason']);
        }

        return redirect()->route('admin.xp.curve')->with('success', 'Curve refitted. The title agnostic catalogue now lands exactly on rank '.RankCurve::ANCHOR_RANK.'.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|max:60',
            'game_scope' => ['required', Rule::in(array_keys($this->scopes()))],
            // From the registry only: a metric nobody computes would never be awarded.
            'metric_key' => ['required', Rule::in(array_keys($this->metrics->all()))],
            'threshold' => 'required|numeric|min:0',
            'xp' => 'required|integer|min:0|max:1000000',
            'sort_order' => 'nullable|integer|min:0',
        ], ['metric_key.in' => 'Choose a metric from the list. A new metric has to be added in code first.']);

        return ['sort_order' => (int) ($data['sort_order'] ?? 0)] + $data;
    }

    private function formData(Achievement $achievement): array
    {
        return [
            'achievement' => $achievement,
            'scopes' => $this->scopes(),
            'metrics' => $this->metrics->all(),
            'categories' => Achievement::distinct()->orderBy('category')->pluck('category'),
        ];
    }

    /** 'all' and every game key => label. */
    private function scopes(): array
    {
        return ['all' => 'All titles'] + Game::options();
    }
}
