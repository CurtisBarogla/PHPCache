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

use Ness\Component\Cache\Adapter\FilesystemCacheAdapter;
use Psr\Log\LoggerInterface;

/**
 * Use local filesystem as cache store
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemCache extends AbstractCache
{
    
    /**
     * Filesystem cache
     * 
     * @param string $cacheDirectory
     *   Cache directory
     * @param int|null|\DateInterval $defaultTtl
     *   Default ttl to apply to the cache pool and the cache. Must be compatible
     * @param string|null $namespace
     *   Namespace of the cache. If setted to null, will register cache values into global namespace
     * @param LoggerInterface|null $logger
     *   If a logger is setted, will log errors when a cache value cannot be setted
     */
    public function __construct(
        string $cacheDirectory, 
        $defaultTtl = null, 
        ?string $namespace = null, 
        ?LoggerInterface $logger = null)
    {
        $this->adapter = new FilesystemCacheAdapter($cacheDirectory);
        parent::__construct($defaultTtl, $namespace, $logger);
    }
    
}
