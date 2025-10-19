<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\SecurityLog;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('created_at', 'desc')->get();
        $roles = Role::orderBy('level', 'desc')->get();
        
        return view('acs.users', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,slug'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        SecurityLog::logEvent('user_created', [
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'action' => 'user_created',
            'description' => 'New user created by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'low',
            'metadata' => [
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'assigned_role' => $request->role,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utente creato con successo',
            'user' => $user->load('roles'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'exists:roles,slug'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        if ($request->filled('role')) {
            $user->roles()->detach();
            $user->assignRole($request->role);
        }

        SecurityLog::logEvent('user_updated', [
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'action' => 'user_updated',
            'description' => 'User updated by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'low',
            'metadata' => [
                'updated_user_id' => $user->id,
                'updated_user_email' => $user->email,
                'updated_role' => $request->role,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utente aggiornato con successo',
            'user' => $user->load('roles'),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi eliminare il tuo stesso account',
            ], 403);
        }

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per eliminare un Super Admin',
            ], 403);
        }

        SecurityLog::logEvent('user_deleted', [
            'severity' => 'warning',
            'ip_address' => request()->ip(),
            'action' => 'user_deleted',
            'description' => 'User deleted by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'medium',
            'metadata' => [
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
            ],
        ]);

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utente eliminato con successo',
        ]);
    }

    public function assignRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => ['required', 'exists:roles,slug'],
        ]);

        if (!auth()->user()->hasPermission('roles.manage') && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per gestire i ruoli',
            ], 403);
        }

        $user->roles()->detach();
        $user->assignRole($request->role);

        SecurityLog::logEvent('role_assigned', [
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'action' => 'role_assigned',
            'description' => 'Role assigned to user by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'medium',
            'metadata' => [
                'target_user_id' => $user->id,
                'target_user_email' => $user->email,
                'assigned_role' => $request->role,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruolo assegnato con successo',
            'user' => $user->load('roles'),
        ]);
    }
}
