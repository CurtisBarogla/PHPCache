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

namespace Zoe\Component\Cache\Exception\PSR6;

use Psr\Cache\InvalidArgumentException as PSR6InvalidArgumentException;

/**
 * InvalidArgumentException
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidArgumentException extends CacheException implements PSR6InvalidArgumentException
{
    //
}
