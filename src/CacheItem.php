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
use Zoe\Component\Cache\Exception\CachePool\InvalidArgumentException;
use Zoe\Component\Cache\Traits\HelpersTrait;

/**
 * Cache item stored into a CacheItemPool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItem implements CacheItemInterface
{

    use HelpersTrait;
    
    /**
     * Cache key
     * 
     * @var string
     */
    private $key;
    
    /**
     * Cache value
     * 
     * @var mixed
     */
    private $value;
    
    /**
     * Expiration time
     * 
     * @var \DateTimeInterface|float|null
     */
    private $expiration = \INF;
    
    /**
     * If the cache value is a hit from a cache pool
     * 
     * @var string
     */
    private $isHit = false;
    
    /**
     * Initialize the cache item
     * 
     * @param string $key
     *   Cache key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::getKey()
     */
    public function getKey(): string
    {
        return $this->key;        
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::get()
     */
    public function get()
    {
        return $this->doGet($this->value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::isHit()
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }
    
    /**
     * @throws InvalidArgumentException
     *   When impossible to serialize (if needed) the given value
     * 
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::set()
     */
    public function set($value): self
    {
        if(!\is_string($value)) {
            if(\is_resource($value)) {
                throw new InvalidArgumentException("Impossible to serialize resource value");
            }        
            try {
                $this->value = \serialize($value);
            } catch (\Exception $e) {
                throw new InvalidArgumentException(\sprintf("Cannot serialize anonymous '%s'",
                    ($value instanceof \Closure) ? "function" : "object"));
            }
        } else 
            $this->value = $value;
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::expiresAt()
     */
    public function expiresAt($expiration): self
    {
        if($expiration instanceof \DateTimeInterface)
            $this->expiration = $expiration;
        else if(null === $expiration)
            $this->expiration = null;
        else
            throw new InvalidArgumentException(\sprintf("Expiration date MUST be an instance of DateTimeInterface or null. '%s' given",
                \is_object($expiration) ? \get_class($expiration) : \gettype($expiration)));
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\Cache\CacheItemInterface::expiresAfter()
     */
    public function expiresAfter($time): self
    {
        if(\is_int($time))
            $this->expiration = new \DateTime("NOW + {$time} seconds");
        else if($time instanceof \DateInterval)
            $this->expiration = (new \DateTime())->add($time);            
        else if(null === $time)
            $this->expiration = null;
        else
            throw new InvalidArgumentException(\sprintf("Expiration time MUST be an instance of DateInterval, an int or null. '%s' given",
                \is_object($time) ? \get_class($time) : \gettype($time)));
        
        return $this;
    }
    
    /**
     * Get the expiration time of the item
     * 
     * @return \DateTimeInterface|float|int|null
     *   Expiration time
     */
    public function getExpiration()
    {
        return $this->expiration;
    }
    
}
