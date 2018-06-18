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

use Cache\TagInterop\TaggableCacheItemInterface;
use Psr\Cache\CacheItemInterface;

/**
 * CacheItem supporting tags
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCacheItem extends CacheItem implements TaggableCacheItemInterface
{
    
    /**
     * Current tags
     * 
     * @var null|string[]
     */
    private $current = null;
    
    /**
     * Tags attached to the items when fectched from the pool
     * 
     * @var string[]
     */
    private $saved = [];
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemInterface::setTags()
     */
    public function setTags(array $tags)
    {
        $this->current = $tags;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemInterface::getPreviousTags()
     */
    public function getPreviousTags()
    {
        return $this->saved;
    }
    
    /**
     * Get current tags attached to the item
     * 
     * @return array
     *   Current tags
     *   
     * @internal
     *   Do not use outside of the TaggableCacheItemPool
     */
    public function getCurrent(): array
    {
        return $this->current ?? $this->saved;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItem::unserialize()
     */
    public function unserialize($serialized)
    {
        list($this->key, $this->value, $this->hit, $this->ttl, $this->saved, $this->current) = \unserialize($serialized);
    }
    
    /**
     * Convert a basic cache item into a taggable one
     * 
     * @param CacheItemInterface $item
     *   Cache item to convert
     * 
     * @return TaggableCacheItemInterface
     *   Taggable version of the given cache item
     */
    public static function convert(CacheItemInterface $item): TaggableCacheItemInterface
    {
        if($item instanceof TaggableCacheItemInterface)
            return $item;
        
        $self = new self($item->getKey());
        $self->value = $item->get();
        $self->ttl = $item->getTtl();
        $self->hit = $item->isHit();
        
        return $self;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\PSR6\CacheItem::toSerialize()
     */
    protected function toSerialize(): array
    {
        $this->saved = $this->getCurrent();
        $this->current = null;
        
        return \array_merge(parent::toSerialize(), [$this->saved, $this->current]);
    }
    
}
