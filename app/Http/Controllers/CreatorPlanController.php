<?php
// app/Http/Controllers/CreatorPlanController.php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use App\Services\PlanStripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controllers\Middleware;

class CreatorPlanController extends Controller
{
    public static function middleware(): array
    {
        return [
            // Apply to the entire controller:
            'auth',
            'verified',
        ];
    }

    public function index(Request $request)
    {
        $plans = CreatorPlan::where('creator_id', $request->user()->id)
            ->orderByDesc('created_at')->get();

        return view('creator.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('creator.plans.create');
    }

    public function store(Request $r, PlanStripeService $planStripe)
    {
        $data = $r->validate([
            'name'        => ['required','string','max:120'],
            'price'       => ['required','numeric','min:1'], // dollars entered in form
            'currency'    => ['required','string','size:3'],
            'interval'    => ['required','in:day,week,month,year'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $plan = CreatorPlan::create([
            'creator_id'  => $r->user()->id,
            'name'        => $data['name'],
            'price_cents' => (int) round($data['price'] * 100),
            'currency'    => strtolower($data['currency']),
            'interval'    => $data['interval'],
            'is_active'   => (bool)($data['is_active'] ?? false),
        ]);

        // Push to Stripe (connected account)
        $planStripe->ensurePrice($plan);

        return redirect()->route('creator.plans.index')
            ->with('success', 'Plan created and synced to Stripe.');
    }

    public function edit(CreatorPlan $plan)
    {
        Gate::authorize('update', $plan->creator); // reuse your UserPolicy
        return view('creator.plans.edit', compact('plan'));
    }

    public function update(Request $r, CreatorPlan $plan, PlanStripeService $planStripe)
    {
        Gate::authorize('update', $plan->creator);

        $data = $r->validate([
            'name'        => ['required','string','max:120'],
            'price'       => ['required','numeric','min:1'],
            'currency'    => ['required','string','size:3'],
            'interval'    => ['required','in:day,week,month,year'],
            'is_active'   => ['nullable','boolean'],
        ]);

        $plan->fill([
            'name'        => $data['name'],
            'price_cents' => (int) round($data['price'] * 100),
            'currency'    => strtolower($data['currency']),
            'interval'    => $data['interval'],
            'is_active'   => (bool)($data['is_active'] ?? false),
        ])->save();

        // Creating a new Price captures any changes
        $planStripe->ensurePrice($plan);

        return redirect()->route('creator.plans.index')
            ->with('success', 'Plan updated and synced to Stripe.');
    }

    public function destroy(CreatorPlan $plan)
    {
        Gate::authorize('update', $plan->creator);
        $plan->delete();
        return redirect()->route('creator.plans.index')
            ->with('success', 'Plan deleted.');
    }
}
