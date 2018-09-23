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

use Ness\Component\Cache\InMemoryCache;
use Psr\Log\LoggerInterface;

/**
 * InMemoryCache testcase
 * 
 * @see \Ness\Component\Cache\InMemoryCache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InMemoryCacheTest extends AbstractCacheTest
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cache = [
            new InMemoryCache()   
        ];
        if(\interface_exists(LoggerInterface::class))
            $this->cache[] = new InMemoryCache(null, null, $this->getMockBuilder(LoggerInterface::class)->getMock());
    }
    
}
