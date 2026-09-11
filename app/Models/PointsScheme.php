<?php

namespace App\Models;

use App\Models\Concerns\Tenantable;
use App\Services\PointsSchemeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class PointsScheme extends Model
{
    use Tenantable;

    protected $fillable = [
        'league_id', 'name', 'type', 'description', 'config', 'points_table',
        'fastest_lap_points', 'pole_points', 'leading_lap_points', 'is_template', 'scope_note',
    ];

    protected function casts(): array
    {
        return [
            'config'              => 'array',
            'points_table'        => 'array',
            'fastest_lap_points'  => 'integer',
            'pole_points'         => 'integer',
            'leading_lap_points'  => 'integer',
            'is_template'         => 'boolean',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    // League managers create their own scheme by copying a template, never by
    // editing a shared one — the template itself is untouched by this. Every
    // field carries over, including type/config, so the copy can still be
    // regenerated (not just hand-edited) if the league wants to nudge a
    // setting before their season locks it.
    public function copyFor(League $league, ?string $name = null): self
    {
        return self::create([
            'league_id'           => $league->id,
            'name'                => $name ?: $this->name,
            'type'                => $this->type,
            'description'         => $this->description,
            'config'              => $this->config,
            'points_table'        => $this->points_table,
            'fastest_lap_points'  => $this->fastest_lap_points,
            'pole_points'         => $this->pole_points,
            'leading_lap_points'  => $this->leading_lap_points,
            'is_template'         => false,
            'scope_note'          => $this->scope_note,
        ]);
    }

    // Regenerates points_table from type + config for linear/curved schemes.
    // Manual schemes have no generator — their table is whatever was typed in,
    // so this is a no-op for them. $referenceFieldSize only matters for a
    // percentage-depth scheme: it decides how long a table to actually store
    // (the live per-round cutoff is resolved separately at standings time,
    // see PointsSchemeGenerator::scoringCutoff()).
    public function regenerateTable(int $referenceFieldSize = 30): array
    {
        $config = $this->config ?? [];

        if ($this->type === 'linear') {
            $depth = PointsSchemeGenerator::resolveDepth($config, $referenceFieldSize);
            return PointsSchemeGenerator::linear(
                (int) ($config['top'] ?? 25),
                (int) ($config['gap'] ?? 1),
                $depth,
                isset($config['floor']) ? (int) $config['floor'] : null
            );
        }

        if ($this->type === 'curved') {
            $depth = PointsSchemeGenerator::resolveDepth($config, $referenceFieldSize);
            return PointsSchemeGenerator::curved(
                (int) ($config['top'] ?? 30),
                (int) ($config['floor'] ?? 1),
                $depth,
                (string) ($config['steepness'] ?? 'standard')
            );
        }

        return $this->points_table ?? [];
    }

    // How many positions score in one specific round of one specific
    // championship, given that round's own classified-finisher count.
    public function scoringCutoffFor(int $classifiedFinishers): int
    {
        return PointsSchemeGenerator::scoringCutoff(
            $this->type,
            $this->config ?? [],
            count($this->points_table ?? []),
            $classifiedFinishers
        );
    }

    // Every non-template championship (in this scheme's own league) whose
    // scoring.points_scheme_id points at this scheme. Templates are copied,
    // never referenced directly, so this is only ever meaningful for an owned
    // scheme — a template's list is always empty.
    public function championshipsInUse(): Collection
    {
        if ($this->is_template) {
            return new Collection();
        }

        return Championship::withoutTenantScope()
            ->where('league_id', $this->league_id)
            ->get()
            ->filter(fn (Championship $c) => (string) ($c->settings->scoring->points_scheme_id ?? '') === (string) $this->id)
            ->values();
    }

    // A scheme is locked once any championship using it has a scored round —
    // editing it further (which would recompute and overwrite points_table)
    // would silently change points already awarded. Locked schemes can still
    // be edited, but only via an explicit XCL-admin override, recorded in the
    // audit log by the controller.
    public function isLockedByCompletedRounds(): bool
    {
        return $this->championshipsInUse()->contains(
            fn (Championship $c) => $c->rounds()->where('status', 'finished')->exists()
        );
    }

    // Max points a single round's winner can take from this scheme — top
    // table value plus every bonus. Used for the "total points available
    // across a full season" sanity check in the editor.
    public function maxPointsPerRound(): int
    {
        $table = $this->points_table ?? [];
        $top   = !empty($table) ? (int) reset($table) : 0;

        return $top + $this->fastest_lap_points + $this->pole_points + $this->leading_lap_points;
    }
}
