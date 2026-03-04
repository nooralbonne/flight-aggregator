<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

/**
 * JsonResponseFilter
 *
 * Ensures API routes always return proper JSON content-type.
 */
class JsonResponseFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Only set JSON header for API routes
        $uri = $request->getUri()->getPath();

        if (str_starts_with($uri, '/api/') || str_starts_with($uri, 'api/')) {
            $response->setHeader('Content-Type', 'application/json; charset=UTF-8');
            $response->setHeader('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
