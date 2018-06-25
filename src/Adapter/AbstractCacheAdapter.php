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
 * Common to all CacheAdapter
 * Naively loop for setting multiple values over the single method (get, set, delete) if not overloaded.
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractCacheAdapter implements CacheAdapterInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): array
    {
        return \array_map([$this, "get"], $keys);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): ?array
    {
        foreach ($values as $key => $value)
            if($this->set($key, $value["value"], $value["ttl"]))
                unset($values[$key]);
                
        return empty($values) ? null : \array_keys($values);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys): ?array
    {
        foreach ($keys as $index => $key)
            if($this->delete($key))
                unset($keys[$index]);
                
        return empty($keys) ? null : \array_values($keys);
    }
    
}
