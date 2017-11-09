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

namespace Zoe\Component\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Zoe\Component\Cache\Adapter\AdapterInterface;
use Zoe\Component\Cache\Traits\HelpersTrait;
use Zoe\Component\Cache\Exception\CachePool\InvalidArgumentException;

/**
 * Implementation of the PSR-6 CacheItemPoolInterface.
 * Handle interaction with an AdapterInterface to manage CacheItem into a store
 * 
 * @see http://www.php-fig.org/psr/psr-6/
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CachePool implements CacheItemPoolInterface
{
    
    use HelpersTrait;
    
    /**
     * Adapter attached to the cache pool
     * 
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * Deferred list of cache items
     * 
     * @var array
     */
    private $deferred = [];
    
    /**
     * Default ttl in seconds applied to all items setted explicity to null as an expiration time
     * 
     * @var int|null
     */
    private $defaultTtl = null;
    
    /**
     * Initialize the cache pool
     * 
     * @param AdapterInterface $adapter
     *   Adapter implementation
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * Set the default ttl for all item declared explicitly null as expiration time.
     * Set to null to reset to permanent storage
     * 
     * @param int $defaultTtl
     *   Default ttl in seconds
     */
    public function setDefaultTtl(?int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }
    
    /**
     * Switch to an another adapter
     * 
     * @param AdapterInterface $adapter
     *   Adapter implementation
     */
    public function switchAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItem()
     */
    public function getItem($key): CacheItemInterface
    {
        $this->validateKey($key, InvalidArgumentException::class);
        
        return (null !== $item = $this->adapter->get($key)) ? \unserialize($item) : new CacheItem($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItems()
     */
    public function getItems(array $keys = []): array
    {
        if(empty($keys)) return [];
        
        $this->validateKeys($keys, InvalidArgumentException::class);
        
        $items = [];
        foreach ($this->adapter->getMultiple($keys) as $key => $item)
            $items[$key] = (null !== $item) ? \unserialize($item) : new CacheItem($key);
        
        return $items;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::hasItem()
     */
    public function hasItem($key): bool
    {
        $this->validateKey($key, InvalidArgumentException::class);
        
        return $this->adapter->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::clear()
     */
    public function clear(): bool
    {
        unset($this->deferred);
        $this->deferred = [];
        
        return $this->adapter->clear();
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItem()
     */
    public function deleteItem($key): bool
    {
        $this->validateKey($key, InvalidArgumentException::class);
        
        return $this->adapter->del($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItems()
     */
    public function deleteItems(array $keys): bool
    {
        $this->validateKeys($keys, InvalidArgumentException::class);
            
        return false === \array_search(false, $this->adapter->delMultiple($keys), true);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::save()
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->setHit($item, true);
        
        return $this->adapter->set($item->getKey(), \serialize($item), $this->getTtl($item));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::commit()
     */
    public function commit(): bool
    {
        if(empty($this->deferred)) return true;
        
        \array_map(function(string $key, CacheItemInterface $item): void {
            $this->setHit($item, true);
            $this->deferred[$key] = ["value" => \serialize($item), "ttl" => $this->getTtl($item)];
        }, \array_keys($this->deferred), $this->deferred);
        
        $results = $this->adapter->setMultiple($this->deferred);
                
        foreach ($results as $key => $result) {
            if(true === $result)
                unset($this->deferred[$key]);
        }
        
        return empty($this->deferred);
    }
    
    /**
     * Change hit status of a cache item
     * 
     * @param CacheItemInterface $item
     *   Item to set as hit
     * @param bool $hit
     *   True if the item is considered a hit. False otherwise
     */
    private function setHit(CacheItemInterface $item, bool $hit): void
    {
        $reflection = new \ReflectionClass($item);
        
        $property = $reflection->getProperty("isHit");
        $property->setAccessible(true);
        $property->setValue($item, $hit);
    }
    
    /**
     * Get the time to live of a cache item
     * 
     * @param CacheItemInterface $item
     *   Cache item instance
     * 
     * @return int|null
     *   Time to live of the cache item in seconds, or null for permanent storage
     */
    private function getTtl(CacheItemInterface $item): ?int
    {
        $expiration = $item->getExpiration();
        
        if(\is_float($expiration) && \is_infinite($expiration))
            return null;
        
        if($expiration instanceof \DateTimeInterface)
            return $expiration->getTimestamp() - \time();
        
        if(null === $expiration)
            return $this->defaultTtl;
        
        // should never be thrown... but in case
        throw new InvalidArgumentException(\sprintf("This cache item '%s' has an invalid expiration time",
            $item->getKey()));
    }
    
}
