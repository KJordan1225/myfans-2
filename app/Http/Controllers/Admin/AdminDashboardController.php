<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Example: you can compute KPIs to pass to the view
        $stats = [
            'users'         => \App\Models\User::count(),
            'creators'      => \App\Models\UserProfile::whereNotNull('stripe_account_id')->count(),
            'active_plans'  => \App\Models\CreatorPlan::count(),
            'subscriptions' => \App\Models\Subscription::count(),
            'posts'         => \App\Models\Post::count(),
            'mrr'           => 0, // compute if you store it
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

