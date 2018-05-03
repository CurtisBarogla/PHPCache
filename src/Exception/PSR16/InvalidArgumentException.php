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

namespace Zoe\Component\Cache\Exception\PSR16;

use Psr\SimpleCache\InvalidArgumentException as PSR16InvalidArgumentException;

/**
 * InvalidArgumentException
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidArgumentException extends CacheException implements PSR16InvalidArgumentException
{
    //
}
