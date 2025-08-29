<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatorSubscribePageController extends Controller
{
     public function show(User $creator)
    {
        abort_unless($creator->is_creator && $creator->stripe_account_id, 404);
        $prices = DB::table('creator_prices')->where('creator_id', $creator->id)->get();

        return view('creator.subscribe', compact('creator', 'prices'));
    }

}
