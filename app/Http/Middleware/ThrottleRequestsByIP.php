<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsByIP
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter()->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        $this->limiter()->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    protected function resolveRequestSignature($request)
    {
        return sha1($request->ip());
    }

    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    protected function buildResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter()->availableIn($key);

        return response()->json([
            'error' => 'Too many requests. Please try again later.',
        ], Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => $retryAfter,
        ]);
    }

    protected function addHeaders($response, $maxAttempts, $remainingAttempts)
    {
        return $response;
    }

    protected function calculateRemainingAttempts($key, $maxAttempts)
    {
        return $this->limiter()->retriesLeft($key, $maxAttempts);
    }
}
