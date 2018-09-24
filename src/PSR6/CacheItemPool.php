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
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Ness\Component\Cache\Exception\CacheException;
use Ness\Component\Cache\Exception\SerializerException;
use Ness\Component\Cache\Serializer\SerializerInterface;

/**
 * PSR6 Cache implementation.
 * Use cache adapter to interact with a cache store
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPool implements CacheItemPoolInterface
{
    
    use ValidationTrait;
    
    /**
     * Default pool ttl applied to non-explicity setted to null CacheItem
     * 
     * @var int|null|\DateTimeInterface|\DateInterval
     */
    private $defaultTtl;
    
    /**
     * Deferred list
     * 
     * @var array[array]
     */
    private $deferred;
    
    /**
     * Adapter used to interact with a cache store
     *
     * @var CacheAdapterInterface
     */
    protected $adapter;
    
    /**
     * Cache pool namespace
     * 
     * @var string|null
     */
    protected $namespace;
    
    /**
     * Serializer state
     * 
     * @var bool
     */
    protected static $serializerInitialized = false;

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
    public const CACHE_FLAG = "@ness_psr6_cache_";
    
    /**
     * Initialize cache pool
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapter
     * @param int|null|\DateTimeInterface|\DateInterval
     *   Default pool ttl applied to non-explicity setted to null CacheItem
     * @param string $namespace
     *   Cache pool namespace (by default setted to global)
     *   
     * @throws InvalidArgumentException
     *   When default ttl is invalid type
     * @throws CacheException
     *   When serializer is not registered
     */
    public function __construct(CacheAdapterInterface $adapter, $defaultTtl = null, string $namespace = "global")
    {
        if(!self::$serializerInitialized)
            throw new CacheException("Serializer is not registered. Did you forget to set it via " . __CLASS__ . "::registerSerializer() method ?");
        
        $this->adapter = $adapter;
        $this->defaultTtl = $this->validateTtl($defaultTtl);
        $this->namespace = $namespace;
    }
    
    /**
     * Commit all non-commited items
     */
    public function __destruct()
    {
        if(null !== $this->deferred)
            $this->commit();
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::getItem()
     */
    public function getItem($key)
    {
        try {
            return (null !== $item = $this->adapter->get($this->validateKey($key))) 
                ? $this->factoryItem($key, $item) 
                : $this->deferred[$this->prefix($key)] ?? new CacheItem($key);            
        } catch (SerializerException $e) {
            return new CacheItem($key);
        }
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
                        try {
                            return (null !== $item) 
                                ? $this->factoryItem($key, $item)
                                : $this->deferred[$this->prefix($key)] ?? new CacheItem($key);                            
                        } catch (SerializerException $e) {
                            return new CacheItem($key);
                        }
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
        $this->adapter->purge(self::CACHE_FLAG.$this->namespace);
        
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
        try {
            return $this->adapter->set($this->prefix($item->getKey()), \json_encode($item), $this->getTtl($item));            
        } catch (SerializerException $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemPoolInterface::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferred[$this->prefix($item->getKey())] = $item;
        
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

        $commit = [];
        foreach ($this->deferred as $key => $item) {
            try {
                $commit[$key] = ["value" => \json_encode($item), "ttl" => $this->getTtl($item)];
                unset($this->deferred[$key]);
            } catch (SerializerException $e) {
                continue;
            } finally {
                if(empty($this->deferred))
                    $this->deferred = null;
            }
        }
        
        return null === $this->adapter->setMultiple($commit) && null === $this->deferred;
    }
    
    /**
     * Register serializer.
     * If a serializer is already setted, nothing will happen
     * 
     * @param SerializerInterface $serializer
     *   Value serializer
     */
    public static function registerSerializer(SerializerInterface $serializer): void
    {
        if(self::$serializerInitialized)
            return;
        
        CacheItem::$serializer = $serializer;
        self::$serializerInitialized = true;
    }
    
    /**
     * Set to null a registered serializer
     */
    public static function unregisterSerializer(): void
    {
        CacheItem::$serializer = null;
        self::$serializerInitialized = false;
    }
    
    /**
     * Initialize a new cache item
     * 
     * @param string $key
     *   Cache item key
     * @param string $item
     *   Item representation
     * 
     * @return CacheItemInterface
     *   Cache item initialized
     */
    protected function factoryItem(string $key, string $item): CacheItemInterface
    {
        return CacheItem::createFromJson($key, $item);
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
        return (CacheItem::DEFAULT_TTL === $item->getTtl()) ? $this->defaultTtl : $item->getTtl();
    }
    
    /**
     * Validate and convert default ttl
     * 
     * @param mixed $ttl
     *   Default pool ttl
     *   
     * @return int|null
     *   Converted ttl
     *   
     * @throws InvalidArgumentException
     *   When given ttl type is not handled
     */
    private function validateTtl($ttl): ?int
    {
        if(\is_int($ttl) || null === $ttl)
            return $ttl;
        
        if($ttl instanceof \DateTimeInterface)
            return $ttl->format("U") - \time();
        
        if($ttl instanceof \DateInterval)
            return (new \DateTime())->add($ttl)->format("U") - \time();
        
        throw new InvalidArgumentException(\sprintf("Default ttl for CachePool MUST be null, an int (time in seconds), an implementation of DateTimeInterface or a DateInterval. '%s' given",
            (\is_object($ttl) ? \get_class($ttl) : \gettype($ttl))));
    }
    
}
