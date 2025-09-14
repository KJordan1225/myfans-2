<?php

namespace App\Http\Controllers;

use App\Models\CreatorPlan;
use App\Services\PayPalClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorPlanController extends Controller
{
  public function __construct(private PayPalClient $pp) {}

  public function index() {
    $plans = CreatorPlan::where('creator_id', Auth::id())->latest()->get();
    return view('creator.plans.index', compact('plans'));
  }

  public function store(Request $r) {
    $data = $r->validate([
      'name'=>'required|string|max:120',
      'amount'=>'required|numeric|min:1',
      'currency'=>'required|string|size:3',
      'interval_unit'=>'required|in:DAY,WEEK,MONTH,YEAR',
      'interval_count'=>'required|integer|min:1|max:12',
    ]);
    $data['creator_id'] = Auth::id();

    // Create PayPal product + plan for this creator
    $product = $this->pp->createProduct("{$r->user()->name} Membership", "Subscription for @{$r->user()->username}");
    $plan = $this->pp->createPlan($product['id'], $data['name'], $data['currency'], (string)$data['amount'], $data['interval_unit'], (int)$data['interval_count']);

    $data['paypal_product_id'] = $product['id'];
    $data['paypal_plan_id'] = $plan['id'];

    $plan = CreatorPlan::create($data);

    return back()->with('success','Plan created.');
  }
}
