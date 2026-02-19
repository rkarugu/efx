<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryDriverMiddleware
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
        if (!Auth::check()) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();
        
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

        // Check if user has delivery driver permissions
        $logged_user_info = getLoggeduserProfile();
        $my_permissions = $logged_user_info->permissions ?? [];
        
        if (isset($my_permissions['delivery-driver___view'])) {
            return $next($request);
        }

        // Deny access
        return redirect()->route('admin.dashboard')->with('error', 'Access denied. Delivery drivers only.');
    }
}
