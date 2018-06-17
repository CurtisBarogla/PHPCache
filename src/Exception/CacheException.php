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

namespace Ness\Component\Cache\Exception;

use Psr\Cache\CacheException as PSR6CacheException;
use Psr\SimpleCache\CacheException as PSR16CacheException;

/**
 * Common Cache exception
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheException extends \Exception implements PSR6CacheException, PSR16CacheException
{
    //
}