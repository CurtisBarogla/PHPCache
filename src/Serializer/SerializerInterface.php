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
 * Handles complex values for making them compatibles with a cache storage 
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface SerializerInterface
{
    
    /**
     * Serialize complex value
     * 
     * @param mixed $value
     *   Value to serialize. Can be anything except a string
     * 
     * @return string
     *   Serialized value
     *  
     * @throws SerializerException
     *   When the value cannot be normalized
     */
    public function serialize($value): string;
    
    /**
     * Restore a value from its serialized form
     * 
     * @param string $value
     *   Serialized representation of the complex value
     * 
     * @return mixed
     *   Value restored
     *   
     * @throws SerializerException
     *   When the value cannot be restored
     */
    public function unserialize(string $value);
    
}
