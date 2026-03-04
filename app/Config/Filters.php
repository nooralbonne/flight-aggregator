<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Filters\RateLimitFilter;
use App\Filters\JsonResponseFilter;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'          => \CodeIgniter\Filters\CSRF::class,
        'toolbar'       => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'      => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars'  => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'ratelimit'     => RateLimitFilter::class,
        'jsonresponse'  => JsonResponseFilter::class,
    ];

    public array $required = [
        \CodeIgniter\Filters\ForceHTTPS::class        => 'before',
        \CodeIgniter\Filters\PageCache::class         => 'before',
        \CodeIgniter\Filters\PerformanceMetrics::class => 'after',
        \CodeIgniter\Filters\DebugToolbar::class      => 'after',
    ];

    public array $globals = [
        'before' => [],
        'after'  => [],
    ];

    public array $methods = [];

    public array $filters = [
        'ratelimit' => [
            'before' => ['api/flights/*'],
        ],
        'jsonresponse' => [
            'after' => ['api/*'],
        ],
    ];
}
