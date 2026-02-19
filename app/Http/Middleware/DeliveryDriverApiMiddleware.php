<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class DeliveryDriverApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = null;

        // Try JWT authentication first
        try {
            $user = JWTAuth::parseToken()->authenticate();
            Auth::login($user);
        } catch (JWTException $e) {
            // JWT failed, try session authentication
            if (Auth::check()) {
                $user = Auth::user();
            }
        }

        // If no user authenticated by either method
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized - Please login first'], 401);
        }

        // Check if user is a delivery driver (role_id = 6)
        if ($user->role_id == 6) {
            return $next($request);
        }

        // Check role name/slug contains 'delivery'
        $roleName = '';
        if (isset($user->userRole)) {
            $roleName = $user->userRole->name ?? $user->userRole->title ?? $user->userRole->slug ?? '';
        }

        if (stripos($roleName, 'delivery') !== false) {
            return $next($request);
        }

        // Deny access
        return response()->json(['success' => false, 'message' => 'Access denied. Delivery drivers only.'], 403);
    }
}