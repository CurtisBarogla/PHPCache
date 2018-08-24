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

use Ness\Component\Cache\Adapter\RedisCacheAdapter;
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Use a redis as cache store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCache extends AbstractCache
{
    
    /**
     * Redis cache
     *
     * @param \Redis $redis
     *   Redis connection
     * @param int|null|\DateInterval $defaultTtl
     *   Default ttl to apply to the cache pool and the cache. Must be compatible
     * @param string|null $namespace
     *   Namespace of the cache. If setted to null, will register cache values into global namespace
     * @param LoggerInterface|null $logger
     *   If a logger is setted, will log errors when a cache value cannot be setted
     *   
     * @throws InvalidArgumentException
     *   When the default ttl is not compatible between PSR6 and PSR16
     */
    public function __construct(
        \Redis $redis, 
        $defaultTtl = null, 
        ?string $namespace = null, 
        ?LoggerInterface $logger = null)
    {
        $this->adapter = new RedisCacheAdapter($redis);
        parent::__construct($defaultTtl, $namespace, $logger);
    }
    
}
