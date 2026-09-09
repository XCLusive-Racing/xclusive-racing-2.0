<?php

namespace App\Settings\Casts;

use App\Settings\ChampionshipSettings;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

// Casts championships.settings into a typed ChampionshipSettings object, upgrading
// it against the current schema on every read so old data always loads with new
// keys filled in. Writing always re-upgrades too, and stamps settings_version —
// a save is what actually brings a row's stored JSON up to date.
class ChampionshipSettingsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ChampionshipSettings
    {
        $stored = $value ? json_decode($value, true) : [];

        return new ChampionshipSettings(ChampionshipSettingsSchema::upgrade($stored ?? []));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $data = $value instanceof ChampionshipSettings ? $value->toArray() : (array) $value;
        $data = ChampionshipSettingsSchema::upgrade($data);

        return [
            $key                => json_encode($data),
            'settings_version'  => ChampionshipSettingsSchema::CURRENT_VERSION,
        ];
    }
}
