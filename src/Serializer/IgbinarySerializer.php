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

namespace Ness\Component\Cache\Serializer;

use Ness\Component\Cache\Exception\SerializerException;

/**
 * Use igbinary.
 * Handles all serializable values
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class IgbinarySerializer implements SerializerInterface
{
    
    /**
     * Value for null
     * 
     * @var string
     */
    private const IG_BINARY_NULL = "0000000200";
    
    /**
     * Initialize serialize
     */
    public function __construct()
    {
        if(!\extension_loaded("igbinary"))
            throw new \RuntimeException("Igbinary extension not found. Use another serializer");
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Serializer\SerializerInterface::serialize()
     */
    public function serialize($value): string
    {
        try {
            if(\is_resource($value))
                throw new SerializerException();
                
            return \igbinary_serialize($value);
        } catch (\Exception $e) {
            throw new SerializerException();
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Serializer\SerializerInterface::unserialize()
     */
    public function unserialize(string $value)
    {
        if(!isset($value[0]))
            return $value;
        
        if(\bin2hex($value) === "0000000200")
            return null;
                
        return (null === $unserialized = @\igbinary_unserialize($value)) ? $value : $unserialized;
    }

}
