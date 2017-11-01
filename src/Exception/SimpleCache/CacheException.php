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

use Psr\SimpleCache\CacheException as Psr16CacheException;

/**
 * CacheException SimpleCache component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheException extends \Exception implements Psr16CacheException
{
    //
}
