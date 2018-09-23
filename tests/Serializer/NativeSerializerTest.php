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

use NessTest\Component\Cache\CacheTestCase;
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
class NativeSerializerTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\Serializer\NativeSerializer::serialize()
     */
    public function testSerialize(): void
    {
        $serializer = new NativeSerializer();
        
        $this->assertSame(\serialize(45), $serializer->serialize(45));
        $this->assertSame(\serialize(new \stdClass()), $serializer->serialize(new \stdClass()));
        $this->assertSame(\serialize(["foo" => "bar", "bar" => new \stdClass(), "moz" => 42]), $serializer->serialize(["foo" => "bar", "bar" => new \stdClass(), "moz" => 42]));
        $this->assertSame(\serialize(true), $serializer->serialize(true));
        $this->assertSame(\serialize(null), $serializer->serialize(null));
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\NativeSerializer::unserialize()
     */
    public function testUnserialize(): void
    {
        $serializer = new NativeSerializer();
        
        $this->assertSame("", $serializer->unserialize(""));
        $this->assertSame("f", $serializer->unserialize("f"));
        $this->assertNull($serializer->unserialize("N;"));
        $this->assertSame("foo", $serializer->unserialize("foo"));
        $this->assertFalse($serializer->unserialize("b:0;"));
        $this->assertSame("f:oo", $serializer->unserialize("f:oo"));
        $this->assertSame(42, $serializer->unserialize($serializer->serialize(42)));
        $this->assertTrue($serializer->unserialize($serializer->serialize(true)));
        $this->assertSame(["foo" => "bar", "bar" => ["foo" => "bar"], "moz" => 42], $serializer->unserialize($serializer->serialize(["foo" => "bar", "bar" => ["foo" => "bar"], "moz" => 42])));
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
