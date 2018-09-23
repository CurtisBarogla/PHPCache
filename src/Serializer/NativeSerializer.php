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
 * Use native serialize/unserialize php functions
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NativeSerializer implements SerializerInterface
{
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Serializer\SerializerInterface::serialize()
     */
    public function serialize($value): string
    {
        try {
            // can be serialized weirdly....
            if(\is_resource($value))
                throw new SerializerException();
            
            return \serialize($value);
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
        if(!isset($value[0]) || !isset($value[1]))
            return $value;
            
        if("N;" === $value)
            return null;
            
        if($value[1] !== ":")
            return $value;
        
        if("b:0;" === $value)
            return false;
            
        return (false !== $unserialized = @\unserialize($value)) ? $unserialized : $value;
    }
    
}
