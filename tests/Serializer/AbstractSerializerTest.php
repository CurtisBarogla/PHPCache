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
use Ness\Component\Cache\Serializer\SerializerInterface;

/**
 * Common to all Serializer testcase
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractSerializerTest extends CacheTestCase
{
    
    /**
     * Serializer currently tested
     * MUST be initialized into a setUp
     * 
     * @var SerializerInterface
     */
    protected $serializer;
    
    /**
     * @see \Ness\Component\Cache\Serializer\SerializerInterface::serialize()
     */
    public function testSerialize(): void
    {
        foreach ($this->provideValues() as $value)
            $this->assertNotFalse($this->serializer->serialize($value));
    }
    
    /**
     * @see \Ness\Component\Cache\Serializer\SerializerInterface::unserialize()
     */
    public function testUnserialize(): void
    {
        $serializedValues = [];
        foreach ($this->provideValues() as $value)
            $serializedValues[] = $this->serializer->serialize($value);
        
        $unserializedValues = [];
        foreach ($serializedValues as $serializedValue)
            $unserializedValues[] = $this->serializer->unserialize($serializedValue);
        
        $this->compare($unserializedValues);
        $this->assertSame("", $this->serializer->unserialize(""));
    }
    
    /**
     * Provides some values that might be passed to the serializer
     * 
     * @return \Generator
     *   List of values
     */
    private function provideValues(): \Generator
    {
        yield "";
        yield "foo";
        yield 42;
        yield ["foo" => "bar", "bar" => "foo"];
        yield new \stdClass();
        yield null;
        yield true;
        yield false; 
    }
    
    /**
     * Compare values generated provided by provideValues() to a given set
     * 
     * @param array $serialized
     *   Values to compare the set
     */
    private function compare(array $serialized): void
    {
        foreach ($this->provideValues() as $index => $value) {
            $this->assertEquals($serialized[$index], $value);
        }
    }
    
}
