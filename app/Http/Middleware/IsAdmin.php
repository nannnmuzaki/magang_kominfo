<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Gunakan Gate 'is-admin' yang sudah didefinisikan
        if (! Gate::allows('is-admin')) {
            // Jika bukan admin, hentikan permintaan dan tampilkan halaman error 404 (Not Found)
            abort(404);
        }

        return $next($request);
    }
}
