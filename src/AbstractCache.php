<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Ness\Component\Cache;

use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Cache\Traits\CacheTrait;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\PSR16\Cache;
use Ness\Component\Cache\PSR6\CacheItemPool;
use Psr\Log\LoggerInterface;
use Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter;

/**
 * Common to all caches compliants with PSR6 and PSR16
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractCache implements CacheInterface, CacheItemPoolInterface
{
    
    use CacheTrait;
    
    /**
     * Cache pool
     * 
     * @var CacheItemPoolInterface
     */
    private $pool;
    
    /**
     * Simple cache
     * 
     * @var CacheInterface
     */
    private $cache;
    
    /**
     * Adapter used
     * 
     * @var CacheAdapterInterface
     */
    protected $adapter;
    
    /**
     * Initialize PSR6 and PSR16
     * 
     * @param int|null|\DateInterval $defaultTtl
     *   Default ttl to apply to the cache pool and the cache. Must be compatible
     * @param string|null $namespace
     *   Namespace of the cache. If setted to null, will register cache values into global namespace
     * @param LoggerInterface|null $logger
     *   If a logger is setted, will log errors when a cache value cannot be setted
     */
    public function __construct($defaultTtl = null, ?string $namespace = null, ?LoggerInterface $logger = null)
    {
        if(null !== $logger) {
            $this->adapter = new LoggingWrapperCacheAdapter($this->adapter);
            $this->adapter->setLogger($logger);
        }
        
        $this->cache = new Cache($this->adapter, $defaultTtl, $namespace ?? "global");
        $this->pool = new CacheItemPool($this->adapter, $defaultTtl, $namespace ?? "global");
        
        unset($this->adapter);
    }
    
}
