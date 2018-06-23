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

namespace NessTest\Component\Cache;

use function NessTest\Component\Cache\config\getTestConfiguration;
use Ness\Component\Cache\RedisCache;
use Psr\Log\LoggerInterface;

/**
 * RedisCache testcase
 *
 * @see \Ness\Component\Cache\RedisCache
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCacheTest extends AbstractCacheTest
{
    
    /**
     * Redis instance
     * 
     * @var \Redis
     */
    private static $redis;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\class_exists(\Redis::class))
            self::markTestSkipped("Redis class not found");
        
        $config = getTestConfiguration("REDIS_CONFIGS")["redis_without_prefix"];
        self::$redis = new \Redis();
        self::$redis->connect($config["host"], $config["port"]);
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->cache = [
            new RedisCache(self::$redis)
        ];
        if(\interface_exists(LoggerInterface::class))
            $this->cache[] = new RedisCache(self::$redis, null, null, $this->getMockBuilder(LoggerInterface::class)->getMock());
    }
    
}
