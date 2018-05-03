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

namespace Zoe\Component\Cache\Store;

/**
 * Built-in store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface CacheStoreInterface
{
    
    /**
     * Get a single value from the store.
     * Return null if not value corresponds to the given key
     * 
     * @param string $key
     *   Key value
     * 
     * @return string|null
     *   Value for this key or null
     */
    public function get(string $key): ?string;
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Key value
     * @param string $value
     *   Value for this key
     * 
     * @return bool
     *   True if the value has been correctly stored. False otherwise
     */
    public function set(string $key, string $value): bool;
    
    /**
     * Set value with an expiration time into the store
     * 
     * @param string $key
     *   Key value
     * @param string $value
     *   Value for this key
     * @param int $ttl
     *   Expiration time in seconds
     * 
     * @return bool
     *   True if the value has been stored correctly. False otherwise
     */
    public function setex(string $key, string $value, int $ttl): bool;
    
    /**
     * Remove a key from the store
     * 
     * @param string $key
     *   Key to remove
     * 
     * @return bool
     *   True if the value has been correctly removed. False otherwise
     */
    public function del(string $key): bool;
    
    /**
     * Get ttl of a value
     * 
     * @param string $key
     *   Key value
     * 
     * @return int|null
     *   Null if the key does not correspond to a valid one. -1 if the value key is stored indefinitely. Time to live in seconds
     */
    public function ttl(string $key): ?int;
    
    /**
     * Check if a value exists into the store
     * 
     * @param string $key
     *   Key to check
     * 
     * @return bool
     *   True if the key is in the store
     */
    public function exists(string $key): bool;
    
    /**
     * List keys from the store
     * 
     * @param string|null $pattern
     *   If key must respect a certain pattern
     * 
     * @return \Generator
     *   All keys
     */
    public function list(?string $pattern): \Generator;
    
    /**
     * Flush the store from all its values
     */
    public function flush(): void;
    
    /**
     * Get namespace setted for this store
     * 
     * @return string|null
     *   Namespace of the store or null if no namespace has been defined
     */
    public function getNamespace(): ?string;
    
}
