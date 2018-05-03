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

use Psr\Cache\CacheException as PSR6Exception;

/**
 * CacheException
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheException extends \Exception implements PSR6Exception
{
    //   
}
