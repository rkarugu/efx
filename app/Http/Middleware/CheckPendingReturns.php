<?php

namespace App\Http\Middleware;

use App\Model\Setting;
use App\Models\WaStoreReturn;
use Closure;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckPendingReturns
{
    public function handle(HttpRequest $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $permissions = getPreviousPermissionsArray(auth()->user());
        if (!isset($permissions['return-to-supplier-from-store___approve'])) {
            return $next($request);
        }

        if (in_array(
                $request->route()->getName(), 
                [
                    'admin.logout',
                    'return-to-supplier.from-store.pending',
                    'return-to-supplier.from-store.approve'
                ]
            ) || $request->is('api*')
        ) {
            return $next($request);
        }

        $days = Setting::where('name', 'MAXIMUM_PENDING_RTS_DAYS')->first()->description ?? 3;

        $pendingReturns = WaStoreReturn::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('created_at', '<', now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s'))
            ->whereHas('supplier', function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->where('users.id', auth()->user()->id);
                });
            })
            ->exists();

        if (!$pendingReturns) {
            return $next($request);
        }

        Session::flash('warning', "You have returns older than $days Days. Please process them to proceed");

        return redirect()->route('return-to-supplier.from-store.pending');
    }
}
