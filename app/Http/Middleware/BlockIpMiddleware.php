<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request as HttpRequest;
class BlockIpMiddleware
{
    // set IP addresses
    public $blockIps = ['197.248.65.101',  "103.59.75.207",'147.47.5.160','196.216.84.140','196.216.91.72','196.216.90.213','5.11.11.5','80.240.202.5'];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(HttpRequest $request, Closure $next)
    {
        // if (!in_array($request->ip(), $this->blockIps)) {
        //     return response()->json("You don't have permission to access this website.");
        // }
        return $next($request);
    }
}