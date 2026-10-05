<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

// The Memberships page and the XCL Supporter checkout (App\Services\MembershipService).
class MembershipController extends Controller
{
    public function index()
    {
        $membership = auth()->check() ? Membership::where('user_id', auth()->id())->first() : null;

        return view('memberships', [
            'membership' => $membership,
            'checkoutOpen' => Membership::checkoutOpenFor(auth()->user()),
            'testMode' => Membership::currentMode() === 'test',
            'billing' => config('memberships.billing'),
            'perks' => config('memberships.perks'),
        ]);
    }

    public function checkout(Request $request, MembershipService $memberships)
    {
        if (! Membership::checkoutOpenFor(auth()->user())) {
            return redirect()->route('memberships')->with('error', 'Memberships are launching soon.');
        }

        $plan = (string) $request->input('plan', 'monthly'); // billing option: monthly / yearly
        if (! Membership::isBillingOption($plan)) {
            return redirect()->route('memberships')->with('error', 'That payment option is not available.');
        }

        // Already paying this way and renewing. The other option is a new checkout (a switch).
        $membership = Membership::where('user_id', auth()->id())->first();
        if ($membership?->isRenewing() && $membership->isPaidUp() && $membership->plan === $plan) {
            return redirect()->route('memberships')->with('success', 'You already have '.$membership->planName().'.');
        }

        try {
            return redirect()->away($memberships->checkout(auth()->user(), $plan));
        } catch (Throwable $e) {
            Log::error('Membership checkout failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return redirect()->route('memberships')->with('error', 'The checkout could not be started. Please try again later.');
        }
    }

    // Mollie sends the driver back here after the checkout, paid or not.
    public function return(MembershipService $memberships)
    {
        try {
            $payment = $memberships->handleLatestCheckout(auth()->user());
        } catch (Throwable $e) {
            Log::error('Membership return check failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);
            $payment = null;
        }

        return match ($payment?->status) {
            'paid' => redirect()->route('memberships')->with('success', 'Thank you for supporting XCL! Your '.config('memberships.name').' perks are now active.'),
            'open', 'pending', 'authorized' => redirect()->route('memberships')->with('success', 'Your payment is being processed. Your perks unlock as soon as it is confirmed.'),
            default => redirect()->route('memberships')->with('error', 'The payment was not completed. You have not been charged.'),
        };
    }

    public function cancel(MembershipService $memberships)
    {
        $membership = Membership::where('user_id', auth()->id())->first();
        if (! $membership?->isRenewing()) {
            return redirect()->route('memberships');
        }

        try {
            $memberships->cancel($membership);
        } catch (Throwable $e) {
            Log::error('Membership cancel failed', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return redirect()->route('memberships')->with('error', 'Your membership could not be canceled. Please try again later.');
        }

        return redirect()->route('memberships')->with('success',
            'Your membership has been canceled. Your perks stay active until '.$membership->paid_until?->format('j M Y').'.');
    }

    // Mollie's webhook: only says "payment {id} changed" — handlePayment() fetches it back.
    public function webhook(Request $request, MembershipService $memberships)
    {
        $id = (string) $request->input('id');
        if (! preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
            return response('', 200);
        }

        try {
            $memberships->handlePayment($id);
        } catch (Throwable $e) {
            Log::error('Mollie webhook failed', ['payment' => $id, 'error' => $e->getMessage()]);

            return response('', 500); // Mollie retries
        }

        return response('', 200);
    }
}
