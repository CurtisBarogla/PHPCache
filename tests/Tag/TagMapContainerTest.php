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

namespace NessTest\Component\Cache\Tag;

use NessTest\Component\Cache\CacheTestCase;
use Ness\Component\Cache\Tag\TagMapContainer;

/**
 * TagMapContainer testcase
 * 
 * @see \Ness\Component\Cache\Tag\TagMapContainer
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TagMapContainerTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMapContainer::registerMap()
     */
    public function testRegisterMap(): void
    {
        $adapter = $this->getMockedAdapter(null);
        
        $foo = TagMapContainer::registerMap("foo", $adapter);
        $this->assertSame($foo, TagMapContainer::registerMap("foo", $adapter));
    }
    
}
