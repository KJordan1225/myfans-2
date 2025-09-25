<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription; // your model

class AdminSubscriptionsController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string)$request->input('q'));
        $status  = $request->input('status');
        $perPage = (int) $request->input('perPage', 15);

        $subs = Subscription::query()
            ->with(['user','creator','plan'])
            ->when($q, function ($qb) use ($q) {
                $qb->where('id', $q)
                   ->orWhereHas('user', fn($u)=>$u->where('email','like',"%{$q}%")
                                                  ->orWhere('name','like',"%{$q}%"))
                   ->orWhereHas('creator', fn($c)=>$c->where('name','like',"%{$q}%"))
                   ->orWhereHas('plan', fn($p)=>$p->where('name','like',"%{$q}%"));
            })
            ->when($status, fn($qb)=>$qb->where('status',$status))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.pages.subscriptions', compact('subs'));
    }
}
