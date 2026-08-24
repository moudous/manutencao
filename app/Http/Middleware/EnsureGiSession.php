<?php

namespace App\Http\Middleware;

use App\Services\GiPermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureGiSession
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->session()->has('gi_context'), 401, 'Abra esta aplicação pelo menu do GI.');
        View::share('giPermissoes', app(GiPermissionService::class));

        return $next($request);
    }
}
