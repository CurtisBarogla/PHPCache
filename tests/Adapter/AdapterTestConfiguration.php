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

namespace ZoeTest\Component\Cache\Adapter;

use ZoeTest\Component\Cache\GlobalConfiguration;

/**
 * Defining simply configurations over required resources (like a redis connection, memcached... parameters)
 * Only constants are declared here
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface AdapterTestConfiguration extends GlobalConfiguration
{
    
    /******** REDIS ********/
    
    /**
     * Redis host
     * 
     * @var string
     */
    public const REDIS_HOST = "127.0.0.1";
    
    /**
     * Redis port
     *
     * @var int
     */
    public const REDIS_PORT = 6379;
    
    /**
     * Redis options
     * 
     * @var array[]|null
     */
    public const REDIS_OPTIONS = [
        "REDIS_BASE"        =>  null,
        "REDIS_PREFIXED"    =>  [
            \Redis::OPT_PREFIX      =>  "PREFIX_"
        ]
    ];
    
}
