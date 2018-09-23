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

use Ness\Component\Cache\ApcuCache;
use Psr\Log\LoggerInterface;

/**
 * ApcuCache testcase
 *
 * @see \Ness\Component\Cache\ApcuCache
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ApcuCacheTest extends AbstractCacheTest
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cache = [
            new ApcuCache()
        ];
        if(\interface_exists(LoggerInterface::class))
            $this->cache[] = new ApcuCache(null, null, $this->getMockBuilder(LoggerInterface::class)->getMock());
    }
    
}
