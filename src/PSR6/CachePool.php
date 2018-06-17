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

namespace Ness\Component\Cache\PSR6;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Cache\Traits\ValidationTrait;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;

/**
 * PSR6 Cache implementation.
 * Use cache adapter to interact with a cache store
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CachePool implements CacheItemPoolInterface
{
    
    use ValidationTrait;
    
    /**
     * Adapter used to interact with a cache store
     *
     * @var CacheAdapterInterface
     */
    protected $adapter;
    
    /**
     * Deferred list
     * 
     * @var array[array]
     */
    private $deferred;
    
    /**
     * List of characters accepted
     *
     * @var string
     */
    public const ACCEPTED_CHARACTERS = "A-Za-z0-9_.{}()/\@:";
    
    /**
     * List of reserved characters
     *
     * @var string
     */
    public const RESERVED_CHARACTERS = "{}()/\@:";
    
    /**
     * Max length allowed
     *
     * @var int
     */
    public const MAX_LENGTH = 64;
    
    /**
     * Identifier to mark values cached by this implementation
     *
     * @var string
     */
    public const CACHE_FLAG = "psr6_cache_";
    
    /**
     * Initialize cache pool
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapter
     */
    public function __construct(CacheAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItem()
     */
    public function getItem($key)
    {
        return (null !== $item = $this->adapter->get($this->validateKey($key))) ? \unserialize($item) : new CacheItem($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItems()
     */
    public function getItems(array $keys = [])
    {
        if(empty($keys))
            return [];
        
        return \array_combine(
                    $keys,
                    \array_map(function(?string $item, string $key): CacheItemInterface {
                        return (null !== $item) ? \unserialize($item) : new CacheItem($key);
                    }, $this->adapter->getMultiple(\array_map([$this, "validateKey"], $keys)), $keys));
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::hasItem()
     */
    public function hasItem($key)
    {
        return $this->adapter->has($this->validateKey($key));
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::clear()
     */
    public function clear()
    {
        $this->adapter->purge(self::CACHE_FLAG);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItem()
     */
    public function deleteItem($key)
    {
        return $this->adapter->delete($this->validateKey($key));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItems()
     */
    public function deleteItems(array $keys)
    {
        return null === $this->adapter->deleteMultiple(\array_map([$this, "validateKey"], $keys));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::save()
     */
    public function save(CacheItemInterface $item)
    {
        return $this->adapter->set(self::CACHE_FLAG.$item->getKey(), \serialize($item), $this->getTtl($item));
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[self::CACHE_FLAG.$item->getKey()] = ["value" => \serialize($item), "ttl" => $this->getTtl($item)];
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::commit()
     */
    public function commit()
    {
        if(null === $this->deferred)
            return true;
        
        $result = null === $this->adapter->setMultiple($this->deferred);
        $this->deferred = null;
        
        return $result;
    }
    
    /**
     * Determine correct ttl over a cache item
     * 
     * @param CacheItem $item
     *   Cache item
     * 
     * @return int|null
     *   Time in seconds or null
     */
    private function getTtl(CacheItem $item): ?int
    {
        return (\is_float($item->getTtl())) ? null : $item->getTtl();
    }
    
}
