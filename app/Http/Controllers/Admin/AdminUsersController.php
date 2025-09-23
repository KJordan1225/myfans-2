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

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'ids'    => ['required','array','min:1'],
            'ids.*'  => ['integer','exists:users,id'],
            'action' => ['required','in:assign_role,remove_role,suspend,unsuspend'],
            'role'   => ['nullable','string'], // required when action is role-related (checked below)
        ]);

        $actor = $request->user();
        $isSuper = $actor->hasRole('super-admin');

        // Prevent non-super admins from touching super-admins, and from changing their own role
        $query = \App\Models\User::query()->whereIn('id', $validated['ids']);
        if (!$isSuper) {
            $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'super-admin'));
        }
        $users = $query->get();

        if ($users->isEmpty()) {
            return back()->with('swal', [
                'icon'  => 'info',
                'title' => 'No eligible users',
                'text'  => 'Nothing to update (targets may be protected).',
            ]);
        }

        $action = $validated['action'];
        $role   = trim((string)($validated['role'] ?? ''));

        // Role is required for role actions
        if (in_array($action, ['assign_role','remove_role'], true) && $role === '') {
            return back()->with('swal', [
                'icon'  => 'warning',
                'title' => 'Role required',
                'text'  => 'Please choose a role for this bulk action.',
            ]);
        }

        $affected = 0;

        \DB::transaction(function () use ($users, $action, $role, $actor, $isSuper, &$affected) {
            foreach ($users as $user) {
                // Blocks for safety
                if (!$isSuper && $user->id === $actor->id && in_array($action, ['assign_role','remove_role'], true)) {
                    continue; // do not let admins change their own roles
                }

                switch ($action) {
                    case 'assign_role':
                        if (!$user->hasRole($role)) {
                            $user->assignRole($role);
                            $affected++;
                        }
                        break;

                    case 'remove_role':
                        if ($user->hasRole($role)) {
                            // never remove super-admin role unless actor is super-admin
                            if (!($role === 'super-admin' && !$isSuper)) {
                                $user->removeRole($role);
                                $affected++;
                            }
                        }
                        break;

                    case 'suspend':
                        if (!($user->is_suspended ?? false)) {
                            $user->is_suspended = true;
                            $user->save();
                            $affected++;
                        }
                        break;

                    case 'unsuspend':
                        if ($user->is_suspended ?? false) {
                            $user->is_suspended = false;
                            $user->save();
                            $affected++;
                        }
                        break;
                }
            }
        });

        return back()->with('swal', [
            'icon'  => 'success',
            'title' => 'Bulk action complete',
            'text'  => "Updated {$affected} user(s).",
        ]);
    }

}

