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

namespace Ness\Component\Cache\Traits;

use \Ness\Component\Cache\Exception\InvalidArgumentException;

/**
 * Handle manipulation and validation over cache component support tagging feature
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait TagHandlingTrait
{
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface::invalidateTag()
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::invalidateTags()
     */
    public function invalidateTag($tag)
    {
        $this->queueInvalidation($tag);
        
        return $this->tagMap->update(false);
    }
    
    /**
     * {@inheritDoc}
     * @see \Cache\TagInterop\TaggableCacheItemPoolInterface::invalidateTags()
     * @see \Ness\Component\Cache\PSR16\TaggableCacheInterface::invalidateTags()
     */
    public function invalidateTags(array $tags)
    {
        \array_map([$this, "queueInvalidation"], $tags);
        
        return $this->tagMap->update(false);
    }
    
    /**
     * Apply validation rules declared on a tag
     * 
     * @param string $tag
     *   Tag to validate
     * 
     * @throws InvalidArgumentException
     *   When the given tag is invalid
     */
    private function validateTag(string $tag): void
    {
        if(\strlen($tag) > self::MAX_LENGTH_TAG || 0 === \preg_match("#^[".self::ACCEPTED_CHARACTERS_TAG."]+$#", $tag))
            throw new InvalidArgumentException("This tag '{$tag}' is invalid. Tag length MUST be < 32 and contains only alphanum characters");
    }
    
    /**
     * Queue an invalidate of a specific tag
     * 
     * @param string $tag
     *   Tag to queue for invalidation
     */
    private function queueInvalidation(string $tag): void
    {
        $this->tagMap->delete($this->adapter, $tag, self::$gcTapMap);
    }
    
}
