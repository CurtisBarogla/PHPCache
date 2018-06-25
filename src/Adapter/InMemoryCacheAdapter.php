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
 * Use a simple array for storing cache values
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InMemoryCacheAdapter extends AbstractCacheAdapter
{
    
    /**
     * Cached values
     * 
     * @var string[]
     */
    private $cache;
    
    /**
     * Ttl of all caches values
     * 
     * @var int[]
     */
    private $expirations;
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (!$this->gc($key)) ? $this->cache[$key] : null;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        $this->cache[$key] = $value;
        if(null !== $ttl)
            $this->expirations[$key] = $ttl + \time();
        
        return true;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        if($this->gc($key))
            return false;

        unset($this->cache[$key]);
        if(isset($this->expirations[$key]))
            unset($this->expirations[$key]);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        return !$this->gc($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        if(null === $this->cache)
            return;
        
        if(null === $pattern) {
            $this->cache = [];
            $this->expirations = [];
            
            return;
        }
        
        $keys = [];
        foreach (\array_keys($this->cache) as $key)
            if(1 === \preg_match("#{$pattern}#", $key))
                $keys[] = $key;                    
        
        $this->deleteMultiple($keys);
    }
    
    /**
     * Perform a verification over a cache key if still valid
     * 
     * @param string $key
     *   Cache key to verify
     *   
     * @return bool
     *   Return true if the given key has been purged or non stored
     */
    private function gc(string $key): bool
    {
        if(!isset($this->cache[$key]))
            return true;
        
        if(!isset($this->expirations[$key]))
            return false;
        
        if($this->expirations[$key] < \time()) {
            unset($this->cache[$key]);
            unset($this->expirations[$key]);
            
            return true;
        }
        
        return false;
    }
    
}
