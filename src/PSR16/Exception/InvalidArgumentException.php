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

namespace Ness\Component\Cache\PSR16\Exception;

use Psr\SimpleCache\InvalidArgumentException as PSR16InvalidArgumentException;

/**
 * PSR6 InvalidArgumentException
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidArgumentException extends \InvalidArgumentException implements PSR16InvalidArgumentException
{
    //
}
