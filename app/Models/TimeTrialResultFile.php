<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['time_trial_event_id', 'ftp_server_id', 'filename', 'lap_count'])]
class TimeTrialResultFile extends Model {}
