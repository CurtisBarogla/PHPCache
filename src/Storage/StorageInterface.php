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

/**
 * Storage can be attached to an AdapterInterface
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface StorageInterface
{

    /**
     * Get a value from the storage by its key
     * 
     * @param string $key
     *   Value key
     * 
     * @return string|null
     *   Value stored or null if the value for the given key is not setted
     */
    public function get(string $key): ?string;
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Value key
     * @param string $value
     *   Value to store
     * 
     * @return bool
     *   True if the storage process has been done correctly. False otherwise
     */
    public function set(string $key, string $value): bool;
    
    /**
     * Set a value into the store with a time to live value
     * 
     * @param string $key
     *   Value key
     * @param int $ttl
     *   Time to live in seconds
     * @param string $value
     *   Value to store
     * 
     * @return bool
     *   True if the storage process has been done correctly. False otherwise
     */
    public function setEx(string $key, int $ttl, string $value): bool;
    
    /**
     * Delete a value from the store by its key
     * 
     * @param string $key
     *   Value key to delete
     * 
     * @return bool
     *   True if the value has been correctly deleted from the store. False otherwise
     */
    public function del(string $key): bool;
    
    /**
     * Set a value to expiration
     * 
     * @param string $key
     *   Value key to expire
     * 
     * @return bool
     *   True if the value has been correctly setted to expiration. False otherwise
     */
    public function expire(string $key): bool;
    
    /**
     * Check if a value exists into the store
     * 
     * @param string $key
     *   Key value to check the existence
     * 
     * @return bool
     *   Return true if the value exists into the store for the given key. False otherwise
     */
    public function exists(string $key): bool;
    
    /**
     * Rename an existant value
     * 
     * @param string $key
     *   Value key to rename
     * @param string $newkey
     *   New name for the given key
     * 
     * @return bool
     *   True if the key has been correctly renamed. False if a similar key exists or anything else
     */
    public function rename(string $key, string $newkey): bool;
    
    /**
     * Get the time to live of a value
     * 
     * @param string $key
     *   Key value
     * 
     * @return int|null
     *   Time to live of the value, -1 if the value is not setted, null if permanent
     */
    public function ttl(string $key): ?int;
    
    /**
     * Remove all value stored
     * 
     * @return bool
     *   True if all values has been deleted. False otherwise
     */
    public function flush(): bool;
    
    /**
     * List all keys from the store or only ones respecting a certain pattern (can be a regex) if one is given
     * 
     * @param string|null $pattern
     *   Pattern of the keys to list
     * 
     * @return \Generator
     *   All keys founded
     */
    public function list(?string $pattern = null): \Generator;
    
    /**
     * Get the number of values stored
     * 
     * @return int
     *   Number of value stored
     */
    public function count(): int;
    
}
