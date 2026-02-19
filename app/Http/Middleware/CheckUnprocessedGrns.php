<?php

namespace App\Http\Middleware;

use App\Model\Setting;
use App\Model\WaGrn;
use App\Models\WaStoreReturn;
use Closure;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckUnprocessedGrns
{
    public function handle(HttpRequest $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $permissions = getPreviousPermissionsArray(auth()->user());
        if (!isset($permissions['pending-grns___process'])) {
            return $next($request);
        }

        if (
            in_array(
                $request->route()->getName(),
                [
                    'admin.logout',
                    'pending-grns.index',
                    'grns.receive-documents',
                    'maintain-suppliers.supplier_invoice_order_details',
                    'maintain-suppliers.supplier_invoice_process',
                ]
            ) || $request->is('api*')
        ) {
            return $next($request);
        }

        $days = Setting::where('name', 'MAXIMUM_UNPROCESSED_GRN_DAYS')->first()->description ?? 3;

        // HOTFIX: Bypass unprocessed GRN check to prevent redirect loop
        // Allow users to access system regardless of pending unprocessed GRNs
        /*
        $pendingGRNs = WaGrn::query()
            ->where('created_at', '<', now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s'))
            ->doesntHave('invoice')
            ->exists();

        if (!$pendingGRNs) {
            return $next($request);
        }

        Session::flash('warning', "You have GRNs more than $days Days not processed");

        return redirect()->route('pending-grns.index');
        */
        
        return $next($request);
    }
}
