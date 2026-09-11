<?php

namespace App\Settings;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use JsonSerializable;

// A typed read view over a championship's settings JSON. Always fully populated —
// ChampionshipSettingsCast runs every value through ChampionshipSettingsSchema::upgrade()
// before building one of these, so every field the schema defines is present here,
// stored value or default. Access a group as $settings->format->multiclass_enabled,
// or reach across groups with dot notation via get().
class ChampionshipSettings implements Arrayable, JsonSerializable
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __get(string $group): Fluent
    {
        return new Fluent($this->data[$group] ?? []);
    }

    public function get(string $dotKey, mixed $default = null): mixed
    {
        return Arr::get($this->data, $dotKey, $default);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
