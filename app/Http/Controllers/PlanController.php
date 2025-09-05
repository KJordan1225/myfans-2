<?php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\StripeConnectService;

class PlanController extends Controller
{
    public function __construct(private StripeConnectService $svc) {}

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $plans = CreatorPlan::query()->where('creator_id', $user->id)->latest()->get();
        return view('plans.create', compact('plans', 'user'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'amount' => 'required|integer|min:100', // cents
            'interval' => 'required|in:day,week,month,year',
            'platform_fee_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $creator = Auth::user();
        abort_unless($creator->profile?->stripe_account_id, 422, 'Connect your Stripe account first.');

        $plan = $this->svc->createPlanPrice(
            creator: $creator,
            name: $data['name'],
            amountCents: $data['amount'],
            interval: $data['interval'],
            currency: 'usd',
            platformFeePercent: (float) ($data['platform_fee_percent'] ?? 15.0)
        );

        return back()->with('success', 'Plan created.');
    }
}

