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
use Ness\Component\Cache\Exception\CacheException;

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
     * Cache namespace
     * 
     * @var string|null
     */
    private $namespace;
    
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
    public const CACHE_FLAG = "psr16_cache_";
    
    /**
     * Initialize cache
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapater
     * @param int|\DateInterval|null
     *   Default ttl applied to all ttl non-explicitly declared
     * @param string $namespace
     *   Cache namespace
     */
    public function __construct(CacheAdapterInterface $adapter, $defaultTtl = null, string $namespace = "global")
    {
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
        return (null !== $value = $this->adapter->get($this->validateKey($key))) ? $this->isSerialize($value) : $default;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::set()
     */
    public function set($key, $value, $ttl = -1)
    {
        return $this->adapter->set($this->validateKey($key), (!\is_string($value)) ? \serialize($value) : $value, $this->getTtl($ttl));
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
            return (null === $value) ? $default : $this->isSerialize($value);
        }, $this->adapter->getMultiple(\array_map([$this, "validateKey"], $keys))));
    }

    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = -1)
    {
        $this->validateIterable($values);
        return null === $this->adapter->setMultiple(
            \array_combine(
                \array_map([$this, "validateKey"], \array_keys($values)), 
                \array_map(function($value) use ($ttl) {
                    return 
                        [
                            "value" => (!\is_string($value)) ? \serialize($value) : $value, 
                            "ttl" => $this->getTtl($ttl)
                        ];
        }, $values)));            
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
     * Check if the value has been serialized.
     * Unserialize it if needed
     * 
     * @param string $value
     *   Value to check
     *   
     * @return mixed
     *   Value unserialized if needed
     */
    protected function isSerialize(string $value)
    {
        if(!isset($value[0]) || !isset($value[1]))
            return $value;
        
        if("N;" === $value) {
            return null;
        }
        
        if($value[1] !== ":") {
            return $value;
        }
        
        if("b:0;" === $value) {
            return false;
        }

        return (false !== $unserialized = @\unserialize($value)) ? $unserialized : $value;
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
     * @throws CacheException
     *   When not a valid type
     */
    protected function getTtl($ttl): ?int
    {
        try {
            if(\is_int($ttl))
                return ($ttl === -1) ? $this->defaultTtl : $ttl;
            
            return (null === $ttl) ? $ttl : (new \DateTime())->add($ttl)->format("U") - time();
        } catch (\TypeError $e) {
            throw new CacheException(\sprintf("Ttl MUST be null or an int (time in seconds) or an instance of DateInterval. '%s' given",
                (\is_object($ttl) ? \get_class($ttl) : \gettype($ttl))));
        }
    }
    
}
