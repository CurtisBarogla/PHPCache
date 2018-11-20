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

namespace Ness\Component\Cache\PSR16;

use Psr\SimpleCache\CacheInterface;
use Ness\Component\Cache\Exception\InvalidArgumentException;

/**
 * Simple extension of PSR-16 CacheInterface allowing you to attribute a tag to your cached values and provide mass invalidation by tags 
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface TaggableCacheInterface extends CacheInterface
{
    
    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @param array|null             $tags  A set of tags to apply to the value
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     * @throws InvalidArgumentException
     *   When a tag is considered invalid by the cache component
     */
    public function set($key, $value, $ttl = null, ?array $tags = null);
    
    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     * @param array|null             $tags   A set of tags to apply to all values
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     * @throws InvalidArgumentException
     *   When a tag is considered invalid by the cache component
     */
    public function setMultiple($values, $ttl = null, ?array $tags = null);
    
    /**
     * Invalidate a set of cached values previously tagged
     * 
     * @param string $tag
     *   Tag to invalidate
     * 
     * @return bool
     *   True if values have been removed successufully. False otherwise
     *   
     * @throws InvalidArgumentException
     *   When tag is invalid
     */
    public function invalidateTag($tag);
    
    /**
     * Invalidate a set of cached values previously tagged
     *
     * @param array $tag
     *   Tags to invalidate
     *
     * @return bool
     *   True if values have been removed successufully. False otherwise
     *   
     * @throws InvalidArgumentException
     *   When tag is invalid
     */
    public function invalidateTags(array $tags);
    
}
