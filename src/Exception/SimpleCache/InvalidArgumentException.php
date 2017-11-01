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

namespace Zoe\Component\Cache\Exception\SimpleCache;

use Psr\SimpleCache\InvalidArgumentException as Psr16InvalidArgumentException;

/**
 * InvalidArgumentException SimpleCache component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidArgumentException extends CacheException implements Psr16InvalidArgumentException
{
    //    
}