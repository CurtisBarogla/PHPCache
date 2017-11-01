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

use Zoe\Component\Cache\Storage\StorageInterface;

/**
 * Adapter accepted instance of StorageInterface as store
 * 
 * @see \Zoe\Component\Cache\Storage\StorageInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class StorageAdapter implements AdapterInterface
{
    
    /**
     * Store instance
     * 
     * @var StorageInterface
     */
    private $store;
    
    /**
     * Initialize the adapter
     * 
     * @param StorageInterface $store
     *   StoreInterface instance
     */
    public function __construct(StorageInterface $store)
    {
        $this->store = $store;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return $this->store->get($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): \Generator
    {
        foreach ($keys as $key) {
            if(null === $value = $this->store->get($key))
                yield $key => null;
            else
                yield $key => $value;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        if(null !== $ttl)
            return $this->store->setEx($key, $ttl, $value);
        else
            return $this->store->set($key, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value)
            ($this->set($key, $value["value"], $value["ttl"])) ? $result[$key] = true : $result[$key] = false; 
            
        return $result;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::del()
     */
    public function del(string $key): bool
    {
        return $this->store->del($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::delMultiple()
     */
    public function delMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key)
            ($this->store->del($key)) ? $result[$key] = true : $result[$key] = false;
            
        return $result;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::exists()
     */
    public function exists(string $key): bool
    {
        return $this->store->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::clear()
     */
    public function clear(?string $pattern = null): bool
    {
        $error = false;
        foreach ($this->store->list($pattern) as $key) {
            if(false === $this->store->del($key)) $error = true;;
        }
        
        return $error !== true;
    }
    
}
