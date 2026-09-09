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

            return [
                'name'       => 'required|string|max:150',
                'slug'       => 'required|alpha_dash|max:150|unique:championships,slug,' . $championship->id,
                'game'       => 'required|in:acc,lmu',
                'platform'   => 'required|in:pc,console,cross',
                'visibility' => 'required|in:public,unlisted',
                'image'      => 'nullable|image|max:4096',
            ];
        }

        $groups = ChampionshipSettingsSchema::STEP_GROUPS[$step] ?? [];

        return ChampionshipSettingsSchema::rulesForGroups($groups);
    }
}
