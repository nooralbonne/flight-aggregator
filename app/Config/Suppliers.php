<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Suppliers extends BaseConfig
{
    /**
     * Supplier A configuration
     */
    public array $supplierA = [
        'name'         => 'Supplier A',
        'base_url'     => 'http://localhost:8080/mock/supplier-a',
        'timeout'      => 5,
        'simulate_slow' => false,   // set true to test slow response
        'slow_delay'    => 4,       // seconds delay when simulate_slow=true
    ];

    /**
     * Supplier B configuration
     */
    public array $supplierB = [
        'name'         => 'Supplier B',
        'base_url'     => 'http://localhost:8080/mock/supplier-b',
        'timeout'      => 5,
        'simulate_fail' => false,   // set true to test failure
    ];

    /**
     * Global timeout for all supplier calls (seconds)
     */
    public int $globalTimeout = 5;

    /**
     * Cache TTL for search results (seconds)
     */
    public int $cacheTTL = 120;
}
