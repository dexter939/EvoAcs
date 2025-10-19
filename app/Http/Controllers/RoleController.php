<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\SecurityLog;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('level', 'desc')->get();
        $permissions = Permission::orderBy('category')->orderBy('name')->get();
        
        return view('acs.roles', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles'],
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'level' => $request->level,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        SecurityLog::logEvent('role_created', [
            'severity' => 'warning',
            'ip_address' => $request->ip(),
            'action' => 'role_created',
            'description' => 'New role created by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'high',
            'metadata' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => count($request->permissions ?? []),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruolo creato con successo',
            'role' => $role->load('permissions'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Prevent modification of system roles
        if (in_array($role->slug, ['super-admin', 'admin', 'operator', 'technician', 'viewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi modificare i ruoli di sistema predefiniti',
            ], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $id],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug,' . $id],
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'level' => $request->level,
            'description' => $request->description,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        SecurityLog::logEvent('role_updated', [
            'severity' => 'warning',
            'ip_address' => $request->ip(),
            'action' => 'role_updated',
            'description' => 'Role updated by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'high',
            'metadata' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => count($request->permissions ?? []),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruolo aggiornato con successo',
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Prevent deletion of system roles
        if (in_array($role->slug, ['super-admin', 'admin', 'operator', 'technician', 'viewer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi eliminare i ruoli di sistema predefiniti',
            ], 403);
        }

        // Check if role is assigned to users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare: il ruolo è assegnato a ' . $role->users()->count() . ' utenti',
            ], 403);
        }

        SecurityLog::logEvent('role_deleted', [
            'severity' => 'critical',
            'ip_address' => request()->ip(),
            'action' => 'role_deleted',
            'description' => 'Role deleted by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'critical',
            'metadata' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
        ]);

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruolo eliminato con successo',
        ]);
    }

    public function assignPermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->permissions()->sync($request->permissions ?? []);

        SecurityLog::logEvent('permissions_updated', [
            'severity' => 'warning',
            'ip_address' => $request->ip(),
            'action' => 'permissions_updated',
            'description' => 'Permissions updated for role by ' . auth()->user()->name,
            'user_id' => auth()->id(),
            'risk_level' => 'high',
            'metadata' => [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => count($request->permissions),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permessi aggiornati con successo',
            'role' => $role->load('permissions'),
        ]);
    }
}
