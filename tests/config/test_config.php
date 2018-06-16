<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 */

// SIMPLY AN ARRAY REPRESENTING CONFIGURATIONS OVER ALL EXTRA STORAGES USED DURING TESTING PROCESS

namespace NessTest\Component\Cache\config {
    global $configs;
    $configs = [
        "REDIS_CONFIGS"  =>  [
            "redis_without_prefix"  =>  [
                "host"                  =>  "127.0.0.1",
                "port"                  =>  6379
            ],
            "redis_with_prefix"     =>  [
                "host"                  =>  "127.0.0.1",
                "port"                  =>  6379,
                "options"               =>  [
                    \Redis::OPT_PREFIX       =>  "prefix_"
                ]
            ]
        ]
    ];
    
    /**
     * Get a connection configuration
     * 
     * @param string $config
     *   Configuration identifier
     * 
     * @return array
     *   Connection configurations
     */
    function getTestConfiguration(string $config): array
    {
        global $configs;
        
        return $configs[$config];
    }
    
}