<?php

namespace App\Http\Requests\Championship;

use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

// Full validation across every group, run again at publish time even though each
// group already passed per-step validation on the way here — a step can be
// revisited and left in a bad state, or skipped by hitting the route directly.
class PublishChampionshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('championship')) ?? false;
    }

    public function rules(): array
    {
        $championship = $this->route('championship');

        return array_merge([
            'name'       => 'required|string|max:150',
            'slug'       => 'required|alpha_dash|max:150|unique:championships,slug,' . $championship->id,
            'game'       => 'required|in:acc,lmu',
            'platform'   => 'required|in:pc,console,cross',
        ], ChampionshipSettingsSchema::rules());
    }

    protected function prepareForValidation(): void
    {
        // Publishing validates the championship as it already stands, not a
        // fresh submission — merge its current real columns and settings in so
        // the rules above have something to check.
        $championship = $this->route('championship');

        $this->merge([
            'name'     => $championship->name,
            'slug'     => $championship->slug,
            'game'     => $championship->game,
            'platform' => $championship->platform,
            'settings' => $championship->settings->toArray(),
        ]);
    }
}
