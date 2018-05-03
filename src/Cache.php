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
use Zoe\Component\Cache\Utils\ValidationTrait;
use Zoe\Component\Cache\Adapter\CacheAdapterInterface;

/**
 * Basic implementation of PSR-16 Standard CacheInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class Cache implements CacheInterface
{
    
    use ValidationTrait;
    
    /**
     * Adapter used to store values
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
     * Max characters allowed for a valid PSR16 cache key
     *
     * @var int
     */
    private const MAX_CHARS_ALLOWED = 64;
    
    /**
     * Characters allowed for a valid PSR16 cache key
     *
     * @var string
     */
    private const ALLOWED_CHARS = "A-Za-z0-9_.{}()/\@:";
    
    /**
     * Reserved characters. Cannot be used into a PSR16 cache key
     *
     * @var string
     */
    private const RESERVED_CHARS = "{}()/\@:";
    
    /**
     * Reference for PSR definition
     *
     * @var string
     */
    protected const PSR = "PSR16";
    
    /**
     * Flag value for matching only values stored by the cache implementation
     * 
     * @var string
     */
    public const PSR16_CACHE_FLAG = "CACHE_PSR16_";
    
    /**
     * Initialize cache
     * 
     * @param CacheAdapterInterface $adapter
     *   Adapter used to store cache value
     */
    public function __construct(CacheAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::get()
     */
    public function get($key, $default = null)
    {
        $this->validateKey($this->verify(false), $key, ["string"], self::PSR16_CACHE_FLAG)($key);
        
        return (null !== $value = $this->adapter->get($key)) ? $this->checkSerialized($key, $value) : $default;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::set()
     */
    public function set($key, $value, $ttl = null)
    {
        $this->validateKey($this->verify(false), $key, ["string"], self::PSR16_CACHE_FLAG)($key);
        $this->verifyTtl($ttl);
       
        ( ($result = $this->adapter->set($key, $this->validateValue($value, [$this, "normalize"]), $ttl)) ) ?: $this->currentErrors[] = $key;
        
        return null === $this->currentErrors;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::delete()
     */
    public function delete($key)
    {
        $this->validateKey($this->verify(false), $key, ["string"], self::PSR16_CACHE_FLAG)($key);

        ($this->adapter->delete($key)) ?: $this->currentErrors[] = $key;
        
        return null === $this->currentErrors;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::clear()
     */
    public function clear()
    {
        return $this->adapter->clear(self::PSR16_CACHE_FLAG);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::getMultiple()
     */
    public function getMultiple($keys, $default = null)
    {
        $validation = $this->validateKey($this->verify(true), $keys, ["iterable", "array"], self::PSR16_CACHE_FLAG);
        \array_walk($keys, $validation);
        
        $values = [];
        foreach ($this->adapter->getMultiple($keys) as $key => $value) {
            $values[\substr($key, \strlen(self::PSR16_CACHE_FLAG))] = 
                (null !== $value) ? $this->checkSerialized($key, $value) : $default;
        }
        
        return $values;
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = null)
    {
        $validation = $this->validateKey($this->verify(true), $values, ["iterable", "array"], self::PSR16_CACHE_FLAG);
        $this->verifyTtl($ttl);
        $keys = \array_keys($values);
        \array_walk($keys, $validation);

        return null === $this->currentErrors = $this->adapter->setMultiple(array_map(function(string $key, $value) use ($ttl) {
            return (object) ["key" => $key, "value" => $this->validateValue($value, [$this, "normalize"]), "ttl" => $ttl];
        }, $keys, $values));
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::deleteMultiple()
     */
    public function deleteMultiple($keys)
    {
        $validation = $this->validateKey($this->verify(true), $keys, ["iterable", "array"], self::PSR16_CACHE_FLAG);
        \array_walk($keys, $validation);

        return null === $this->currentErrors = $this->adapter->deleteMultiple($keys);
    }
    
    /**
     * {@inheritDoc}
     * @see \Psr\SimpleCache\CacheInterface::has()
     */
    public function has($key)
    {
        $this->validateKey($this->verify(false), $key, ["string"], self::PSR16_CACHE_FLAG)($key);
        
        return $this->adapter->exists($key);
    }
    
    /**
     * Verify type
     * 
     * @param bool $multiple
     *   True if multiple types are accepted
     * 
     * @return callable
     *   Verification to execute
     */
    protected function verify(bool $multiple): callable
    {
        $this->currentErrors = null;
        
        return ($multiple) 
            ? function ($value): bool {
                return $value instanceOf \Traversable || is_array($value);
        }   : function ($value): bool {
                return is_string($value);
        };
    }
    
    /**
     * Verify the ttl of a cache value
     * 
     * @param mixed $ttl
     *   Ttl value to verify
     */
    protected function verifyTtl(&$ttl): void
    {
        $this->validateAndConvertExpiration(function($ttl): bool {
            return \is_int($ttl) || \is_null($ttl) || $ttl instanceof \DateInterval;
        }, $ttl, "Ttl MUST be an instance of DateInterval or an integer (time in seconds), or null");
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
    
    /**
     * Check a serialized value and unserialized it if needed
     * 
     * @param string $key
     *   Key for logging on error
     * @param string $value
     *   Value to check
     *   
     * @return mixed
     *   Unserialized value if needed
     */
    protected function checkSerialized(string $key, string $value)
    {
        if($value === "N;")
            return null;
        
        if($value[1] !== ":" || false === \strpbrk($value[0], "adObis"))
            return $value;
        
        if($value === "b:0;")
            return false;
        
        if(false !== $unserialized = \unserialize($value))
            return $unserialized;

        $this->currentErrors[] = $key;
        
        return $value;
    }
    
}
