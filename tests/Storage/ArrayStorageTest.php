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

namespace ZoeTest\Component\Cache\Storage;

use Zoe\Component\Cache\Exception\InvalidRegexException;
use Zoe\Component\Cache\Storage\ArrayStorage;

/**
 * ArrayStorage testcase
 * To disable the test of this store, comment the extends
 * 
 * @see \Zoe\Component\Cache\Storage\ArrayStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class ArrayStorageTest extends StorageTest
{
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\Storage\ArrayStorage::list()
     */
    public function testExceptionListWhenPatternIsInvalid(): void
    {
        $this->expectException(InvalidRegexException::class);
        $this->expectExceptionMessage("This pattern '#' is invalid");
        
        $store = new ArrayStorage();
        $store->set("foo", "bar");
        
        $this->extractGenerator($store->list("#"));
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Storage\StorageTest::getStorages()
     */
    protected function getStorages(): array
    {
        return [
            "array_store_without_prefix"    =>  new ArrayStorage(),   
            "array_store_with_prefix"       =>  new ArrayStorage(self::PREFIX),
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Storage\StorageTest::getPrefixProperty()
     */
    protected function getPrefixProperty(): ?string
    {
        return "prefix";
    }
    
}
