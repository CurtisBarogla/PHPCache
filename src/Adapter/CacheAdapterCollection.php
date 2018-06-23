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

/**
 * Collection of adapters
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheAdapterCollection implements CacheAdapterInterface
{

    /**
     * Adapters setted
     * 
     * @var CacheAdapterInterface[]
     */
    private $adapters;
    
    /**
     * Initialize adapter
     * 
     * @param string $identifier
     *   Identifier for the default adapter
     * @param CacheAdapterInterface $defaultAdapter
     *   Default adapter
     */
    public function __construct(string $identifier, CacheAdapterInterface $defaultAdapter)
    {
        $this->adapters[$identifier] = $defaultAdapter;
    }
    
    /**
     * Add a new adapter
     * 
     * @param string $identifier
     *   Adapter identifier
     * @param CacheAdapterInterface $adapter
     *   Cache adapter
     */
    public function addAdapter(string $identifier, CacheAdapterInterface $adapter): void
    {
        $this->adapters[$identifier] = $adapter;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return $this->execute(function(?string $value): ?string {
            return $value;
        }, "get", $key) ?? null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): array
    {
        $found = [];
        return $this->execute(function(array $results) use (&$keys, &$found): ?array {
            foreach ($results as $index => $result) {
                if(null !== $result) {
                    $found[$keys[$index]] = $result;
                    unset($keys[$index]);
                }                
            }
            
            if(empty($keys))
                return \array_values($found);
            
            $keys = \array_values($keys);
            
            return null;
        }, "getMultiple", $keys) ?? \array_merge(\array_values($found), \array_fill(0, \count($keys), null));
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        $success = false;
        $this->execute(function(bool $result) use (&$success): void {
            if($result)
                $success = true;
        }, "set", $key, $value, $ttl);
            
        return $success;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): ?array
    {
        $still = \array_keys($values);
        $this->execute(function(?array $result) use (&$still): void {
            $still = \array_intersect($result ?? [], $still);
        }, "setMultiple", $values);
            
        return (empty($still)) ? null : \array_values($still);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        $success = false;
        $this->execute(function(bool $result) use (&$success): void {
            if($result)
                $success = true;
        }, "delete", $key);
        
        return $success;
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys): ?array
    {
        $still = $keys;
        $this->execute(function(?array $result) use (&$still): void {
            $still = \array_intersect($result ?? [], $still);
        }, "deleteMultiple", $keys);
        
        return (empty($still)) ? null : \array_values($still);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        return $this->execute(function(bool $result): ?bool {
            return ($result) ? true : null;
        }, "has", $key) ?? false;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        $this->execute(null, "purge", $pattern);
    }
    
    /**
     * Execute an action over all adapters
     * 
     * @param \Closure|null $action
     *   Action to execute after the execution of an adapter. Takes as parameter the last result of the last adapter executed.
     *   If this action return anything elsa than null or void, it will stop the execution and return the last result returned.
     *   If setted to null, will simply loop over all adapters without doing anything else
     * @param string $method
     *   Method to execute on adapters
     * @param mixed &...$args
     *   Arguments to pass to the adapter
     *   
     * @return mixed
     *   Last result if setted or null
     */
    private function execute(?\Closure $action, string $method, &...$args)
    {
        foreach ($this->adapters as $identifier => $adapter) {
            $current = $adapter->{$method}(...$args);
            if(null !== $action && null !== $result = $action->call($this, $current)) 
                return $result;
        }
        
        return null;
    }
    
}
