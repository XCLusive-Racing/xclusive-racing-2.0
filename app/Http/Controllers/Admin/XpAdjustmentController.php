<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\XpLedgerEntry;
use App\Xp\XpAwarder;
use Illuminate\Http\Request;

// Admin > XP & Achievements > Adjustments: grant a driver XP by hand, with a reason,
// written to the ledger as an adjustment. There is no way to take XP away, by design.
class XpAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = XpLedgerEntry::where('source', 'adjustment')->with('user:id,name,first_name,last_name,display_name_preference')
            ->latest('id')->limit(50)->get();
        $admins = User::whereIn('id', $adjustments->where('reference_type', 'user')->pluck('reference_id'))->get()->keyBy('id');

        return view('admin.xp.adjustments', compact('adjustments', 'admins'));
    }

    public function store(Request $request, XpAwarder $awarder)
    {
        $data = $request->validate([
            'driver' => 'required|string|max:255',
            'xp' => 'required|integer|min:1|max:1000000',
            'reason' => 'required|string|min:5|max:255',
        ]);

        $driver = $this->findDriver($data['driver']);
        if (! $driver) {
            return back()->withInput()->withErrors(['driver' => 'No driver found with that ID, email address or name.']);
        }

        $awarder->adjust($driver, (int) $data['xp'], $data['reason'], $request->user());

        return back()->with('success', number_format((int) $data['xp']).' XP granted to '.$driver->displayName().'.');
    }

    // An account ID, an email address, or an exact account name.
    private function findDriver(string $input): ?User
    {
        $input = trim($input);

        return (ctype_digit($input) ? User::find((int) $input) : null)
            ?? User::where('email', $input)->first()
            ?? User::where('name', $input)->first();
    }
}
