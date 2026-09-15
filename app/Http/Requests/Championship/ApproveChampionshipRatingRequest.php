<?php

namespace App\Http\Requests\Championship;

use App\Models\Championship;
use Illuminate\Foundation\Http\FormRequest;

// The only request class that is ever allowed to result in xcl_rating_enabled
// becoming true. Authorization is checked here (not just in the controller or
// the view), against ChampionshipPolicy::approveRating — the same gate as
// update(), so a league's own manager can reach this too, not just XCL staff.
// There is no request field for xcl_rating_enabled at all: approving is an
// action, not a value a caller supplies, so there's nothing here to smuggle a
// value through.
class ApproveChampionshipRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Championship $championship */
        $championship = $this->route('championship');

        return $this->user()?->can('approveRating', $championship) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
