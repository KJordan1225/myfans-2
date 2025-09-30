<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsernameController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|alpha_dash',
        ]);

        $username = ltrim($request->input('username'), '@'); // strip @ if user adds it
        $link = url('@' . $username);

        return back()->with('link', $link);
    }
}

