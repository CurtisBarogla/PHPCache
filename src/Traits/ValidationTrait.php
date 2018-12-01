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

namespace Ness\Component\Cache\Traits;

use Ness\Component\Cache\Exception\InvalidArgumentException;

/**
 * Validate informations on cache components
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait ValidationTrait
{

    /**
     * Validate a cache key based on const defined and return it prefixed
     * 
     * @param string $key
     *   Key to validate
     *   
     * @return string
     *   Key validated and prefixed
     *   
     * @throws InvalidArgumentException
     *   When the key is invalid
     */
    protected function validateKey(string $key): string
    {
        if(false !== $reserved = \strpbrk($key, self::RESERVED_CHARACTERS))
            throw new InvalidArgumentException("This cache key '{$key}' is invalid. It contains reserved characters '{$reserved}' from list " . self::RESERVED_CHARACTERS);
            
        if(\strlen($key) > self::MAX_LENGTH)
            throw new InvalidArgumentException("This cache key '{$key}' is invalid. Max characters allowed " . self::MAX_LENGTH);
        
        if(0 === \preg_match("#^[".self::ACCEPTED_CHARACTERS."]+$#", $key))
            throw new InvalidArgumentException("This cache key '{$key}' is invalid. It contains invalid characters. Characters allowed : " . self::ACCEPTED_CHARACTERS);

        return $this->prefix($key);            
    }
    
    /**
     * Apply a prefix on a key
     * 
     * @param string $key
     *   Key to prefix
     * 
     * @return string
     *   Key prefixed
     */
    protected function prefix(string $key): string
    {
        return (null === $this->namespace) ? self::CACHE_FLAG . $key : self::CACHE_FLAG . "{$this->namespace}_{$key}";
    }
    
}
