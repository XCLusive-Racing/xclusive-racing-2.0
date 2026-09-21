<?php

namespace App\Models;

use App\Enums\SafetyRatingGrade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceClass extends Model
{
    protected $fillable = [
        'race_id', 'name', 'color', 'car_class',
        'max_drivers', 'sr_requirement', 'min_rating', 'sort_order',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class);
    }

    // This class's effective driver cap: its own max_drivers if the admin set one,
    // otherwise an even split of the race's overall max_drivers across all of its
    // classes -- e.g. a 50-driver race with 2 classes and no per-class caps set splits
    // into 25 each, rather than leaving each class silently uncapped. Null (uncapped)
    // if neither the class nor the race sets a limit.
    public function effectiveCap(): ?int
    {
        if ($this->max_drivers !== null) {
            return $this->max_drivers;
        }

        $race = $this->race;
        if (! $race || $race->max_drivers === null) {
            return null;
        }

        $classCount = $race->raceClasses->count();

        return $classCount > 0 ? (int) ceil($race->max_drivers / $classCount) : null;
    }

    public function isFull(): bool
    {
        $cap = $this->effectiveCap();
        if ($cap === null) {
            return false;
        }

        return $this->registrations()->count() >= $cap;
    }

    // How many still-active registrations were created before this one -- a driver's
    // FIFO position within this class, 0-indexed. Used instead of a stored waitlist
    // flag/table: whether someone is waitlisted (and their queue position) is always
    // derived live from created_at/id order, so cancelling a registration promotes the
    // next one in line automatically, just by shifting everyone's rank down.
    public function registrationRank(RaceRegistration $registration): int
    {
        return $this->registrations()
            ->where(function ($q) use ($registration) {
                $q->where('created_at', '<', $registration->created_at)
                    ->orWhere(function ($q2) use ($registration) {
                        $q2->where('created_at', $registration->created_at)
                            ->where('id', '<', $registration->id);
                    });
            })
            ->count();
    }

    public function isRegistrationWaitlisted(RaceRegistration $registration): bool
    {
        $cap = $this->effectiveCap();
        if ($cap === null) {
            return false;
        }

        return $this->registrationRank($registration) >= $cap;
    }

    /** 1-indexed position on the waiting list (only meaningful when isRegistrationWaitlisted() is true). */
    public function waitlistPosition(RaceRegistration $registration): int
    {
        return $this->registrationRank($registration) - $this->effectiveCap() + 1;
    }

    public function waitlistCount(): int
    {
        $cap = $this->effectiveCap();
        if ($cap === null) {
            return 0;
        }

        return max(0, $this->registrations()->count() - $cap);
    }

    /** Returns [grade-letter, hex-color] for the Min. SR badge. */
    public function srTier(): array
    {
        if (! $this->sr_requirement) {
            return ['', '#9ca3af'];
        }
        $grade = SafetyRatingGrade::fromRating((float) $this->sr_requirement);

        return [$grade->value, $grade->color()];
    }

    /** Returns [display-name, hex-color] for the XCL Rating tier badge. */
    public function xclTierInfo(): array
    {
        return match ($this->min_rating) {
            'rookie' => ['Rookie',   '#ef4444'],
            'bronze' => ['Bronze',   '#cd7f32'],
            'silver' => ['Silver',   '#9ca3af'],
            'gold' => ['Gold',     '#f59e0b'],
            'platinum' => ['Platinum', '#7c3aed'],
            'alien' => ['Alien',    '#10b981'],
            default => ['',         '#6b7280'],
        };
    }
}
