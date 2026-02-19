<?php

namespace App\Http;

use App\Http\Middleware\AuthenticateCoop;
use App\Http\Middleware\CheckPreviousDayOperationShiftBalanced;
use App\Http\Middleware\endOfDayLock;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\HttpsProtocol::class,

    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HttpsProtocol::class,
            \App\Http\Middleware\ReadNotification::class,
            \App\Http\Middleware\CheckPendingReturns::class,
            \App\Http\Middleware\CheckApprovedReturns::class,
            \App\Http\Middleware\CheckUnsentGrnDocuments::class,
            \App\Http\Middleware\CheckUnprocessedGrns::class,
        ],
        'AdminLoggedIn'=>[\App\Http\Middleware\AdminLoggedIn::class],
//         'ActiveDevices'=>[\App\Http\Middleware\CheckUserActiveDevices::class],
        'AdminBeforeLoggedIn'=>[\App\Http\Middleware\AdminBeforeLoggedIn::class],

        'api' => [
           // 'throttle:60,1', //commenting it for stop error 429
            'bindings',
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'jwt.auth' => \App\Http\Middleware\VerifyJWTToken::class,
        'mobile-devices' => \App\Http\Middleware\CheckUserActiveDevices::class,
        'mobile-device-sessions' => \App\Http\Middleware\MobileDeviceMultipleSession::class,
        'ip-blocker'=>\App\Http\Middleware\BlockIpMiddleware::class,
        'isr_request'=>\App\Http\Middleware\IsrAuthorize::class,
        'OtpVerified'=>\App\Http\Middleware\OtpVerified::class,
        'pending-returns' => \App\Http\Middleware\CheckPendingReturns::class,
        'approved-returns' => \App\Http\Middleware\CheckApprovedReturns::class,
        'unsent-grn-documents' => \App\Http\Middleware\CheckUnsentGrnDocuments::class,
        'auth.check' => \App\Http\Middleware\CheckIfAuthenticated::class,
        // 'jwt_auth'=>\App\Http\Middleware\JWTAuthMiddleware::class,
        'operation-shift-balanced'=> CheckPreviousDayOperationShiftBalanced::class,
        'set-session-lifetime' => \App\Http\Middleware\SetSessionLifetime::class,
        'auth.coop' => AuthenticateCoop::class,
        'branch-close'=>endOfDayLock::class,
        'delivery-driver' => \App\Http\Middleware\DeliveryDriverMiddleware::class,
        'delivery-driver-api' => \App\Http\Middleware\DeliveryDriverApiMiddleware::class,
    ];
}
