<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * RateLimitFilter
 *
 * Limits requests per IP using file-based cache.
 * Default: 30 requests per 60 seconds.
 */
class RateLimitFilter implements FilterInterface
{
    protected int $maxRequests;
    protected int $window; // seconds

    public function __construct()
    {
        $this->maxRequests = (int) env('RATE_LIMIT_REQUESTS', 30);
        $this->window      = (int) env('RATE_LIMIT_WINDOW', 60);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $cache = \Config\Services::cache();
        $ip    = $request->getIPAddress();
        $key   = 'rate_limit_' . md5($ip);

        $data = $cache->get($key);

        if ($data === null) {
            $cache->save($key, ['count' => 1, 'reset_at' => time() + $this->window], $this->window);
            return null;
        }

        // Window expired → reset
        if (time() > $data['reset_at']) {
            $cache->save($key, ['count' => 1, 'reset_at' => time() + $this->window], $this->window);
            return null;
        }

        // Over limit
        if ($data['count'] >= $this->maxRequests) {
            $retryAfter = $data['reset_at'] - time();

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setHeader('X-RateLimit-Limit', (string) $this->maxRequests)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('X-RateLimit-Reset', (string) $data['reset_at'])
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after_seconds' => $retryAfter,
                ]);
        }

        // Increment
        $data['count']++;
        $ttl = $data['reset_at'] - time();
        $cache->save($key, $data, max(1, $ttl));

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add rate limit headers to every API response
        $cache = \Config\Services::cache();
        $ip    = $request->getIPAddress();
        $key   = 'rate_limit_' . md5($ip);

        $data = $cache->get($key);
        if ($data) {
            $remaining = max(0, $this->maxRequests - $data['count']);
            $response->setHeader('X-RateLimit-Limit', (string) $this->maxRequests);
            $response->setHeader('X-RateLimit-Remaining', (string) $remaining);
            $response->setHeader('X-RateLimit-Reset', (string) $data['reset_at']);
        }

        return $response;
    }
}
