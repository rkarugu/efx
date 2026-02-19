<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Notifications\DatabaseNotification as Notification;
use Symfony\Component\HttpFoundation\Response;

class ReadNotification
{
    public function handle(HttpRequest $request, Closure $next): Response
    {
        if ($request->has('notification')) {
            $notification = Notification::find($request->get('notification'));
            if (!is_null($notification)) {
                $notification->markAsRead();
            }
        }
        
        return $next($request);
    }
}
