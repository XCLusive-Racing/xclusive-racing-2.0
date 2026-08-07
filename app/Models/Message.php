<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $fillable = ['user_id', 'title', 'body', 'type', 'read_at', 'related_id', 'related_type'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function typeIcon(): string
    {
        return match ($this->type) {
            'event_registration'  => 'fa-flag-checkered',
            'report_confirmation' => 'fa-file-circle-check',
            'report_resolved'     => 'fa-gavel',
            'news'                => 'fa-newspaper',
            default               => 'fa-envelope',
        };
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'event_registration'  => '#7c3aed',
            'report_confirmation' => '#2563eb',
            'report_resolved'     => '#16a34a',
            'news'                => '#db2877',
            default               => '#6b7280',
        };
    }
}