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
 * Use a memcached connection as a cache store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedCacheAdapter implements CacheAdapterInterface
{

    /**
     * Memcached connection
     * 
     * @var \Memcached
     */
    private $memcached;
    
    /**
     * Initialize adapter
     * 
     * @param \Memcached $memcached
     *   Memcached connection
     */
    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false !== $value = $this->memcached->get($key)) ? $value : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): array
    {
        $fetched = $this->memcached->getMulti($keys);
        if($this->memcached->getResultMessage() === "NOT FOUND")
            return \array_fill(0, \count($keys), null);
        
        if(\count($fetched) === \count($keys))
            return \array_values($fetched);

        return \array_values(
                    \array_merge(
                        \array_fill(
                            0, 
                            \count(\array_diff_key($keys, \array_keys($fetched))), 
                            null), 
                        $fetched));
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return $this->memcached->set($key, $value, $ttl ?? 0);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): ?array
    {
        $misses = null;
        
        foreach ($values as $key => $value)
            if(!$this->set($key, $value["value"], $value["ttl"]))
                $misses[] = $key;
            
        return $misses;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys): ?array
    {
        return (!\in_array(\Memcached::RES_NOTFOUND, $results = $this->memcached->deleteMulti($keys), true)) 
            ? null 
            : \array_keys($results, \Memcached::RES_NOTFOUND, true);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        if(null === $pattern)
            $this->memcached->flush();
        
        if("" !== $prefix = $this->memcached->getOption(\Memcached::OPT_PREFIX_KEY))
            $this->memcached->setOption(\Memcached::OPT_PREFIX_KEY, "");
            
        foreach ($this->memcached->getAllKeys() as $key) {
            if(1 === \preg_match("#{$pattern}#", $key))
                $this->memcached->delete($key);
        }
        
        if("" !== $prefix)
            $this->memcached->setOption(\Memcached::OPT_PREFIX_KEY, $prefix);
    }

}
