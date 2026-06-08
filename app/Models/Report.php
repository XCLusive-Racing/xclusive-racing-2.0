<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'user_id', 'race_id', 'reported_driver_name',
        'lap_number', 'incident_corner', 'description',
        'video_url', 'status', 'admin_notes', 'reviewed_by',
    ];

    public static function statuses(): array
    {
        return [
            'pending'       => ['label' => 'Pending',       'color' => '#f59e0b'],
            'investigating' => ['label' => 'Investigating',  'color' => '#3b82f6'],
            'resolved'      => ['label' => 'Resolved',       'color' => '#10b981'],
            'dismissed'     => ['label' => 'Dismissed',      'color' => '#6b7280'],
        ];
    }

    public function statusMeta(): array
    {
        return self::statuses()[$this->status] ?? ['label' => ucfirst($this->status), 'color' => '#6b7280'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}