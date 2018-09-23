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

use Ness\Component\Cache\FilesystemCache;
use Psr\Log\LoggerInterface;
use org\bovigo\vfs\vfsStream;

/**
 * FilesystemCache testcase
 *
 * @see \Ness\Component\Cache\FilesystemCache
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemCacheTest extends AbstractCacheTest
{
    
    /**
     * Cache directory
     * 
     * @var string
     */
    private static $cacheDirectory;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        $stream = vfsStream::setup();
        self::$cacheDirectory = $stream->url()."/var/cache";
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cache = [
            new FilesystemCache(self::$cacheDirectory)
        ];
        if(\interface_exists(LoggerInterface::class))
            $this->cache[] = new FilesystemCache(self::$cacheDirectory, null, null, $this->getMockBuilder(LoggerInterface::class)->getMock());
    }
    
}