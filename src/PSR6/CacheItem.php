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

use Ness\Component\Cache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

/**
 * Basic implementation of PSR6 CacheItem.
 * This implementation implements Serializable as CachePool implementation will serialize it when stored. <br />
 * So DOT NOT serialize your items yourself...
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItem implements CacheItemInterface, \Serializable
{
 
    /**
     * Cache item key
     * 
     * @var string
     */
    protected $key;
    
    /**
     * Cache value
     * 
     * @var mixed
     */
    protected $value;
    
    /**
     * Ttl of the item in seconds
     * 
     * @var int|float|null
     */
    protected $ttl = \INF;
    
    /**
     * If item if from a cache store
     * 
     * @var bool
     */
    protected $hit = false;
    
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
        return $this->hit;
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
        try {
            $this->ttl = (null === $expiration) ? null : $expiration->format("U") - \time();
        } catch (\Error $e) {
            throw new InvalidArgumentException(\sprintf("Expiration time on '%s' cache item MUST be null or an instance of DateTimeInterface. '%s' given",
                $this->key,
                (\is_object($expiration) ? \get_class($expiration) : \gettype($expiration))));
        }
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::expiresAfter()
     */
    public function expiresAfter($time)
    {
        try {
            $this->ttl = (null === $time || \is_int($time)) ? $time : ((new \DateTime())->add($time))->format("U") - time();
        } catch (\TypeError $e) {
            throw new InvalidArgumentException(\sprintf("Expiration time on '%s' cache item MUST be null an int (representing time in seconds) or an instance of DateInterval. '%s' given",
                $this->key,
                (\is_object($time) ? \get_class($time) : \gettype($time))));
        }
            
        return $this;
    }
    
    /**
     * Get ttl of the cache item.
     * Will return an int or null if an explicit expiration time as been setted or a float if setted for an infinite period of time
     * 
     * @internal
     *   Do not use it outside of the CachePool.
     * 
     * @return int|null|float
     *   Current ttl   
     */
    public function getTtl()
    {
        return $this->ttl; 
    }

    /**
     * {@inheritDoc}
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return \serialize($this->toSerialize());
    }

    /**
     * {@inheritDoc}
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list($this->key, $this->value, $this->hit, $this->ttl) = \unserialize($serialized);
    }
    
    /**
     * Data to pass to serialization
     * 
     * @return array
     *   Cache item datas
     */
    protected function toSerialize(): array
    {
        return [
            $this->key,
            $this->value,
            true,
            $this->ttl
        ];
    }

}
