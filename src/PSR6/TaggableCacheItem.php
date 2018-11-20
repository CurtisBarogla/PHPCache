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
     * Responsible to check a tag applied to an item
     * 
     * MUST be registered only by the pool
     * 
     * @var \Closure
     */
    public static $tagValidation = null;
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemInterface::setTags()
     */
    public function setTags(array $tags)
    {
        \call_user_func(self::$tagValidation, $tags);
        
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
     * Initialize a TaggableCacheItem from its json representation
     *
     * @param string $key
     *   TaggableCacheItem key
     * @param string $json
     *   Json representation
     *
     * @return TaggableCacheItem
     *   TaggableCacheItem initialized
     */
    public static function createFromJson(string $key, string $json): CacheItemInterface
    {
        $json = \json_decode($json, true);

        $item = new self($key);
        $item->hit = true;
        $item->value = self::$serializer->unserialize($json["value"]);
        $item->ttl = $json["ttl"];
        $item->saved = $json["saved"] ?? [];
        
        return $item;
    }
    
    /**
     * Register validation process into TaggableCacheItem.
     * Do nothing if a process is already registered
     * 
     * @param \Closure $validation
     *   Validation process
     *   
     * @internal
     *   MUST never be used outside of the TaggableCacheItemPool
     */
    public static function registerTagValidation(\Closure $validation): void
    {
        if(null === self::$tagValidation)
            self::$tagValidation = $validation;
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
    protected function toJson(): array
    {
        $this->saved = $this->getCurrent();
        $this->current = null;
        
        return \array_merge(
            parent::toJson(), 
            [
                "saved"     => $this->saved
            ]);
    }
    
}
