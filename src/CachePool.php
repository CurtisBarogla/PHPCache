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
use Zoe\Component\Cache\Utils\ValidationTrait;
use Zoe\Component\Cache\Adapter\CacheAdapterInterface;
use Zoe\Component\Cache\Item\CacheItem;

/**
 * Basic implementation of PSR-6 Standard CacheItemPoolInterface.
 * This implementation is based on connecting adapters
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CachePool implements CacheItemPoolInterface
{
    
    use ValidationTrait;
    
    /**
     * Adapter to communicate with an external store
     * 
     * @var CacheAdapterInterface
     */
    protected $adapter;
    
    /**
     * Current "critics" errors from an adapter for the last cache operation.
     * Contains all keys
     *
     * @var array|null
     */
    protected $currentErrors = null;
    
    /**
     * Deferred pool
     * 
     * @var array|null
     */
    protected $deferred = null;
    
    /**
     * Max characters allowed for a valid PSR6 cache key
     * 
     * @var int
     */
    private const MAX_CHARS_ALLOWED = 64;
    
    /**
     * Characters allowed for a valid PSR6 cache key
     * 
     * @var string
     */
    private const ALLOWED_CHARS = "A-Za-z0-9_.{}()/\@:";
    
    /**
     * Reserved characters. Cannot be used into a PSR6 cache key
     * 
     * @var string
     */
    private const RESERVED_CHARS = "{}()/\@:";
    
    /**
     * Reference for PSR definition
     * 
     * @var string
     */
    private const PSR = "PSR6";
    
    /**
     * Flag value for matching only values stored by the cache pool implementation
     *
     * @var string
     */
    public const PSR6_CACHE_FLAG = "CACHE_POOL_PSR6_";
    
    /**
     * Initialize cache pool
     * 
     * @param CacheAdapterInterface $adapter
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
        $this->validateKey(function(string $key) {return \is_string($key);}, $key, ["string"])($key);
        
        return 
            $this->deferred[self::PSR6_CACHE_FLAG.$key] ?? 
            ((null !== $item = $this->adapter->get(self::PSR6_CACHE_FLAG.$key)) ? \unserialize($item) : new CacheItem($key));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItems()
     */
    public function getItems(array $keys = [])
    {
        if(empty($keys)) return [];
        
        $validation = $this->validateKey(null, $keys, ["array"], self::PSR6_CACHE_FLAG);
        \array_walk($keys, $validation);
        
        $items = [];
        foreach ($this->adapter->getMultiple($keys) as $key => $item) {        
            $key = \substr($key, \strlen(self::PSR6_CACHE_FLAG));
            $items[$key] = $this->deferred[self::PSR6_CACHE_FLAG.$key] ?? ((null !== $item) ? \unserialize($item) : new CacheItem($key));
        }
        
        return $items;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::hasItem()
     */
    public function hasItem($key)
    {
        $this->validateKey(function(string $key) {return \is_string($key);}, $key, ["string"], self::PSR6_CACHE_FLAG)($key);
        
        return $this->adapter->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::clear()
     */
    public function clear()
    {
        return $this->adapter->clear(self::PSR6_CACHE_FLAG);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItem()
     */
    public function deleteItem($key)
    {
        $this->validateKey(function(string $key) {return \is_string($key);}, $key, ["string"], self::PSR6_CACHE_FLAG)($key);
        
        ($this->adapter->delete($key)) ?: $this->currentErrors[] = $key;
        
        return null === $this->currentErrors;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::deleteItems()
     */
    public function deleteItems(array $keys)
    {
        $validation = $this->validateKey(null, $keys, ["array"], self::PSR6_CACHE_FLAG);
        \array_walk($keys, $validation);
        
        return null === $this->currentErrors = $this->adapter->deleteMultiple($keys);
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::save()
     */
    public function save(CacheItemInterface $item)
    {
        $item->setHit();
        
        ($result = $this->adapter->set($item->getKey(), \serialize($item), $this->handleExpiration($item))) ?: $this->currentErrors = $item->getKey();
        
        return $result;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[self::PSR6_CACHE_FLAG.$item->getKey()] = $item;
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::commit()
     */
    public function commit()
    {
        if(null === $this->deferred)
            return true;

        $this->currentErrors = $this->adapter->setMultiple(\array_map(function(CacheItem $item): \stdClass {
            $item->setHit();
            
            return (object) ["key" => self::PSR6_CACHE_FLAG.$item->getKey(), "value" => \serialize($item), "ttl" => $this->handleExpiration($item)];
        }, $this->deferred));
        
        return null === $this->deferred = ($this->currentErrors) ? \array_diff_key($this->deferred, $this->currentErrors) : null;
    }
    
    /**
     * Handle the expiration time of the item with infinite value
     * 
     * @param CacheItemInterface $item
     *   Cache item
     * 
     * @return int|null
     *   Null if the ttl of the item is infinite. Ttl in seconds instead
     *   
     * @throws \LogicException
     *   When item does not implement a getTtl() method
     */
    private function handleExpiration(CacheItemInterface $item): ?int
    {
        try {
            return (!\is_infinite($item->getTtl())) ?: null;
        } catch (\TypeError $e) {
            return $item->getTtl();
        }         
    }
    
}
