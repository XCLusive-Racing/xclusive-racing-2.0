<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A race session's raw ACC results JSON (see the migration): the source of the results
// page's detailed stats. Stored gzipped + base64 so any file fits a text column.
class RaceSessionFile extends Model
{
    protected $fillable = ['race_id', 'race_number', 'content'];

    public static function put(Race $race, int $raceNumber, string $json): void
    {
        self::updateOrCreate(
            ['race_id' => $race->id, 'race_number' => $raceNumber],
            ['content' => base64_encode(gzencode($json, 9))],
        );
    }

    public static function jsonFor(Race $race, int $raceNumber = 1): ?string
    {
        $content = self::where('race_id', $race->id)->where('race_number', $raceNumber)->value('content');
        $json = $content !== null ? gzdecode(base64_decode($content)) : false;

        return $json === false ? null : $json;
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
