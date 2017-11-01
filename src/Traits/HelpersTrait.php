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

namespace Zoe\Component\Cache\Traits;

/**
 * Common helpers for Cache component
 * Handle data validation, data treatments etc...
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait HelpersTrait
{
    
    /**
     * Validate a key
     *
     * @param string $key
     *   Key to validate
     * @param string $classNameException
     *   Name of the class for the exception
     *
     * @throws \Exception
     *   When the given key is invalid
     */
    protected function validateKey(string $key, string $classNameException): void
    {
        if(\strlen($key) > 64)
            throw new $classNameException(\sprintf("Key '%s' cannot exceed 64 characters",
                $key));
            
        if(false !== $reservedCharacters = \strpbrk($key, "{}()/\\@:"))
            throw new $classNameException(\sprintf("Key '%s' contains reserved characters '%s'",
                $key,
                $reservedCharacters));
                
        if(0 === \preg_match("#[A-Za-z0-9_.]+#", $key))
            throw new $classNameException(\sprintf("Key '%s' contains invalids characters",
                $key));
    }
    
    /**
     * Validate an array of keys
     * 
     * @param array $keys
     *   Keys to validate
     * @param string $classNameException
     *   Name of the class for the exception
     *
     * @throws \Zoe\Component\Cache\Exception\CachePool\InvalidArgumentException
     *   When the given key is invalid (PSR-6)
     * @throws \Zoe\Component\Cache\Exception\SimpleCache\InvalidArgumentException
     *   When the given key is invalid (PSR-16)
     */
    protected function validateKeys(array $keys, string $classNameException): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key, $classNameException);
        }
    }
    
    
    /**
     * Get the value from the cache item.
     * Unserialize it only if needed
     *
     * @param mixed|null $value
     *   Value to get. Will take a local property if setted to null
     *
     * @return mixed
     *   Value setted before serialization if serialized
     */
    protected function doGet($value = null)
    {
        if(null === $value)
            $value = $this->value;
        
        if(!\is_string($value))
            return $value;
            
        if(null === $value || $value === "N;")
            return $value = null;
                
        if($value === "b:0;")
            return $value = false;
                    
        if($value[1] !== ":")
            return $value;
                        
        // the given array is the current serializable values for a first char of a serialized string with the serialize function
        if(!\in_array($value[0], ["s", "b", "i", "d", "a", "O"]) || false === ($unserialized = @\unserialize($value)))
            return $value;
        else {
            $value = $unserialized;
            unset($unserialized);
            return $value;
        }
    }
    
}
