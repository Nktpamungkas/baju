<?php

namespace App\Http\Middleware;

use App\Domains\Auth\Service\AdminAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function __construct(private AdminAuthService $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->auth->check($request)) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
