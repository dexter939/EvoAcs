<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SecurityLog;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            SecurityLog::logUnauthorizedAccess($request->path(), 'User not authenticated');
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->hasPermission($permission)) {
            SecurityLog::logUnauthorizedAccess(
                $request->path(),
                "User {$user->id} lacks permission: {$permission}"
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to perform this action.'
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
