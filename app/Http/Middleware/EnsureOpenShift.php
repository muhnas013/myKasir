<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Kasir tanpa shift `open` diarahkan ke Buka Shift (docs/06 P1 langkah 1). */
class EnsureOpenShift
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->activeShift()) {
            return redirect()->route('shift.open');
        }

        return $next($request);
    }
}
