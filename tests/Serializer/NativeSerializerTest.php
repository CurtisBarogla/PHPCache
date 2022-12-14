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

namespace NessTest\Component\Cache\Serializer;

use Ness\Component\Cache\Serializer\NativeSerializer;
use Ness\Component\Cache\Exception\SerializerException;

/**
 * NativeSerializer testcase
 * 
 * @see \Ness\Component\Cache\Serializer\NativeSerializer
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeSerializerTest extends AbstractSerializerTest
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->serializer = new NativeSerializer();
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\Serializer\NativeSerializer::serialize()
     */
    public function testExceptionWhenTryingToSerialiseResource(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new NativeSerializer();
        
        $value = \fopen("php://temp", "r");
        $serializer->serialize($value);
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\NativeSerializer::serialize()
     */
    public function testExceptionWhenTryingToSerializeAnonymousFunc(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new NativeSerializer();
        
        $serializer->serialize(function(){});
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\NativeSerializer::serialize()
     */
    public function testExceptionWhenTryingToSerializeAnonymousClass(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new NativeSerializer();
        
        $serializer->serialize(new class() {});
    }
    
}
