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

namespace Zoe\Component\Cache\Exception\CachePool;

use Psr\Cache\InvalidArgumentException as Psr6InvalidArgumentException;

/**
 * InvalidArgumentException CachePool component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidArgumentException extends CacheException implements Psr6InvalidArgumentException
{
    //    
}