<?php

namespace Config;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\BaseConfig;

class Cache extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Primary Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used as the primary handler when
     * using the Cache library. The handler is specified below in the
     * $validHandlers array, along with any settings the handler needs.
     */
    public string $handler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Backup Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used if the primary handler is
     * unable to be initialized. Often used to provide a fallback in production
     * environments.
     */
    public string $backupHandler = 'dummy';

    /**
     * --------------------------------------------------------------------------
     * Cache Directory Path
     * --------------------------------------------------------------------------
     *
     * The path to where cache files should be stored. Useed with the File
     * and Wincache handlers.
     */
    public string $storePath = WRITEPATH . 'cache/';

    /**
     * --------------------------------------------------------------------------
     * Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Whether to take the URL query string into consideration when generating
     * output cache files.
     */
    public bool $cacheQueryString = false;

    /**
     * --------------------------------------------------------------------------
     * Key Prefix
     * --------------------------------------------------------------------------
     *
     * This string is added as a prefix to all cache item names to avoid
     * collisions if you run multiple CodeIgniter applications on the same
     * system.
     */
    public string $prefix = 'flight_';

    /**
     * --------------------------------------------------------------------------
     * Default TTL
     * --------------------------------------------------------------------------
     *
     * The default number of seconds to save items when none is specified.
     */
    public int $ttl = 300;

    /**
     * --------------------------------------------------------------------------
     * Reserved Characters
     * --------------------------------------------------------------------------
     *
     * A string of reserved characters that will not be allowed in keys or tags.
     * Defaults to {}()/\@:
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * --------------------------------------------------------------------------
     * File settings
     * --------------------------------------------------------------------------
     * Your file storage preferences, if you're using the File cache handler.
     *
     * @var array{storePath?: string, mode?: int}
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    /**
     * -------------------------------------------------------------------------
     * Memcached settings
     * -------------------------------------------------------------------------
     *
     * Your Memcached servers, if you're using the Memcached cache handler.
     *
     * @see https://codeigniter.com/user_guide/libraries/caching.html#memcached
     *
     * @var array<string, array<string, bool|int|string>>
     */
    public array $memcached = [
        'default' => [
            'host'   => '127.0.0.1',
            'port'   => 11211,
            'weight' => 1,
            'raw'    => false,
        ],
    ];

    /**
     * -------------------------------------------------------------------------
     * Redis settings
     * -------------------------------------------------------------------------
     *
     * Your Redis server, if you're using the Redis or Predis cache handler.
     *
     * @see https://codeigniter.com/user_guide/libraries/caching.html#redis
     *
     * @var array<string, bool|int|string|null>
     */
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    /**
     * -------------------------------------------------------------------------
     * Available Cache Handlers
     * -------------------------------------------------------------------------
     *
     * This is an array of cache engine alias' and class names. If you create
     * your own driver, add it here to allow it to be used throughout
     * the framework. Aliased items may be registered here.
     *
     * @var array<string, class-string<CacheInterface>>
     */
    public array $validHandlers = [
        'dummy'     => \CodeIgniter\Cache\Handlers\DummyHandler::class,
        'file'      => \CodeIgniter\Cache\Handlers\FileHandler::class,
        'memcached' => \CodeIgniter\Cache\Handlers\MemcachedHandler::class,
        'predis'    => \CodeIgniter\Cache\Handlers\PredisHandler::class,
        'redis'     => \CodeIgniter\Cache\Handlers\RedisHandler::class,
        'wincache'  => \CodeIgniter\Cache\Handlers\WincacheHandler::class,
    ];
}
