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

namespace Ness\Component\Cache\Adapter;

/**
 * Use apcu internal php storage as cache store.
 * Require obviously apcu extension
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuCacheAdapter extends AbstractCacheAdapter
{

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false !== $value = \apcu_fetch($key)) ? $value : null;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return apcu_store($key, $value, $ttl ?? 0);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        return \apcu_delete($key);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        return \apcu_exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        if(null === $pattern) {
            \apcu_clear_cache();
            
            return;
        }
        
        foreach (new \APCuIterator("#{$pattern}#", APC_ITER_KEY) as $key => $value)         
            \apcu_delete($key);
    }
    
}
