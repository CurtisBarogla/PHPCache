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

use Ness\Component\Cache\Adapter\NullCacheAdapter;
use Psr\Log\LoggerInterface;

/**
 * Null cache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NullCache extends AbstractCache
{
    
    /**
     * Null cache
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
        $this->adapter = new NullCacheAdapter();
        parent::__construct(null, null, null);
    }
    
}
