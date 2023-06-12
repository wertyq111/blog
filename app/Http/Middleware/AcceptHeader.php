<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AcceptHeader
{
    public function handle(Request $request, Closure $next)
    {

        $request->headers->set('Accept', 'application/json');

        $response = $next($request);
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization');

        return $response;
    }
}
