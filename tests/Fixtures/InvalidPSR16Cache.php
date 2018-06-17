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

namespace NessTest\Component\Cache\Fixtures;

use Ness\Component\Cache\Traits\ValidationTrait;

/**
 * Only for testing purpose
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InvalidPSR16Cache
{
    use ValidationTrait;
    
    /**
     * For testing purpose
     * 
     * @param string $key
     *   Key
     */
    public function exec(string $key): void
    {
        $this->validateKey($key);
    }

}
