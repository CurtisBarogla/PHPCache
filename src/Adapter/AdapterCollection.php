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

namespace Zoe\Component\Cache\Adapter;

/**
 * Simply iterate over a set of registered AdapterInterface implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AdapterCollection implements AdapterInterface
{
    
    /**
     * Collection of adapters
     * 
     * @var AdapterInterface[]
     */
    private $adapters = [];
    
    /**
     * Initialize the adapter
     * 
     * @param string $name
     *   Adapter name
     * @param AdapterInterface $adapter
     *   Default adapter
     */
    public function __construct(string $name, AdapterInterface $adapter)
    {
        $this->adapters[$name] = $adapter;
    }
    
    /**
     * Add an adapter to the collection
     * 
     * @param string $name
     *   Adapter name
     * @param AdapterInterface $adapter
     *   AdapterInterface instance
     */
    public function add(string $name, AdapterInterface $adapter): void
    {
        $this->adapters[$name] = $adapter;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        foreach ($this->adapters as $adapter) {
            if(null !== $value = $adapter->get($key))
                return $value;
            else 
                continue;
        }
        
        return null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): \Generator
    {
        $iteratorCollection = new \AppendIterator();
        foreach ($this->adapters as $adapter) {
            $iteratorCollection->append($adapter->getMultiple($keys));
        }
        
        $yielded = [];
        foreach ($iteratorCollection as $key => $value) {
            if(isset($yieled[$key])) continue;
            if(null !== $value) {
                $yieled[$key] = true;
                yield $key => $value;
            } else {
                yield $key => null;
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return $this->doExecute("set", $key, $value, $ttl);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): array
    {
        $return = [];
        foreach ($this->adapters as $name => $adapter) {
            if(empty($values))
                break;
            $this->loopOverMultiple($values, "setMultiple", $adapter, $return);
        }
        
        return $return;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::del()
     */
    public function del(string $key): bool
    {
        return $this->doExecute("del", $key);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::delMultiple()
     */
    public function delMultiple(array $keys): array
    {
        $return = [];
        foreach ($this->adapters as $name => $adapter) {
            if(empty($keys))
                break;
            $this->loopOverMultiple($keys, "delMultiple", $adapter, $return);
        }
        
        return $return;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::exists()
     */
    public function exists(string $key): bool
    {
        return $this->doExecute("exists", $key);
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::clear()
     */
    public function clear(?string $pattern = null): bool
    {
        $fail = false;
        foreach ($this->adapters as $adapter) {
            if(false === $adapter->clear($pattern))
                $fail = true;
        }
        
        return !$fail;
    }
    
    /**
     * Loop over an adapter method that return array
     * 
     * @param array $values
     *   Values passed to the adapter
     * @param string $method
     *   Method to execute
     * @param AdapterInterface $adapter
     *   Adapter instance
     * @param int $iterator
     *   Iterator to flush successfully treated values
     * @param array $return
     *   Initialized array filled with result from adapter call
     */
    private function loopOverMultiple(
        array& $values, 
        string $method, 
        AdapterInterface $adapter,
        array& $return): void
    {
        // reset indexes
        if(!isset($values[0]))
            $values = \array_values($values);
        $iterator = 0;
        foreach ($adapter->{$method}($values) as $key => $result) {
            if(true === $result) {
                $return[$key] = true;
                unset($values[$iterator]);
            } else {
                $return[$key] = false;
            }
            $iterator++;
        }
    }
    
    /**
     * Execute a method over all registered adapters
     * 
     * @param string $method
     *   Method to execute
     * @param mixed $args
     *   Args passed to the method
     * 
     * @return bool
     *   Method call result
     */
    private function doExecute(string $method, ...$args): bool
    {
        foreach ($this->adapters as $adapter) {
            if($adapter->{$method}(...$args))
                return true;
            else
                continue;
        }
        
        return false;
    }

}
