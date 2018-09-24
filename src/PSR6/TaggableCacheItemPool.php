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

namespace Ness\Component\Cache\PSR6;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Psr\Cache\CacheItemInterface;
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Ness\Component\Cache\Exception\CacheException;

/**
 * CachePool supporting tags
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCacheItemPool extends CacheItemPool implements TaggableCacheItemPoolInterface
{
    
    /**
     * Interaction between the pool and a tag map
     * 
     * @var TagMap
     */
    private $tagMap;
    
    /**
     * Initialize cache pool
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapter
     * @param CacheAdapterInterface|null $tagMapAdapter
     *   Cache adapter to store a list of tags referencing items
     * @param int|null|\DateTimeInterface|\DateInterval
     *   Default pool ttl applied to non-explicity setted to null CacheItem
     * @param string $namespace
     *   Cache pool namespace (by default setted to global)
     *   
     * @throws InvalidArgumentException
     *   When default ttl is invalid
     * @throws CacheException
     *   When serializer is not registered 
     */
    public function __construct(
        CacheAdapterInterface $adapter, 
        ?CacheAdapterInterface $tagMapAdapter = null, 
        $defaultTtl = null, 
        string $namespace = "global")
    {
        parent::__construct($adapter, $defaultTtl, $namespace);
        $this->tagMap = new TagMap();
        $this->tagMap->setAdapter($tagMapAdapter ?? $adapter);
        $this->tagMap->setNamespace($this->namespace);
    }
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface:getItem()
     */
    public function getItem($key)
    {
        return TaggableCacheItem::convert(parent::getItem($key));
    }
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface:getItems()
     */
    public function getItems(array $keys = [])
    {
        return \array_map(TaggableCacheItem::class."::convert", parent::getItems($keys)); 
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::clear()
     */
    public function clear()
    {
        return parent::clear() && $this->tagMap->clear();
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::save()
     */
    public function save(CacheItemInterface $item)
    {
        if(!$item instanceof TaggableCacheItem)
            return parent::save($item);
        
        $this->tagMap->save($this->prefix($item->getKey()), $item->getCurrent(), false);
        
        return parent::save($item) && $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::saveDeferred()
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if(!$item instanceof TaggableCacheItem)
            return parent::saveDeferred($item);
        
        $this->tagMap->save($this->prefix($item->getKey()), $item->getCurrent(), true);

        return parent::saveDeferred($item);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::commit()
     */
    public function commit()
    {
        return parent::commit() && $this->tagMap->update(true);
    }
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface::invalidateTag()
     */
    public function invalidateTag($tag)
    {
        $this->tagMap->delete($this->adapter, $tag);
        
        return $this->tagMap->update(false);
    }

    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface::invalidateTags()
     */
    public function invalidateTags(array $tags)
    {
        foreach ($tags as $tag)
            $this->tagMap->delete($this->adapter, $tag);
        
        return $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::factoryItem()
     */
    protected function factoryItem(string $key, string $item): CacheItemInterface
    {
        return TaggableCacheItem::createFromJson($key, $item);
    }

}
