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

namespace Zoe\Component\Cache\Storage;

use Zoe\Component\Cache\Exception\InvalidRegexException;
use Zoe\Component\Filesystem\FilesystemInterface;
use Zoe\Component\Filesystem\Exception\IOException;
use Zoe\Component\Filesystem\Exception\InvalidDirectoryException;
use Zoe\Component\Filesystem\Exception\InvalidFileException;

/**
 * Storage using an os filesystem
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemStorage implements StorageInterface
{
    
    /**
     * Storage prefix
     * 
     * @var string
     */
    private $prefix;
    
    /**
     * Filesystem instance
     * 
     * @var FilesystemInterface
     */
    private $filesystem;
    
    /**
     * Directory where to store values.
     * Will be created if needed
     * 
     * @var string
     */
    private $directory;
    
    /**
     * Permanent storage
     * 2 years
     * 
     * @var int
     */
    private const PERMANENT_STORAGE = 63072000;
    
    /**
     * Considered permanent
     * 1 year
     * 
     * @var int
     */
    private const CONSIDERED_PERMANENT = 31536000;
    
    /**
     * Initialize the storage
     * 
     * @param FilesystemInterface $filesystem
     *   Filesystem instance
     * @param string $directory
     *   Directory store
     * @param string $prefix
     *   Prefix storage
     */
    public function __construct(FilesystemInterface $filesystem, string $directory, ?string $prefix = null)
    {
        $this->filesystem = $filesystem;
        $this->directory = $directory;
        $this->prefix = $prefix;
        
        $this->initCacheDirectory();
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::get()
     */
    public function get(string $key): ?string
    {
        $this->prefixKey($key);
        
        if(!$this->isValid($key)) return null;
        
        return $this->filesystem->getContent($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::set()
     */
    public function set(string $key, string $value): bool
    {
        $this->prefixKey($key);
        
        return $this->doSet($key, $value, null);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::setEx()
     */
    public function setEx(string $key, int $ttl, string $value): bool
    {
        $this->prefixKey($key);
        
        return $this->doSet($key, $value, $ttl);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::del()
     */
    public function del(string $key): bool
    {
        $this->prefixKey($key);
        
        if(!$this->isValid($key)) return false;
        
        $this->filesystem->rm($key);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::expire()
     */
    public function expire(string $key): bool
    {
        $this->prefixKey($key);
        
        if(!$this->isValid($key)) return false;
        
        $this->filesystem->touch($key, new \DateTime());
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::exists()
     */
    public function exists(string $key): bool
    {
        $this->prefixKey($key);
        
        return $this->isValid($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::rename()
     */
    public function rename(string $key, string $newkey): bool
    {
        $this->prefixKey($key);
        $this->prefixKey($newkey);

        if(!$this->isValid($key) || $this->isValid($newkey)) return false;
        
        $this->filesystem->rename($key, $newkey);
        
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::ttl()
     */
    public function ttl(string $key): ?int
    {
        $this->prefixKey($key);
        
        $exists = true;
        $expired = false;
        $ttl = null;
        
        $this->gc($key, $exists, $expired, $ttl);
        
        if(!$exists || $expired) return -1;
        
        return ($ttl >= self::CONSIDERED_PERMANENT) ? null : $ttl;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::flush()
     */
    public function flush(): bool
    {
        try {
            $this->filesystem->rmdirs($this->directory);
            
            return true;
        } catch (IOException $e) {
            return false;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::list()
     */
    public function list(?string $pattern = null): \Generator
    {
        $length = null;
        if(null !== $this->prefix)
            $length = \strlen($this->prefix);
        
        foreach ($this->filesystem->ls($this->directory, true) as $file) {
            $path = $file->getPathname();
            if(null !== $length) {
                if(!$this->hasCurrentPrefix($file->getFilename(), $length)) continue;
            }
            if(!$this->isValid($path)) continue;
            if(null !== $pattern) {
                $match = @\preg_match("#{$pattern}#", $file->getFilename());
                if(false === $match) {
                    throw new InvalidRegexException(\sprintf("This pattern '%s' is invalid",
                        $pattern));
                }
                if(0 === $match) continue;
            }
            yield $file->getFilename();
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Storage\StorageInterface::count()
     */
    public function count(): int
    {
        $count = 0;
        $length = null;
        if(null !== $this->prefix)
            $length = \strlen($this->prefix);
        foreach ($this->filesystem->ls($this->directory, true) as $key) {
            if(null !== $length)
                if(!$this->hasCurrentPrefix($key->getFilename(), $length)) continue;
            if(!$this->isValid($key->getPathname())) continue;
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Prefix a key
     * 
     * @param string $key
     *   Key to prefix
     */
    private function prefixKey(string& $key): void
    {
        $key = $this->directory.DIRECTORY_SEPARATOR.$this->prefix.$key;
    }
    
    /**
     * Check if the given key starts with the current prefix 
     * 
     * @param string $key
     *   Key to check if prefixed
     * @param int $prefixLength
     *   Length of the prefix
     * 
     * @return bool
     *   True if the key is prefixed. False otherwise
     */
    private function hasCurrentPrefix(string $key, int $prefixLength): bool
    {
        return \substr($key, 0, $prefixLength) === $this->prefix;
    }
    
    /**
     * Set a value into the store
     * 
     * @param string $key
     *   Cache key
     * @param string $value
     *   Cache value
     * @param int|null $ttl
     *   Time to live or null for permanent storage
     * 
     * @return bool
     *   True if the value has been stored correctly. False otherwise
     */
    private function doSet(string $key, string $value, ?int $ttl): bool
    {
        try {
            $this->filesystem->put($key, $value);
            
            if(null === $ttl) {
                $ttl = (string) self::PERMANENT_STORAGE;
                $this->filesystem->touch($key, new \DateTime("NOW + {$ttl} seconds"));
            } else {
                $ttl = (string) $ttl;
                $this->filesystem->touch($key, new \DateTime("NOW + {$ttl} seconds"));
            }
            
            return true;
        } catch (IOException $e) {
            return false;
        } catch (InvalidDirectoryException $e) {
            return false;
        } catch (InvalidFileException $e) {
            return false;
        }
    }
    
    /**
     * Expire invalid key is needed.
     * Executed on each call of the filesystem
     * 
     * @param string $key
     *   Key to evaluate
     * @param bool $exists
     *   Will be false if does not exist
     * @param bool $expired
     *   Will be true if the value has been expired
     * @param int|null $ttl
     *   Current ttl of the cache value
     */
    private function gc(string $key, bool& $exists, bool& $expired, ?int& $ttl = null): void
    {
        try {
            if(($ttl = $this->filesystem->getMTime($key)->getTimestamp() - \time()) <= 0) {
                $this->filesystem->rm($key);
                $expired = true;
            }
        } catch (IOException $e) {
            $exists = false;
        } catch (InvalidFileException $e) {
            $exists = false;
        }
    }
    
    /**
     * Check if the given cache value is still valid
     * 
     * @param string $key
     *   Cache key to check
     * 
     * @return bool
     *   True if the given cache value is still valid. False otherwise
     */
    private function isValid(string $key): bool
    {
        $exists = true;
        $expired = false;
        
        $this->gc($key, $exists, $expired);
        
        return $exists && !$expired;
    }
    
    /**
     * Initialize the cache directory if needed
     */
    private function initCacheDirectory(): void
    {
        if(!$this->filesystem->isDir($this->directory))
            $this->filesystem->mkdirs($this->directory); 
    }
    
}
