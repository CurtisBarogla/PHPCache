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
     * Namespace which the tag map interact
     * 
     * @var string
     */
    private $namespace;
    
    /**
     * Identify the set of tags saved into the cache store
     * 
     * @var string
     */
    public const TAGS_MAP_IDENTIFIER = "@psr6_tags_map";
    
    /**
     * Delete a tag and all items associated to it from the cache
     *      
     * @param CacheAdapterInterface $poolAdapter
     *   Adapter used by the pool to store items
     * @param string $tag
     *   Tag to clear
     */
    public function delete(CacheAdapterInterface $poolAdapter, string $tag): void
    {
        $this->actions["next"][] = function() use ($poolAdapter, $tag): void {
            if(!isset($this->tags[$tag]))
                return;
            $tagged = $this->tags[$tag];
            $poolAdapter->deleteMultiple($tagged);
            unset($this->tags[$tag]);
            foreach ($this->tags as $current => $items) {
                $this->tags[$current] = \array_values(\array_diff($this->tags[$current], $tagged));
                if(empty($this->tags[$current]))
                    unset($this->tags[$current]);
            }
            $this->needsUpdate = true;
        };
    }
    
    /**
     * Save tags attached to the given items into the cache store
     * 
     * @param string $key
     *   Key to link to the given tags
     * @param array $tags
     *   Tags to save
     * @param bool $delayed
     *   Set to true if the save process must be done on the next call of update with commit setted to true
     */
    public function save(string $key, array $tags, bool $delayed): void
    {
        $this->actions[($delayed) ? "delayed" : "next"][] = function() use ($key, $tags): void {
            foreach ($tags as $tag) {
                if(!isset($this->tags[$tag]) || !\in_array($key, $this->tags[$tag])) {
                    $this->tags[$tag][] = $key;
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

        $this->tags = (null !== $map = $this->adapter->get(self::TAGS_MAP_IDENTIFIER."_{$this->namepsace}")) ? \json_decode($map, true) : [];
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
            $result = $this->adapter->set(self::TAGS_MAP_IDENTIFIER."_{$this->namepsace}", \json_encode($this->tags), null);

            return $result;
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
    
    /**
     * Isolate tags to a specific namespace
     * 
     * @param string $namespace
     *   Namespace which isolate tagged items
     */
    public function setNamespace(string $namespace): void
    {
        $this->namepsace = $namespace;
    }
    
}
