<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Cache\RateLimiter;
class ArenaThrottle
{
    private $limiter;
    public function __construct(RateLimiter $limiter) { $this->limiter = $limiter; }
    public function handle($request, Closure $next, $maximum = 90)
    {
        // Guests behind one classroom Wi-Fi must not share a polling/answer quota.
        $key = 'arena:'.hash('sha256', $request->session()->getId().'|'.$request->route()->getName());
        if ($this->limiter->tooManyAttempts($key, (int) $maximum)) {
            return response()->json(['message' => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.'], 429)->header('Retry-After', $this->limiter->availableIn($key));
        }
        $this->limiter->hit($key, 60); return $next($request);
    }
}
