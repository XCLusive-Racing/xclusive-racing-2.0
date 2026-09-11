<?php

namespace App\Http\Requests\Championship;

use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Http\FormRequest;

// Validates one wizard step at a time. For every step but "basics" the rule set
// is generated straight from ChampionshipSettingsSchema — a new rule in the
// schema shows up here automatically, no change needed in this class.
class SaveChampionshipStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('championship')) ?? false;
    }

    public function rules(): array
    {
        $step = $this->route('step');

        if ($step === 'basics') {
            $championship = $this->route('championship');

            return array_merge([
                'name'          => 'required|string|max:150',
                'slug'          => 'required|alpha_dash|max:150|unique:championships,slug,' . $championship->id,
                'game'          => 'required|in:acc,lmu,iracing,ac',
                'platform'      => 'required|in:pc,console,cross',
                'visibility'    => 'required|in:public,unlisted',
                'description'   => 'nullable|string|max:5000',
                'image'         => 'nullable|image|max:4096',
                // Gallery-pick path from <x-media-picker> — an uploaded file (above)
                // always wins; this carries a reused/kept media path otherwise.
                'image_path'    => 'nullable|string|max:500',
                // Ownership (does this server actually belong to the championship's
                // league?) is checked in the controller, not here — a plain
                // exists:ftp_servers,id can't scope that.
                'ftp_server_id' => 'nullable|exists:ftp_servers,id',
            ], ChampionshipSettingsSchema::rulesForGroups(['schedule']));
        }

        $groups = ChampionshipSettingsSchema::STEP_GROUPS[$step] ?? [];

        return ChampionshipSettingsSchema::rulesForGroups($groups);
    }
}
