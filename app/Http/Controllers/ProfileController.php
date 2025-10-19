<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\SecurityLog;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load('roles');
        return view('acs.profile', compact('user'));
    }

    public function updateInfo(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        SecurityLog::logEvent('profile_updated', [
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'action' => 'profile_updated',
            'description' => 'User updated their profile information',
            'user_id' => $user->id,
            'risk_level' => 'low',
            'metadata' => [
                'updated_fields' => ['name', 'email'],
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profilo aggiornato con successo',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        SecurityLog::logEvent('password_changed', [
            'severity' => 'warning',
            'ip_address' => $request->ip(),
            'action' => 'password_changed',
            'description' => 'User changed their password',
            'user_id' => $user->id,
            'risk_level' => 'medium',
            'metadata' => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password modificata con successo',
        ]);
    }
}
