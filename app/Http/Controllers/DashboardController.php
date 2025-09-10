<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $users = User::take(5)->get();   // first 5 users
        return view('dashboard', compact('users'));
    }
}
