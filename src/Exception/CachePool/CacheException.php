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

use Psr\Cache\CacheException as Psr6CacheException;

/**
 * CacheException CachePool component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheException extends \Exception implements Psr6CacheException
{
    //
}
