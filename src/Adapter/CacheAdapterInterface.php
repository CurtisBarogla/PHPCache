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

namespace Zoe\Component\Cache\Adapter;


/**
 * Interact with a Cache component.
 * Relation between Cache component and an external storage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface CacheAdapterInterface
{
    
    /**
     * Get a single value from a store by its key
     * 
     * @param string $key
     *   Cache key
     * @return string|null
     *   Cache value or null if no value correponds to this key
     */
    public function get(string $key): ?string;
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Cache key
     * @param string $value
     *   Cache value
     * @param int|null $ttl
     *   Ttl for the value. Null for "illimited" time
     * 
     * @return bool
     *   True if the value has been stored  with success. False otherwise
     */
    public function set(string $key, string $value, ?int $ttl): bool;
    
    /**
     * Delete a single value from the store by its key
     *
     * @param string $key
     *   Key value to delete
     *
     * @return bool
     *   True if the value has been correctly deleted. False otherwise
     */
    public function delete(string $key): bool;
    
    /**
     * Check if a key is in the store
     * 
     * @param string $key
     *   Cache key
     * 
     * @return bool
     *   True if the value is stored. False otherwise
     */
    public function exists(string $key): bool;
    
    /**
     * Get multiple values from the store
     * 
     * @param iterable $keys
     *   All keys to get. If a key result to a miss, null will be assigned as its value
     *   
     * @return \Generator
     *   All keys founded
     */
    public function getMultiple(iterable $keys): \Generator;
    
    /**
     * Set multiple values at once into the store.
     * Each value informations are accessible via a normalized format. <br />
     * <pre>
     *      $foo->key; (getting the key) (string)
     *      $foo->value; (getting the value to cache) (string)
     *      $foo->ttl; (getting the ttl) (string|null)
     * </pre>
     * Properties value MUST NOT be modified by an adapter
     * 
     * @param mixed $values
     *   Values to store. Each values MUST be accessed via a normalized format.
     *   
     * @return array|null
     *   An array of all keys that failed to be stored or null if everything has been stored with success
     */
    public function setMultiple($values): ?array;
    
    /**
     * Delete multiple values from the store by their keys
     * 
     * @param iterable $keys
     *   Keys to delete
     *   
     * @return array|null
     *   An array of all keys that failed to be stored or null if everything has been stored with success
     */
    public function deleteMultiple(iterable $keys): ?array;
    
    /**
     * Clear the store
     * 
     * @param string|null $pattern
     *   Regex pattern
     * 
     * @return bool
     *   True if the store has been correctly cleared. False otherwise
     */
    public function clear(?string $pattern): bool;
    
}
