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

namespace Ness\Component\Cache\Traits;

use Psr\Cache\CacheItemInterface;

/**
 * Make a component compliant with PSR-6 and PSR-16
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait CacheTrait
{
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::get()
     */
    public function get($key, $default = null)
    {
        return $this->cache->get($key, $default);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::set()
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::delete()
     */
    public function delete($key): bool
    {
        return $this->cache->delete($key);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::clear()
     * @see \Psr\Cache\CacheItemPoolInterface::clear()
     */
    public function clear(): bool
    {
        return $this->cache->clear() && $this->pool->clear();
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::getMultiple()
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->cache->getMultiple($keys, $default);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple($keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\SimpleCache\CacheInterface::has()
     */
    public function has($key): bool
    {
        return $this->cache->has($key);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItem()
     */
    public function getItem($key): CacheItemInterface
    {
        return $this->pool->getItem($key);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItems()
     */
    public function getItems(array $keys = array())
    {
        return $this->pool->getItems($keys);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::hasItem()
     */
    public function hasItem($key): bool
    {
        return $this->pool->hasItem($key);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItem()
     */
    public function deleteItem($key): bool
    {
        return $this->pool->deleteItem($key);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItems()
     */
    public function deleteItems(array $keys): bool
    {
        return $this->pool->deleteItems($keys);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::save()
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->pool->save($item);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->pool->saveDeferred($item);
    }
    
    /**
     * {@inheritdoc}
     * @see \Psr\Cache\CacheItemPoolInterface::commit()
     */
    public function commit(): bool
    {
        return $this->pool->commit();
    }
    
}
