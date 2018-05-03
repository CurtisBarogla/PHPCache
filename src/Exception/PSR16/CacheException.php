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

use Psr\SimpleCache\CacheException as PSR16Exception;

/**
 * CacheException
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheException extends \Exception implements PSR16Exception
{
    //   
}
