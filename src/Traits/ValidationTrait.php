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
        try {
            if( ($tooLong = \strlen($key) > self::MAX_LENGTH)                        ||
                (false !== $reserved = \strpbrk($key, self::RESERVED_CHARACTERS))    || 
                0 === \preg_match("#^[".self::ACCEPTED_CHARACTERS."]+$#", $key)) {
                    $message = ($tooLong) 
                        ? "Max characters allowed " . self::MAX_LENGTH 
                        : (($reserved) ? "It contains reserved characters '{$reserved}' from list " . self::RESERVED_CHARACTERS 
                            : "It contains invalid characters. Characters allowed : " . self::ACCEPTED_CHARACTERS);
                    throw new InvalidArgumentException("This cache key '{$key}' is invalid. {$message}");
            }
            
            return $this->prefix($key);            
        } catch (\Error $e) {
            throw new \Error("A required constant has been not defined into the implementation of the cache component. {$e->getMessage()}");
        }
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
