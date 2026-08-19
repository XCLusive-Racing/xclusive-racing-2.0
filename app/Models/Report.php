<?php

namespace App\Models;

use App\Services\PenaltyCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'race_id', 'reported_driver_name', 'reported_user_id', 'reporter_driver_name',
        'lap_number', 'incident_corner', 'description', 'session_type',
        'video_url', 'clip_good_driver_url', 'clip_bad_driver_url', 'clip_heli_url',
        'status', 'admin_notes', 'reviewed_by',
        'steward_1_id', 'steward_1_verdict', 'steward_1_penalty', 'steward_1_multiplier', 'steward_1_notes', 'steward_1_red_flag',
        'steward_2_id', 'steward_2_verdict', 'steward_2_penalty', 'steward_2_multiplier', 'steward_2_notes', 'steward_2_red_flag',
        'final_penalty', 'final_multiplier',
        'ready_to_process', 'processed_at', 'processed_by',
        'xcl_rating_deduction', 'xcl_rating_return', 'sr_deduction',
        'dismissal_reason', 'ban_review_flagged',
    ];

    protected function casts(): array
    {
        return [
            'steward_1_multiplier' => 'decimal:1',
            'steward_1_red_flag'   => 'boolean',
            'steward_2_multiplier' => 'decimal:1',
            'steward_2_red_flag'   => 'boolean',
            'final_multiplier'     => 'decimal:1',
            'ready_to_process'     => 'boolean',
            'processed_at'         => 'datetime',
            'xcl_rating_deduction' => 'decimal:4',
            'xcl_rating_return'    => 'decimal:4',
            'sr_deduction'         => 'decimal:2',
            'ban_review_flagged'   => 'boolean',
        ];
    }

    public static function statuses(): array
    {
        return [
            'pending'       => ['label' => 'Pending',       'color' => '#9ca3af'],
            'investigating' => ['label' => 'Investigating',  'color' => '#f59e0b'],
            'resolved'      => ['label' => 'Resolved',       'color' => '#16a34a'],
            'dismissed'     => ['label' => 'Dismissed',      'color' => '#6b7280'],
        ];
    }

    public static function sessionTypes(): array
    {
        return [
            'R' => 'Race',
            'Q' => 'Qualifying',
            'P' => 'Practice',
        ];
    }

    public function statusMeta(): array
    {
        return self::statuses()[$this->status] ?? ['label' => ucfirst($this->status), 'color' => '#6b7280'];
    }

    public function sessionLabel(): string
    {
        return self::sessionTypes()[$this->session_type] ?? '—';
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function reportedUserAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function steward1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_1_id');
    }

    public function steward2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_2_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function verdicts(): HasMany
    {
        return $this->hasMany(ReportVerdict::class);
    }

    // --- Steward assignment / verdict helpers ---

    /** Which slot (1 or 2) this user holds, or null if they're an unassigned / additional steward. */
    public function slotFor(User $user): ?int
    {
        if ($this->steward_1_id === $user->id) return 1;
        if ($this->steward_2_id === $user->id) return 2;
        return null;
    }

    public function isStewardAssigned(User $user): bool
    {
        return $this->slotFor($user) !== null;
    }

    /** Whether this user has already submitted a verdict (slot 1/2 or additional). */
    public function hasVerdictFrom(User $user): bool
    {
        return $this->relationLoaded('verdicts')
            ? $this->verdicts->contains('steward_id', $user->id)
            : $this->verdicts()->where('steward_id', $user->id)->exists();
    }

    /** First two verdicts (from different stewards) that share the same penalty + multiplier. */
    public function matchingVerdictPair(): ?array
    {
        $verdicts = $this->verdicts->values();

        for ($i = 0; $i < $verdicts->count(); $i++) {
            for ($j = $i + 1; $j < $verdicts->count(); $j++) {
                if ($verdicts[$i]->matches($verdicts[$j])) {
                    return [$verdicts[$i], $verdicts[$j]];
                }
            }
        }

        return null;
    }

    public function anyRedFlagActive(): bool
    {
        return $this->verdicts->contains('red_flag', true);
    }

    /** 'agree' | 'red_flag' | 'awaiting' — drives the banner shown on the incident page. */
    public function agreementState(): string
    {
        $pair = $this->matchingVerdictPair();

        if (! $pair) {
            return 'awaiting';
        }

        return $this->anyRedFlagActive() ? 'red_flag' : 'agree';
    }

    // --- Rating field / driver resolution ---

    /** elo_{game} / sr_{game} column name for this report's race, or null if the game isn't rated. */
    public function ratingFields(): ?array
    {
        $game = $this->race?->game;

        return match ($game) {
            'acc'     => ['elo' => 'elo_acc', 'sr' => 'sr_acc'],
            'lmu'     => ['elo' => 'elo_lmu', 'sr' => 'sr_lmu'],
            'iracing' => ['elo' => 'elo_iracing', 'sr' => 'sr_iracing'],
            default   => null,
        };
    }

    /**
     * Resolve the reported driver's User account. New reports carry reported_user_id
     * directly (chosen from the race's participant list at submission time). Older
     * reports only stored a name, so we fall back to matching that name against the
     * linked race result, then a Driver/gamertag match against platform_id the same
     * way profile lookups work elsewhere.
     */
    public function reportedUser(): ?User
    {
        if ($this->reported_user_id) {
            return $this->relationLoaded('reportedUserAccount')
                ? $this->reportedUserAccount
                : User::find($this->reported_user_id);
        }

        if ($this->race_id && $this->reported_driver_name) {
            $result = RaceResult::where('race_id', $this->race_id)
                ->where('driver_name', $this->reported_driver_name)
                ->whereNotNull('user_id')
                ->first();

            if ($result) {
                return $result->user;
            }
        }

        if (! $this->reported_driver_name) {
            return null;
        }

        $driver = Driver::where('gamertag', $this->reported_driver_name)->first();

        if (! $driver) {
            return null;
        }

        return User::where('platform_id', $driver->xuid_psid)
            ->orWhere('name', $driver->gamertag)
            ->first();
    }

    /** Live preview of the rating/SR impact for a given penalty + multiplier selection. */
    public function previewCalculation(string $penaltyCode, float|int $multiplier): array
    {
        $reportedUser = $this->reportedUser();
        $fields       = $this->ratingFields();
        $rating       = $fields && $reportedUser ? (float) ($reportedUser->{$fields['elo']} ?? 0) : 0.0;

        return PenaltyCalculator::calculate($penaltyCode, $multiplier, $this->session_type, $rating);
    }
}
