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

class CheckUnsentGrnDocuments
{
    public function handle(HttpRequest $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $permissions = getPreviousPermissionsArray(auth()->user());
        if (!isset($permissions['confirmed-receive-purchase-order___confirm'])) {
            return $next($request);
        }

        if (
            in_array(
                $request->route()->getName(),
                [
                    'admin.logout',
                    'completed-grn.index',
                    'grns.send-documents'
                ]
            ) || $request->is('api*')
        ) {
            return $next($request);
        }

        $days = Setting::where('name', 'MAXIMUM_SEND_GRN_DOCUMENTS_DAYS')->first()->description ?? 3;

        // HOTFIX: Bypass GRN document check to prevent redirect loop
        // Allow users to access system regardless of pending GRN documents
        /*
        $pendingGRNs = WaGrn::query()
            ->join('wa_purchase_orders as orders', 'orders.id', 'wa_grns.wa_purchase_order_id')
            ->where('documents_sent', 0)
            ->where('wa_grns.created_at', '<', now()->subDays($days)->startOfDay()->format('Y-m-d H:i:s'))
            ->where('orders.wa_location_and_store_id', auth()->user()->wa_location_and_store_id)
            ->get();

        if (!$pendingGRNs->count()) {
            return $next($request);
        }

        $grns = implode(',',$pendingGRNs->pluck('grn_number')->flatten()->toArray());

        Session::flash('warning', "You have GRNs with documents more than $days Days not sent to payables ($grns)");

        return redirect()->route('completed-grn.index');
        */
        
        return $next($request);
    }
}
