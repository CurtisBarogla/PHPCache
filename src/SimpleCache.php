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

use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Cache\Traits\HelpersTrait;
use Zoe\Component\Cache\Adapter\AdapterInterface;
use Zoe\Component\Cache\Exception\SimpleCache\InvalidArgumentException;

/**
 * Implementation of the PSR-16 CacheInterface
 * Used to store simple data via an adapter
 * 
 * @see http://www.php-fig.org/psr/psr-16/
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SimpleCache implements CacheInterface
{
    
    use HelpersTrait;
    
    /**
     * Adapter interacted with a store
     * 
     * @var AdapterInterface
     */
    private $adapter;
    
    /**
     * Initialize the cache
     * 
     * @param AdapterInterface $adapter
     *   Adapter instance
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::get()
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key, InvalidArgumentException::class);

        return (null !== $value = $this->adapter->get($key)) ? $this->doGet($value) : $default;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::set()
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key, InvalidArgumentException::class);
        $this->validateTtl($ttl);
        
        if(!\is_string($value))
            $value = \serialize($value);
        
        return $this->adapter->set($key, $value, $ttl);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::delete()
     */
    public function delete($key): bool
    {
        $this->validateKey($key, InvalidArgumentException::class);
        
        return $this->adapter->del($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::clear()
     */
    public function clear(): bool
    {
        return $this->adapter->clear();
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::getMultiple()
     */
    public function getMultiple($keys, $defaults = null): iterable
    {
        $this->_validateKeys($keys);
        
        $results = [];
        foreach ($this->adapter->getMultiple($keys) as $key => $value) {
            if(null === $value) $results[$key] = $defaults;
            else $results[$key] = $this->doGet($value);
        }
        
        return $results;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = null): bool
    {
        try {
            $keys = \array_keys($values);
        } catch (\TypeError $e) {
            throw new InvalidArgumentException("Keys must be an iterable");
        }
        $this->validateKeys($keys, InvalidArgumentException::class);
        $this->validateTtl($ttl);
        
        \array_map(function(string $key, &$value) use ($ttl, &$values): void {
            if(!\is_string($value))
                $value = \serialize($value);
            $values[$key] = ["value" => $value, "ttl" => $ttl];
        }, $keys, $values); 
        
        return false === \array_search(false, $this->adapter->setMultiple($values), true);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple($keys): bool
    {
        $this->_validateKeys($keys);
        
        return false === \array_search(false, $this->adapter->delMultiple($keys), true);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::has()
     */
    public function has($key): bool
    {
        $this->validateKey($key, InvalidArgumentException::class);
        
        return $this->adapter->exists($key);
    }
    
    /**
     * Validate type of the given parameter + PSR-16 validation
     * 
     * @param mixed $keys
     *   Keys to validate
     * @throws InvalidArgumentException
     *   When the given parameter is invalid
     */
    private function _validateKeys($keys): void
    {
        if(!\is_array($keys) && !$keys instanceof \Traversable)
            throw new InvalidArgumentException("Keys must be an iterable");
        
        $this->validateKeys($keys, InvalidArgumentException::class);
    }
    
    /**
     * Validate a ttl to correspond PSR-16 recommendations.
     * Normalize ttl if needed
     * 
     * @param mixed|null $ttl
     *   Ttl to validate
     * @throws InvalidArgumentException
     *   If the given ttl if invalid
     */
    private function validateTtl(&$ttl): void
    {
        $isInterval = $ttl instanceof \DateInterval;
        
        if(null !== $ttl && !\is_int($ttl) && !$isInterval)
            throw new InvalidArgumentException(\sprintf("Ttl MUST be null, an int or an instance of DateInterval. '%s' given",
                (\is_object($ttl) ? \get_class($ttl) : \gettype($ttl))));
            
        if($isInterval) {
            $ttl = (new \DateTime())->add($ttl);
            $ttl = $ttl->getTimestamp() - \time(); 
        }
    }
    
}
