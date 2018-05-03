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

namespace Zoe\Component\Cache\Utils;


/**
 * Validate datas interacting with the cache process
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait ValidationTrait
{
    
    /**
     * Validate a/set key depending of the PSR context.
     * Prefix it if a prefix is given
     * 
     * @param \Closure|null $verification
     *   Verification to apply on the key to valid a type. Takes as parameter key passed. Can be skipped if setted to null
     * @param mixed $key
     *   Key to validate
     * @param array $types
     *   Types accepted
     * @param string|null $prefix
     *   If given, given key will be prefixed
     *   
     * @return callable
     *   Callable taking as parameter the key to validate
     *   
     * @throws \Zoe\Component\Cache\Exception\PSR16\InvalidArgumentException|\Zoe\Component\Cache\Exception\PSR6\InvalidArgumentException
     *   A PSR6 or PSR16 InvalidArgumentException if the given key is invalid
     * @throws \TypeError
     *   When given type does not correspond to required one
     */
    protected function validateKey(?\Closure $verification, &$key, array $types, ?string $prefix = null): callable
    {        
        if(null !== $verification && !$verification->call($this, $key))
            throw new \TypeError(\sprintf("Key given MUST be a/an '%s'. '%s' given",
                \implode(" or a/an ", $types),
                \is_object($key) ? \get_class($key) : \gettype($key)));
        
        $exception = $this->getException();
        return function(string& $key) use ($exception, $prefix): void  {
            if(0 === \preg_match("#^[{".self::ALLOWED_CHARS."}]+$#", $key))
                throw new $exception(\sprintf("This key '%s' is invalid. Supported characters : '%s'",
                    $key,
                    self::ALLOWED_CHARS));
            if(false !== \strpbrk($key, self::RESERVED_CHARS))
                throw new $exception(\sprintf("This key '%s' contains reserved characters : '%s'",
                    $key,
                    self::RESERVED_CHARS));
            if(\strlen($key) > self::MAX_CHARS_ALLOWED)
                throw new $exception(\sprintf("Max characters allowed for cache key '%s' allowed reached. Max setted to '%s'",
                    $key,
                    self::MAX_CHARS_ALLOWED));
                
            $key = $prefix.$key;
        };
    }
    
    /**
     * Validate and convert an expiration time covering all formats accepted by PSR6 and PSR16 (null, int, Datetime, DateInterval).
     * Convert into a time to live value
     * 
     * @param \Closure $validation
     *   Validation to perform over expiration
     * @param mixed& $expiration
     *   Expiration time to validate and convert
     * @param string $exceptionMessage
     *   Exception message to display when validation failed
     *   
     * @throws \Zoe\Component\Cache\Exception\PSR16\InvalidArgumentException|\Zoe\Component\Cache\Exception\PSR6\InvalidArgumentException
     *   A PSR6 or PSR16 InvalidArgumentException when invalid expiration is given
     */
    protected function validateAndConvertExpiration(\Closure $validation, &$expiration, string $exceptionMessage): void
    {
        if(!$validation->call($this, $expiration)) {
            $exception = $this->getException();
            throw new $exception($exceptionMessage);
        }

        if(\is_null($expiration) || \is_int($expiration))
            return;
        
        if($expiration instanceof \DateInterval)
            $expiration = (new \DateTime())->add($expiration);
        
        $expiration = $expiration->format("U") - \time();
    }
    
    /**
     * Check a value. Serialized it if needed.
     * 
     * @param mixed $value
     *   Serializable value
     * @param callable $normalization
     *   Normalization process. Must return a normalized value or throw an exception if normalization cannot be done.
     *   Takes as parameters the value to normalized and the exception class to throw on error
     * 
     * @return string
     *   Serialized value if needed
     */
    protected function validateValue($value, callable $normalization): string
    {
        if(\is_string($value))
            return $value;
        
        return \call_user_func($normalization, $value, $this->getException());
    }
        
    /**
     * Define exception to throw considering PSR environment
     * 
     * @return string
     *   Exception classname
     */
    private function getException(): string
    {
        if(self::PSR === "PSR6")
            return \Zoe\Component\Cache\Exception\PSR6\InvalidArgumentException::class;
        
        return \Zoe\Component\Cache\Exception\PSR16\InvalidArgumentException::class;
    }
    
}
