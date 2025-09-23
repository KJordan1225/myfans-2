<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminUsersController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string)$request->input('q'));
        $role    = $request->input('role');       // e.g. admin, creator, user
        $status  = $request->input('status');     // e.g. active, suspended
        $perPage = (int)($request->input('perPage', 15));

        $query = User::query()
            ->with(['roles', 'profile.media'])
            ->when($q, function ($qBuilder) use ($q) {
                $qBuilder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->when($role, function ($qBuilder) use ($role) {
                $qBuilder->role($role); // spatie scope
            })
            ->when($status, function ($qBuilder) use ($status) {
                // You need a column like `is_suspended` boolean or similar
                if ($status === 'suspended') {
                    $qBuilder->where('is_suspended', true);
                } elseif ($status === 'active') {
                    $qBuilder->where(function ($sub) {
                        $sub->whereNull('is_suspended')->orWhere('is_suspended', false);
                    });
                }
            })
            ->latest('id');

        $users = $query->paginate($perPage)->withQueryString();

        $roles = Role::pluck('name'); // for filter dropdown
        return view('admin.users.index', compact('users', 'q', 'role', 'status', 'perPage', 'roles'));
    }

    public function grantAdmin(User $user)
    {
        $user->assignRole('admin');
        return back()->with('swal', [
            'icon'  => 'success',
            'title' => 'Role granted',
            'text'  => "{$user->name} is now an admin.",
        ]);
    }

    public function revokeAdmin(User $user)
    {
        $user->removeRole('admin');
        return back()->with('swal', [
            'icon'  => 'success',
            'title' => 'Role revoked',
            'text'  => "{$user->name} is no longer an admin.",
        ]);
    }

    public function toggleSuspend(User $user)
    {
        $user->is_suspended = ! (bool) $user->is_suspended;
        $user->save();

        $msg = $user->is_suspended ? 'suspended' : 're-activated';
        return back()->with('swal', [
            'icon'  => 'info',
            'title' => 'Status updated',
            'text'  => "{$user->name} has been {$msg}.",
        ]);
    }
}

