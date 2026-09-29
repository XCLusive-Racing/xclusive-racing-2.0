<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['time_trial_event_id', 'user_id'])]
class TimeTrialRegistration extends Model
{
    public function event(): BelongsTo
    {
        return $this->belongsTo(TimeTrialEvent::class, 'time_trial_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
