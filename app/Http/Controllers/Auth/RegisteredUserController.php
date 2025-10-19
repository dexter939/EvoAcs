<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\SecurityLog;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('viewer');

        event(new Registered($user));

        SecurityLog::logEvent('user_registered', [
            'severity' => 'info',
            'ip_address' => $request->ip(),
            'action' => 'user_registered',
            'description' => 'New user registered: ' . $user->email,
            'user_id' => $user->id,
            'risk_level' => 'low',
            'metadata' => [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
        ]);

        Auth::login($user);

        return redirect(route('acs.dashboard'));
    }
}
