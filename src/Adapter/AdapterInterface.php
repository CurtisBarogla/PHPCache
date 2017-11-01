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
 * Adapter that can be connected to a CachePoolInterface to interact with a store mechanism
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AdapterInterface
{
    
    /**
     * Get a value from the store
     * 
     * @param string $key
     *   Key values to get
     * 
     * @return string|null
     *   Key value stored or null if the value is not setted
     */
    public function get(string $key): ?string;
    
    /**
     * Get multiple value from the store
     * 
     * @param array $keys
     *   Keys values to get from the store
     *   
     * @return \Generator
     *   Generator indexed by the key and filled with values. <br />
     *   If the value has been not found for the given key, the value for this key will be null
     */
    public function getMultiple(array $keys): \Generator;
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Key to set
     * @param string $value
     *   Value to set
     * @param int $ttl
     *   Time to live
     * 
     * @return bool
     *   True if the key has been stored correctly. False otherwise
     */
    public function set(string $key, string $value, ?int $ttl): bool;
    
    /**
     * Set multiple values into the store.
     * All values MUST be indexed by the key and the array formatted<br />
     * [ <br />
     * &emsp;&emsp;"key"    =>  <br />
     *      &emsp;&emsp;&emsp;&emsp;[<br />
     *          &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; "value" =>  {value to store},<br />
     *          &emsp;&emsp;&emsp;&emsp;&emsp;&emsp; "ttl"   =>  {ttl of the value or null}<br />
     *      &emsp;&emsp;&emsp;&emsp;] <br />
     * ]<br />
     * 
     * @param array $values
     *   Array of values formatted
     * 
     * @return array
     *   Array indexed by key and the result of the request. True means the value has been successfully saved, false means an error
     */
    public function setMultiple(array $values): array;
    
    /**
     * Delete a value from the store
     * 
     * @param string $key
     *   Key value to delete
     * 
     * @return bool
     *   True if the value has been correctly deleted. False otherwise
     */
    public function del(string $key): bool;
    
    /**
     * Delete a set of values from the store
     * 
     * @param array $keys
     *   Array of keys to delete
     * 
     * @return array
     *   Array indexed by key and the result of the request. True means the value has been successfully saved, false means an error
     */
    public function delMultiple(array $keys): array;
    
    /**
     * Check if a value is in the store
     * 
     * @param string $key
     *   Value key to check
     * 
     * @return bool
     *   True if the value for the given key is in the store. False otherwise
     */
    public function exists(string $key): bool;
    
    /**
     * Clear the store.
     * Will clear only the key that respect a given pattern if this one is given
     * 
     * @param string|null $pattern
     *   Key pattern value to clear
     * 
     * @return bool
     *   True if all values has been deleted from the store
     */
    public function clear(?string $pattern = null): bool;
    
}
