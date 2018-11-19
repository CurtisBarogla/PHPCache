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

namespace Ness\Component\Cache\PSR16;

use Psr\SimpleCache\CacheInterface;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\Traits\ValidationTrait;
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Ness\Component\Cache\Serializer\SerializerInterface;
use Ness\Component\Cache\Exception\CacheException;
use Ness\Component\Cache\Exception\SerializerException;

/**
 * PSR16 Cache implementation.
 * Use cache adapter to interact with a cache store.
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Cache implements CacheInterface
{
    
    use ValidationTrait;
    
    /**
     * Default ttl applied
     * 
     * @var int|\DateInterval|null
     */
    private $defaultTtl;
        
    /**
     * Value serializer
     * 
     * @var SerializerInterface
     */
    protected static $serializer;
    
    /**
     * Cache namespace
     * 
     * @var string|null
     */
    protected $namespace;
    
    /**
     * Adapter used to interact with a cache store
     * 
     * @var CacheAdapterInterface
     */
    protected $adapter;
    
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
    public const CACHE_FLAG = "@ness_psr16_cache_";
    
    /**
     * Initialize cache
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapater
     * @param int|\DateInterval|null
     *   Default ttl applied to all ttl non-explicitly declared
     * @param string $namespace
     *   Cache namespace
     *   
     * @throws InvalidArgumentException
     *   When default ttl is invalid
     * @throws CacheException
     *   When serializer not registered
     */
    public function __construct(CacheAdapterInterface $adapter, $defaultTtl = null, string $namespace = "global")
    {
        if(null === self::$serializer)
            throw new CacheException("Serializer is not registered. Did you forget to set it via " . __CLASS__ . "::registerSerializer() method ?");
        
        $this->adapter = $adapter;
        $this->defaultTtl = $this->getTtl($defaultTtl);
        $this->namespace = $namespace;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::get()
     */
    public function get($key, $default = null)
    {
        try {
            return (null !== $value = $this->adapter->get($this->validateKey($key))) ? self::$serializer->unserialize($value) : $default;            
        } catch (SerializerException $e) {
            return $default;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::set()
     */
    public function set($key, $value, $ttl = -1)
    {
        try {
            return $this->adapter->set($this->validateKey($key), (!\is_string($value)) ? self::$serializer->serialize($value) : $value, $this->getTtl($ttl));            
        } catch (SerializerException $e) {
            return false;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::delete()
     */
    public function delete($key)
    {
        return $this->adapter->delete($this->validateKey($key));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::clear()
     */
    public function clear()
    {
        $this->adapter->purge(self::CACHE_FLAG.$this->namespace);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::getMultiple()
     */
    public function getMultiple($keys, $default = null)
    {
        $this->validateIterable($keys);
        return \array_combine($keys, \array_map(function(?string $value) use ($default) {
            try {
                return (null === $value) ? $default : self::$serializer->unserialize($value);                
            } catch (SerializerException $e) {
                return $default;
            }
        }, $this->adapter->getMultiple(\array_map([$this, "validateKey"], $keys))));
    }

    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = -1)
    {
        $this->validateIterable($values);
        $commit = [];
        $error = false;
        foreach ($values as $key => $value) {
            try {
                $commit[$key] = [
                    "value" => (!\is_string($value)) ? self::$serializer->serialize($value) : $value,
                    "ttl" => $this->getTtl($ttl)
                ];
            } catch (SerializerException $e) {
                $error = true;
                unset($values[$key]);
                continue;
            }
        }
        return null === $this->adapter->setMultiple(\array_combine(\array_map([$this, "validateKey"], \array_keys($values)), $commit)) && !$error;            
    }

    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple($keys)
    {
        $this->validateIterable($keys);
        
        return null === $this->adapter->deleteMultiple(\array_map([$this, "validateKey"], $keys));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::has()
     */
    public function has($key)
    {
        return $this->adapter->has($this->validateKey($key));
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
        if(null !== self::$serializer)
            return;
        
        self::$serializer = $serializer;
    }
    
    /**
     * Set to null a registered serializer
     */
    public static function unregisterSerializer(): void
    {
        self::$serializer = null;
    }
    
    /**
     * Validate and convert value that must be passed to the adapter
     * 
     * @param mixed $values
     *   Values to validate
     * 
     * @throws InvalidArgumentException
     *   When not a valid value
     */
    protected function validateIterable(&$values): void
    {
        if($values instanceof \Traversable) {
            $values = \iterator_to_array($values);
            return;
        }
        
        if(!\is_array($values))
            throw new InvalidArgumentException(\sprintf("Values/Keys MUST be an array or a Traversable implementation. '%s' given",
                (\is_object($values)) ? \get_class($values) : \gettype($values)));
    }
    
    /**
     * Determine ttl from
     * 
     * @param int|null|\DateInterval $ttl
     *   Accepted type for a ttl
     * @return int|null
     *   Ttl in second or null
     * 
     * @throws InvalidArgumentException
     *   When not a valid type
     */
    protected function getTtl($ttl): ?int
    {
        try {
            if(\is_int($ttl))
                return ($ttl === -1) ? $this->defaultTtl : $ttl;
            
            return (null === $ttl) ? $ttl : (new \DateTime())->add($ttl)->format("U") - time();
        } catch (\TypeError $e) {
            throw new InvalidArgumentException(\sprintf("Ttl MUST be null or an int (time in seconds) or an instance of DateInterval. '%s' given",
                (\is_object($ttl) ? \get_class($ttl) : \gettype($ttl))));
        }
    }
    
}
