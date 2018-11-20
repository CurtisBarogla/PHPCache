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

namespace Ness\Component\Cache\Tag;

use Ness\Component\Cache\Adapter\CacheAdapterInterface;

/**
 * Factory singleton container for tag map
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TagMapContainer
{
    
    /**
     * Tag map already registered indexed by namespace
     * 
     * @var TagMap[]
     */
    private static $maps;
    
    /**
     * Make private
     */
    private function __construct() {}
    
    /**
     * Responsible to initialize new tag map if needed and fetch it if already registered from a specific namespace
     * 
     * @param string $namespace
     *   Namespace of the tag map
     * @param CacheAdapterInterface $adapter
     *   Cache adapter interacting with the tag map
     * 
     * @return TagMap
     *   Tag map already initialized or fresh one
     */
    public static function registerMap(string $namespace, CacheAdapterInterface $adapter): TagMap
    {
        if(isset(self::$maps[$namespace]))
            return self::$maps[$namespace];
        
        $map = new TagMap();
        $map->setNamespace($namespace);
        $map->setAdapter($adapter);
        
        self::$maps[$namespace] = $map;
        
        return $map;
    }

}
