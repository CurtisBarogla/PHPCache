<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Zoe\Component\Cache\Adapter;

/**
 * Common to all CacheAdapter implementations
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractCacheAdapter implements CacheAdapterInterface
{
    
    /**
     * Log all failed actions by an adapter method into given errors container
     *
     * @param array $results
     *   Results to log
     *   
     * @param array|null
     *   All logged actions. Null if no error
     */
    protected function log(array $results): ?array
    {
        $errors = null;
        foreach ($results as $key => $success)
            if(!$success)
                $errors[] = $key;
            
        return $errors;
    }
    
}
