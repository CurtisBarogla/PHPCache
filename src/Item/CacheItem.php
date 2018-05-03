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

namespace Zoe\Component\Cache\Item;

use Psr\Cache\CacheItemInterface;
use Zoe\Component\Cache\Utils\ValidationTrait;

/**
 * Native basic cache item
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItem implements CacheItemInterface, \Serializable
{
    
    use ValidationTrait;
    
    /**
     * Cache item key
     * 
     * @var string
     */
    private $key;
    
    /**
     * Cache item value
     * 
     * @var mixed
     */
    private $value;
    
    /**
     * Ttl of the item
     * 
     * @var int|float|null
     */
    private $ttl = \INF;
    
    /**
     * Cache hit
     * 
     * @var bool
     */
    private $isHit = false;
    
    /**
     * If value has been normalized
     * 
     * @var bool
     */
    private $normalized = false;
    
    /**
     * Reference for PSR definition
     *
     * @var string
     */
    protected const PSR = "PSR6";
    
    /**
     * Initialize a new cache item
     * 
     * @param string $key
     *   Cache item key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::getKey()
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::get()
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::isHit()
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::set()
     */
    public function set($value)
    {
        $this->value = $value;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::expiresAt()
     */
    public function expiresAt($expiration)
    {
        $this->validateAndConvertExpiration(function($expiration) {
            return $expiration === null || $expiration instanceof \DateTimeInterface;
        }, $expiration, \sprintf("Expiration MUST be an instance of DateTimeInterface or null. '%s' given",
            (\is_object($expiration) ? \get_class($expiration) : \gettype($expiration))));
        
        $this->ttl = $expiration;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::expiresAfter()
     */
    public function expiresAfter($time)
    {
        $this->validateAndConvertExpiration(function($time) {
            return \is_int($time) || $time === null || $time instanceof \DateInterval;
        }, $time, \sprintf("Expiration MUST be an instance of DateTimeInterval an int (seconds) or null. '%s' given",
            (\is_object($time) ? \get_class($time) : \gettype($time))));
        
        $this->ttl = $time;
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return \serialize((object)[
            "key"           =>  $this->key,
            "normalized"    =>  !\is_string($this->value),
            "value"         =>  $this->validateValue($this->value, [$this, "normalize"]),
            "ttl"           =>  $this->ttl,
            "isHit"         =>  $this->isHit
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        $serialized = \unserialize($serialized);
        
        $this->key = $serialized->key;
        $this->value = $serialized->normalized ? \unserialize($serialized->value) : $serialized->value;
        $this->ttl = $serialized->ttl;
        $this->isHit = $serialized->isHit;
        $this->normalized = $serialized->normalized;
    }
    
    /**
     * Ttl of the item.
     * Null if null has been explicitly setted or INF
     * 
     * @return int|float|null
     *   Ttl of the item
     */
    public function getTtl()
    {
        return $this->ttl;
    }
    
    /**
     * Consider the item as a hit. Should be called before the item is stored into the pool
     */
    public function setHit(): void
    {
        $this->isHit = true;
    }
    
    /**
     * Try to serialize a serializable value
     *
     * @param mixed $value
     *   Value to normalize
     * @param string $exception
     *   Exception to throw on error
     *
     * @return string
     *   Serialized value
     */
    protected function normalize($value, string $exception): string
    {
        try {
            return \serialize($value);
        } catch (\Exception $e) {
            throw new $exception("Given value is not handled by this Cache implementation. See message : " . $e->getMessage());
        }
    }

}
