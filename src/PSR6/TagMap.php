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

use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Interface between a TaggableCacheItemPool and a CacheAdapter responsible of update, delete of a set of tags
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TagMap
{
    
    /**
     * Memoized tags
     * 
     * @var array
     */
    private $tags;
    
    /**
     * Interacts with the saved tags
     * 
     * @var CacheAdapterInterface
     */
    private $adapter;
    
    /**
     * Actions done on the tags map
     * 
     * @var \Closure[]
     */
    private $actions;
    
    /**
     * If change has been made on the map
     * 
     * @var bool
     */
    private $needsUpdate = false;
    
    /**
     * Identify the set of tags saved into the cache store
     * 
     * @var string
     */
    public const TAGS_MAP_IDENTIFIER = "@psr6_tags_map";
    
    /**
     * Initialize tags map
     */
    public function initializeMap(): void
    {
        $this->tags = (null !== $map = $this->adapter->get(self::TAGS_MAP_IDENTIFIER)) ? \unserialize($map) : [];
    }
    
    /**
     * Delete a tag and all items associted to it from the cache
     * 
     * @param CacheItemPoolInterface $pool
     *   Cache pool
     * @param string $tag
     *   Tag to clear
     */
    public function delete(CacheItemPoolInterface $pool, string $tag): void
    {
        if(!isset($this->tags[$tag]))
            return;
        
        $this->actions["next"][] = function() use ($pool, $tag): void {
            $pool->deleteItems($this->tags[$tag]);
            unset($this->tags[$tag]);
            $this->needsUpdate = true;
        };
    }
    
    /**
     * Save tags attached to the given items into the cache store
     * 
     * @param TaggableCacheItem $item
     *   Taggable item
     * @param bool $delayed
     *   Set to true if the save process must be done on the next call of update with commit setted to true
     */
    public function save(TaggableCacheItem $item, bool $delayed): void
    {
        $this->actions[($delayed) ? "delayed" : "next"][] = function() use ($item): void {
            foreach ($item->getCurrent() as $tag) {
                if(!isset($this->tags[$tag]) || !\in_array($item->getKey(), $this->tags[$tag])) {
                    $this->tags[$tag][] = $item->getKey();
                    $this->needsUpdate = true;
                }
            }
        };
    }
    
    /**
     * Update the stored tags map
     * 
     * @param bool $commit
     *   Set to true to commit all actions
     * 
     * @return bool
     *   True if the maps has been updated correctly or no action has been made. False otherwise
     */
    public function update(bool $commit): bool
    {
        if(null === $this->actions)
            return true;

        foreach ($this->actions as $type => $actions) {
            foreach ($actions as $index => $action) {
                if(!$commit && $type === "delayed")
                    continue;
                $action->call($this);
                unset($this->actions[$type][$index]);
            }
        }
        
        if($this->needsUpdate) {
            $this->needsUpdate = false;
            return $this->adapter->set(self::TAGS_MAP_IDENTIFIER, \serialize($this->tags), null);
        }
        
        return true;
    }
    
    /**
     * Set adapter
     * 
     * @param CacheAdapterInterface $adapter
     *   Cache adapter
     */
    public function setAdapter(CacheAdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }
    
}
