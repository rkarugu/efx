<?php

namespace App\Http\Middleware;

use Closure;
use App\WaDemand;
use App\Model\Setting;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovedReturns
{
    public function handle(HttpRequest $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $permissions = getLoggeduserProfile()->permissions;
        if (!isset($permissions['return-demands___convert'])) {
            return $next($request);
        }

        if (in_array(
                $request->route()->getName(), 
                [
                    'admin.logout',
                    'return-to-supplier.from-store.pending',
                    'return-to-supplier.from-store.approve',
                    'return-demands.index'
                ]
            ) || $request->is('api*')
        ) {
            return $next($request);
        }

        $days = Setting::where('name', 'MAXIMUM_APPROVED_RTS_DAYS')->first()->description ?? 7;

        $pendingDemands = WaDemand::query()
            ->where('processed', false)
            ->where('created_at', '<', now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s'))
            ->whereHas('supplier', function ($query) {
                $query->whereHas('users', function ($query) {
                    $query->where('users.id', auth()->user()->id);
                });
            })
            ->exists();

        if (!$pendingDemands) {
            return $next($request);
        }

        Session::flash('warning', "You have approved returns older than $days Days. Please process them to proceed");

        return redirect()->route('return-demands.index');
    }
}
