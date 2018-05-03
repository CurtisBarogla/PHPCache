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

namespace ZoeTest\Component\Cache;

/**
 * Defining configuration over all components tested.
 * Only constants are declared here
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface GlobalConfiguration
{
    
    /**
     * If tests considered long must be executed (typically sleep function calls)
     * 
     * @var bool
     */
    public const EXECUTE_LONG_TESTS = false;
    
    /**
     * If tests considered stupid must be executed
     *
     * @var bool
     */
    public const EXECUTE_STUPID_TESTS = false;
    
}
