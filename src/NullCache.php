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

namespace Ness\Component\Cache;

use Ness\Component\Cache\Adapter\NullCacheAdapter;

/**
 * Null cache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NullCache extends AbstractCache
{
    
    /**
     * Null cache
     */
    public function __construct()
    {
        $this->adapter = new NullCacheAdapter();
        parent::__construct(null, null, null);
    }
    
}
