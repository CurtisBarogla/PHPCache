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

use Ness\Component\Cache\Tag\TagMap;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\Exception\CacheException;
use Ness\Component\Cache\Exception\InvalidArgumentException;

/**
 * Extension of PSR-16 supporting tags attribution and invalidation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCache extends Cache implements TaggableCacheInterface
{

    /**
     * Tags map
     * 
     * @var TagMap
     */
    private $tagMap;
    
    /**
     * Chance to apply a gc on the tag map
     *
     * @var int
     */
    public static $gcTapMap = 20;
    
    /**
     * Initialize taggable cache
     *
     * @param CacheAdapterInterface $adapter
     *   Cache adapater
     * @param CacheAdapterInterface|null $tagMapAdapter
     *   Adapter interacting with the tag map
     * @param int|\DateInterval|null
     *   Default ttl applied to all ttl non-explicitly declared
     * @param string $namespace
     *   Cache namespace
     *
     * @throws InvalidArgumentException
     *   When default ttl is invalid
     * @throws CacheException
     *   When serializer not registered
     */
    public function __construct(
        CacheAdapterInterface $adapter, 
        CacheAdapterInterface $tagMapAdapter = null,
        $defaultTtl = null, 
        string $namespace = "global")
    {
        parent::__construct($adapter, $defaultTtl, $namespace);
        $this->tagMap = new TagMap();
        $this->tagMap->setAdapter($tagMapAdapter ?? $adapter);
        $this->tagMap->setNamespace(self::CACHE_FLAG.$this->namespace);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::set()
     */
    public function set($key, $value, $ttl = -1, ?array $tags = null): bool
    {
        if(null === $tags)
            return parent::set($key, $value, $ttl);
        
        $this->tagMap->save($this->prefix($key), $tags, false);
        
        return parent::set($key, $value, $ttl) && $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR16\Cache::clear()
     */
    public function clear()
    {
        return parent::clear() && $this->tagMap->clear();  
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::setMultiple()
     */
    public function setMultiple($values, $ttl = -1, ?array $tags = null): bool
    {
        $result = parent::setMultiple($values, $ttl);
        if(null === $tags)
            return $result;
        
        foreach ($values as $key => $value)
            $this->tagMap->save($this->prefix($key), $tags, false);
        
        return $result && $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::invalidateTag()
     */
    public function invalidateTag(string $tag): bool
    {
        $this->tagMap->delete($this->adapter, $tag, self::$gcTapMap);
        
        return $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::invalidateTags()
     */
    public function invalidateTags(array $tags): bool
    {
        foreach ($tags as $tag)
            $this->tagMap->delete($this->adapter, $tag, self::$gcTapMap);
        
        return $this->tagMap->update(false);
    }

}
