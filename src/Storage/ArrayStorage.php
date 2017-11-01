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

namespace Zoe\Component\Cache\Storage;

use Zoe\Component\Cache\Exception\InvalidRegexException;

/**
 * Use the native array as a storage.
 * Store value for the current request
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ArrayStorage implements StorageInterface
{
    
    /**
     * Values stored
     * 
     * @var array
     */
    private $values = [];
    
    /**
     * Expirations time for all values
     * 
     * @var array
     */
    private $expirations = [];
    
    /**
     * Prefix
     * 
     * @var string|null
     */
    private $prefix;
    
    /**
     * Initialize the storage
     * 
     * @param string $prefix
     *   Prefix applied to all keys value
     */
    public function __construct(?string $prefix = null) 
    {
        $this->prefix = $prefix;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::get()
     */
    public function get(string $key): ?string
    {
        $this->prefixKey($key);
        $this->check($key);
        
        if(!isset($this->values[$key])) return null;
        
        return $this->values[$key];
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::set()
     */
    public function set(string $key, string $value): bool
    {
        $this->prefixKey($key);
        
        return $this->doSet($key, null, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::setEx()
     */
    public function setEx(string $key, int $ttl, string $value): bool
    {
        $this->prefixKey($key);
        
        return $this->doSet($key, $ttl, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::del()
     */
    public function del(string $key): bool
    {
        $this->prefixKey($key);
        $this->check($key);
        
        if(!isset($this->values[$key])) return false;
        
        unset($this->values[$key]);
        unset($this->expirations[$key]);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::expire()
     */
    public function expire(string $key): bool
    {
        $this->prefixKey($key);
        $this->check($key);
        
        if(!isset($this->values[$key])) return false;
        
        $this->expirations[$key] = 0;
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::exists()
     */
    public function exists(string $key): bool
    {
        $this->prefixKey($key);
        $this->check($key);
        
        return isset($this->values[$key]);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::rename()
     */
    public function rename(string $key, string $newkey): bool
    {
        $this->prefixKey($key);
        $this->check($key);
        
        if(!isset($this->values[$key])) return false;
        
        $this->prefixKey($newkey);
        $this->check($newkey);
        
        if(isset($this->values[$newkey])) return false;
        
        $value = $this->values[$key];
        $expiration = $this->expirations[$key];
        
        unset($this->values[$key]);
        unset($this->expirations[$key]);
        
        $this->values[$newkey] = $value;
        $this->expirations[$newkey] = $expiration;
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::ttl()
     */
    public function ttl(string $key): ?int
    {
        $this->prefixKey($key);
        $this->check($key);
        
        if(!isset($this->values[$key])) return -1;
        if(null === $this->expirations[$key]) return null;
        
        return $this->expirations[$key] - \time();
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::flush()
     */
    public function flush(): bool
    {
        unset($this->values);
        unset($this->expirations);
        
        $this->values = [];
        $this->expirations = [];
        
        return true;
    }
    
    /**
     * @throws InvalidRegexException
     *   When the pattern is invalid
     * 
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::list()
     */
    public function list(?string $pattern = null): \Generator
    {
        $keys = \array_keys($this->values);
        
        if(null !== $pattern) {
            foreach ($keys as $key) {
                $this->check($key);
                $match = @\preg_match("#{$pattern}#", $key);
                if(false === $match) {
                    throw new InvalidRegexException(\sprintf("This pattern '%s' is invalid", 
                        $pattern));
                }
                if(0 === $match) continue;
                yield $key;
            }
        } else {
            foreach ($keys as $key) {
                $this->check($key);
                yield $key;
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::count()
     */
    public function count(): int
    {
        foreach ($this->values as $key => $value) {
            $this->check($key);
        }
        
        return \count($this->values);
    }
    
    /**
     * Prefix a key
     * 
     * @param string $key
     *   Key to prefix
     */
    private function prefixKey(string& $key): void
    {
        $key = $this->prefix.$key;
    }
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Key value
     * @param int|null $ttl
     *   Time to live
     * @param string $value
     *   Value to store
     *   
     * @return bool
     *   True if the value has been stored correctly. False otherwise
     */
    private function doSet(string $key, ?int $ttl, string $value): bool
    {
        $this->values[$key] = $value;
        $this->expirations[$key] = (null !== $ttl) ? $ttl + \time() : null;
        
        return true;
    }
    
    /**
     * Check if a key is still valid considering a ttl.
     * Will delete the key is expired
     * 
     * @param string $key
     *   Key to check
     */
    private function check(string $key)
    {
        if(!isset($this->values[$key]) || null === $this->expirations[$key]) return;
        
        if($this->expirations[$key] <= \time()) {
            unset($this->values[$key]);
            unset($this->expirations[$key]);
        }
    }

}
