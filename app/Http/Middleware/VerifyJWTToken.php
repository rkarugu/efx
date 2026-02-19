<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class VerifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // $user = JWTAuth::toUser($request->input('token'));
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            if (!$this->isSalesmanMobileApiAllowed($user)) {
                return response()->json(['status' => false, 'message' => 'Salesman mobile access has been disabled.'], 403);
            }
        } catch (\Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['token_expired'], $e->getStatusCode());
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['token_invalid'], $e->getStatusCode());
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException ) {
                return response()->json(['token_invalid'], $e->getStatusCode());
            }else {
                return response()->json(['error' => 'Token is required']);
            }
        }
        return $next($request);
    }

    private function isSalesmanMobileApiAllowed($user): bool
    {
        if (config('salesman.allow_mobile_api', true)) {
            return true;
        }

        $salesRoleIds = config('salesman.sales_role_ids', [169, 170]);
        if (in_array((int) $user->role_id, $salesRoleIds, true)) {
            return false;
        }

        $hasRoute = !empty($user->route);
        if (!$hasRoute) {
            $hasRoute = $user->routes()->exists();
        }

        if ($hasRoute) {
            return false;
        }

        $salesKeywords = config('salesman.sales_role_keywords', ['sales', 'salesman', 'representative']);
        $roleTitle = strtolower((string) optional($user->userRole)->title);

        foreach ($salesKeywords as $keyword) {
            $keyword = strtolower((string) $keyword);
            if ($keyword !== '' && $roleTitle !== '' && str_contains($roleTitle, $keyword)) {
                return false;
            }
        }

        return true;
    }
}