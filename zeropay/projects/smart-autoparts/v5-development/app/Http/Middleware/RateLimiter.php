<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimiter
{
    protected $limits = [
        'api' => ['requests' => 60, 'minutes' => 1],
        'auth' => ['requests' => 5, 'minutes' => 1],
        'payment' => ['requests' => 10, 'minutes' => 5],
        'search' => ['requests' => 30, 'minutes' => 1],
    ];
    
    public function handle(Request $request, Closure $next, string $type = 'api')
    {
        $key = $this->resolveRequestSignature($request, $type);
        $limit = $this->limits[$type] ?? $this->limits['api'];
        
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= $limit['requests']) {
            return $this->buildResponse($key, $limit);
        }
        
        Cache::put($key, $attempts + 1, now()->addMinutes($limit['minutes']));
        
        $response = $next($request);
        
        return $this->addHeaders($response, $limit, $attempts);
    }
    
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();
        $identifier = $user ? $user->id : $request->ip();
        
        return sprintf('rate_limit:%s:%s:%s', $type, $identifier, $request->path());
    }
    
    protected function buildResponse(string $key, array $limit): Response
    {
        $retryAfter = Cache::get($key . ':timer', now()->addMinutes($limit['minutes']))->diffInSeconds();
        
        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $limit['requests'],
            'X-RateLimit-Remaining' => 0,
        ]);
    }
    
    protected function addHeaders(Response $response, array $limit, int $attempts): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit['requests'],
            'X-RateLimit-Remaining' => max(0, $limit['requests'] - $attempts - 1),
            'X-RateLimit-Reset' => now()->addMinutes($limit['minutes'])->timestamp,
        ]);
    }
}