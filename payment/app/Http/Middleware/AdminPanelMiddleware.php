<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPanelMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $role = $user?->role ?? null;
        if(!$user or !$role){
            return redirect()->route('login', ['referrer' => $request->url()]);
        }
        return $next($request);
    }
}
