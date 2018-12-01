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

use Ness\Component\Cache\Exception\SerializerException;
use Ness\Component\Cache\Serializer\IgbinarySerializer;

/**
 * IgbinarySerializer testcase
 * 
 * @see \Ness\Component\Cache\Serializer\IgbinarySerializer
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class IgbinarySerializerTest extends AbstractSerializerTest
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\extension_loaded("igbinary"))
            self::markTestSkipped("Igbinary extension not active or not installed. Test skipped");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->serializer = new IgbinarySerializer();
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\Serializer\IgbinarySerializer::serialize()
     */
    public function testExceptionWhenTryingToSerialiseResource(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new IgbinarySerializer();
        
        $value = \fopen("php://temp", "r");
        $serializer->serialize($value);
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\IgbinarySerializer::serialize()
     */
    public function testExceptionWhenTryingToSerializeAnonymousFunc(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new IgbinarySerializer();
        
        $serializer->serialize(function(){});
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\IgbinarySerializer::serialize()
     */
    public function testExceptionWhenTryingToSerializeAnonymousClass(): void
    {
        $this->expectException(SerializerException::class);
        
        $serializer = new IgbinarySerializer();
        
        $serializer->serialize(new class() {});
    }
    
}
