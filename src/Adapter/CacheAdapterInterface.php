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

namespace Ness\Component\Cache\Adapter;

/**
 * Used to communicate with an external storage and a Cache component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface CacheAdapterInterface
{
    
    /**
     * Get a value from the cache store
     * 
     * @param string $key
     *   Cache key
     * 
     * @return string|null
     *   Cache value corresponding to the key or null if no value has been found
     */
    public function get(string $key): ?string;
    
    /**
     * Get multiple values from the cache store
     * 
     * @param array $keys
     *   Cache keys
     * 
     * @return array[string|null]
     *   Sequential array for each cache value corresponding for each key. Null will be assigned to a non-found value
     */
    public function getMultiple(array $keys): array;
    
    /**
     * Set a value into the cache store
     * 
     * @param string $key
     *   Cache key
     * @param string $value
     *   Value to cache
     * @param int|null $ttl
     *   Expiration time in seconds. Null means indefinite storage time
     * 
     * @return bool
     *   True if the value has been stored with success. False otherwise
     */
    public function set(string $key, string $value, ?int $ttl): bool;
    
    /**
     * Set multiple values into the cache store
     * 
     * @param array $values
     *   Associative array indexed by the cache key. Cache value can be accessed through "value" key and ttl through "ttl" key
     *  
     * @return array|null
     *   Return null if all values has been stored with success or an array representing all cache keys that cannot be stored
     */
    public function setMultiple(array $values): ?array;
    
    /**
     * Delete a value from the cache store
     * 
     * @param string $key
     *   Cache key
     * 
     * @return bool
     *   True if the cached value has been correctly deleted. False otherwise
     */
    public function delete(string $key): bool;
    
    /**
     * Delete multiple values from the cache store
     * 
     * @param array $keys
     *   An array of keys to delete
     * 
     * @return array|null
     *   All list of all keys that cannot be deleted or null if all keys has been deleted
     */
    public function deleteMultiple(array $keys): ?array;
    
    /**
     * Check if a value is cached
     * 
     * @param string $key
     *   Cache key
     * 
     * @return bool
     *   True if the cache key corresponds to a value. False otherwise
     */
    public function has(string $key): bool;
    
    /**
     * Purge the store from all cache values
     * 
     * @param string|null $pattern
     *   Regex pattern for targeting only certain keys or null to purge everything
     */
    public function purge(?string $pattern): void;
    
}
