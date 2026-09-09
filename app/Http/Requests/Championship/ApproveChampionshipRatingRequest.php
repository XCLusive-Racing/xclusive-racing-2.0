<?php

namespace App\Http\Requests\Championship;

use App\Models\Championship;
use Illuminate\Foundation\Http\FormRequest;

// The only request class that is ever allowed to result in xcl_rating_enabled
// becoming true. Authorization is checked here (not just in the controller or
// the view), against ChampionshipPolicy::approveRating — XCL admin only. There
// is no request field for xcl_rating_enabled at all: approving is an action, not
// a value a caller supplies, so there's nothing here for a league manager (who
// can never even reach this route) to smuggle a value through.
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
