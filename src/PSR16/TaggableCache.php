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
use Ness\Component\Cache\Traits\TagHandlingTrait;

/**
 * Extension of PSR-16 supporting tags attribution and invalidation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCache extends Cache implements TaggableCacheInterface
{

    use TagHandlingTrait;
    
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
     * Max characters length accepted for tag
     *
     * @var int
     */
    public const MAX_LENGTH_TAG = 32;
    
    /**
     * Accepted characters for tag
     *
     * @var string
     */
    public const ACCEPTED_CHARACTERS_TAG = "A-Za-z0-9";
    
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
        
        \array_map([$this, "validateTag"], $tags);
            
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
        
        \array_map([$this, "validateTag"], $tags);
            
        foreach ($values as $key => $value)
            $this->tagMap->save($this->prefix($key), $tags, false);
        
        return $result && $this->tagMap->update(false);
    }

}
