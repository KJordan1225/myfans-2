<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreatorSubscribePageController extends Controller
{
     public function show(UserProfile $creator)
    {
        abort_unless($creator->is_creator && $creator->stripe_account_id, 404);
        $prices = DB::table('creator_prices')->where('creator_id', $creator->id)->get();
        $subscription = Subscription::where('creator_id', $creator->id)->firstOrFail();
         $prices = DB::table('creator_prices')->where('creator_id', $creator->id)->get();

        return view('creator.subscribe', compact('creator', 'prices'));
    }

}
