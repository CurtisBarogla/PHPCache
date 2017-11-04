<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Zoe\Component\Cache\Adapter;

/**
 * Adapter accepted and instance of Memcached as a store
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedAdapter implements AdapterInterface
{
    
    /**
     * Memcached store
     * 
     * @var \Memcached
     */
    private $memcached;
    
    /**
     * Initialize the adapter
     * 
     * @param \Memcached $memcached
     *   Memcached instance
     */
    public function __construct(\Memcached $memcached)
    {
        $this->memcached = $memcached;
    }
    
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false !== $value = $this->memcached->get($key)) ? $value : null; 
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): \Generator
    {
        foreach ($keys as $key) {
            if(false !== $value = $this->memcached->get($key))
                yield $key => $value;
            else 
                yield $key => null;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return $this->doSet($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): array
    {
        $results = [];
        foreach ($values as $key => $value) {
            $results[$key] = $this->doSet($key, $value["value"], $value["ttl"]);
        }
        
        return $results;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::del()
     */
    public function del(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::delMultiple()
     */
    public function delMultiple(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->memcached->delete($key);
        }
        
        return $results;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::exists()
     */
    public function exists(string $key): bool
    {
        return false !== $this->memcached->get($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::clear()
     */
    public function clear(?string $pattern = null): bool
    {
        $prefixAltered = false;
        $prefix = $this->memcached->getOption(\Memcached::OPT_PREFIX_KEY);
        if($prefix !== "") {
            $prefixAltered = true;
            $this->memcached->setOption(\Memcached::OPT_PREFIX_KEY, "");
        }
        
        foreach ($this->memcached->getAllKeys() as $key) {
            if(false !== \strpos($key, $prefix.$pattern))
                $this->memcached->delete($key);
        }
        
        if($prefixAltered)
            $this->memcached->setOption(\Memcached::OPT_PREFIX_KEY, $prefix);
            
        return true;
    }
    
    /**
     * Set a value into the memcached store
     * 
     * @param string $key
     *   Key cache value
     * @param string $value
     *   Cache value
     * @param int|null $ttl
     *   Time to live
     * 
     * @return bool
     *   True if the value has been setted correctly. False otherwise
     */
    private function doSet(string $key, string $value, ?int $ttl): bool
    {
        if(null !== $ttl)
            return $this->memcached->set($key, $value, $ttl);
        else 
            return $this->memcached->set($key, $value);
    }

}
