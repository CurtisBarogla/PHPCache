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

use Ness\Component\Cache\Adapter\MemcachedCacheAdapter;
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Use a memcached as cache store
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedCache extends AbstractCache
{
    
    /**
     * Memcached cache
     *
     * @param \Memcached $redis
     *   Memcached connection
     * @param int|null|\DateInterval $defaultTtl
     *   Default ttl to apply to the cache pool and the cache. Must be compatible
     * @param string|null $namespace
     *   Namespace of the cache. If setted to null, will register cache values into global namespace
     * @param LoggerInterface|null $logger
     *   If a logger is setted, will log errors when a cache value cannot be setted
     * @param bool $tagSupport
     *   If cache components handle tags support on cached values
     *   
     * @throws InvalidArgumentException
     *   When the default ttl is not compatible between PSR6 and PSR16
     */
    public function __construct(
        \Memcached $memcached, 
        $defaultTtl = null, 
        ?string $namespace = null, 
        ?LoggerInterface $logger = null,
        bool $tagSupport = false)
    {
        $this->adapter = new MemcachedCacheAdapter($memcached);
        parent::__construct($defaultTtl, $namespace, $logger, $tagSupport);
    }
    
}
