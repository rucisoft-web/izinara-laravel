<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

/** Sebelum terpasang semua halaman diarahkan ke wizard /install; sesudahnya /install ditutup. */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $onInstaller = $request->is('install') || $request->is('install/*');
        if (! Installer::installed() && ! $onInstaller) {
            return redirect('/install');
        }
        if (Installer::installed() && $onInstaller) {
            abort(404);
        }

        return $next($request);
    }
}
