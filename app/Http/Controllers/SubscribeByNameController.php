<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscribeByNameController extends Controller
{
    public function callCreatorSubscribe(User $user)
    {
        $creator = $user->profile;
         $prices = DB::table('creator_prices')->where('creator_id', $creator->id)->get();
        $subscription = Subscription::where('creator_id', $creator->id)->firstOrFail();
         $prices = DB::table('creator_prices')->where('creator_id', $creator->id)->get();
        return view('creator.subscribe', compact('creator', 'prices'));
    }
}
