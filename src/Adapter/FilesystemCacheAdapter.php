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

namespace Ness\Component\Cache\Adapter;

use Ness\Component\Cache\Exception\IOException;

/**
 * Use filesystem as cache store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemCacheAdapter implements CacheAdapterInterface
{
    
    /**
     * Root cache directory
     * 
     * @var string
     */
    private $directory;
    
    /**
     * Ten years in seconds :)
     * 
     * @var int
     */
    public const TEN_YEARS = 315360000;
    
    /**
     * Initialize adapter
     * 
     * @param string $directory
     *   Cache directory
     * @param string $prefix
     *   Cache prefix
     */
    public function __construct(string $directory, ?string $prefix = null)
    {
        $this->directory = (null === $prefix) ? $directory : "{$directory}/{$prefix}";
        
        if(!\is_dir($this->directory) && !\mkdir($this->directory, 0666, true))  {
            throw new IOException(\sprintf("Cache directory '%s' cannot be setted into '%s' directory",
                $this->directory,
                \dirname($this->directory)));
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        $this->prefix($key);
        
        return (!$this->gc($key)) ? \file_get_contents($key) : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): array
    {
        return \array_map(function(string $key): ?string {
            return $this->get($key);
        }, $keys);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        $this->prefix($key);

        return \file_put_contents($key, $value) && \touch($key, \time() + ( (null === $ttl) ? self::TEN_YEARS : $ttl ));
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): ?array
    {
        foreach ($values as $key => $value)
            if($this->set($key, $value["value"], $value["ttl"]))
                unset($values[$key]);
            
        return empty($values) ? null : \array_keys($values);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        $this->prefix($key);
        
        return !$this->gc($key) && \unlink($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys): ?array
    {
        foreach ($keys as $index => $key) {
            if($this->delete($key))
                unset($keys[$index]);
        }

        return empty($keys) ? null : \array_values($keys);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        $this->prefix($key);
        
        return !$this->gc($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        foreach (new \DirectoryIterator($this->directory) as $name => $file) {
            if($file->isDot())
                continue;
            $path = $file->getPathname();
            if(null === $pattern) {
                \unlink($path);
                continue;
            }
            if(0 === \preg_match("#{$pattern}#", $file->getFilename()))
                continue;
            \unlink($path);
        }
    }
    
    /**
     * Apply directory path and prefix to a key
     * 
     * @param string $key
     *   Key to prefix
     */
    private function prefix(string& $key): void
    {
        $key = "{$this->directory}/{$key}";
    }
    
    /**
     * Check validity of a file
     * 
     * @param string $path
     *   Path to file
     * 
     * @return bool
     *   True if the file has been gc. False otherwise
     */
    private function gc(string $path): bool
    {
        if(!\is_file($path))
            return true;
        
        if(\filemtime($path) > \time())
            return false;
        
        return \unlink($path);
    }

}
