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
 * Adapter accepted and instance of Redis as a store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisAdapter implements AdapterInterface
{
    
    /**
     * Redis store
     * 
     * @var \Redis
     */
    private $redis;
    
    /**
     * Initialize the adapter
     * 
     * @param \Redis $redis
     *   Redis instance
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false !== $value = $this->redis->get($key)) ? $value : null;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): \Generator
    {
        $this->redis->multi(\Redis::PIPELINE);
        foreach ($keys as $key) {
            $this->redis->get($key);
        }
        $results = $this->redis->exec();
        $results = \array_combine($keys, $results);
        
        foreach ($results as $key => $result) {
            if(false !== $result)
                yield $key => $result;
            else
                yield $key => null;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        if(null === $ttl)
            return true === $this->redis->set($key, $value);
        else
            return true === $this->redis->setex($key, $ttl, $value);
    }
        
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): array
    {
        $this->redis->multi(\Redis::PIPELINE);
        foreach ($values as $key => $value) {
            $ttl = $value["ttl"];
            if(null !== $ttl)
                $this->redis->setex($key, $ttl, $value["value"]);
            else 
                $this->redis->set($key, $value["value"]);
        }
        $results = $this->redis->exec();
        
        return \array_combine(\array_keys($values), $results);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::del()
     */
    public function del(string $key): bool
    {
        return 0 !== $this->redis->del($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::delMultiple()
     */
    public function delMultiple(array $keys): array
    {
        $this->redis->multi(\Redis::PIPELINE);
        foreach ($keys as $key)
            $this->redis->del($key);
        $results = $this->redis->exec();
        
        $results = \array_map(function(int $value): bool {
            return (bool) $value;
        }, $results);
        
        return \array_combine($keys, $results);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::exists()
     */
    public function exists(string $key): bool
    {
        return $this->redis->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::clear()
     */
    public function clear(?string $pattern = null): bool
    {
        $prefixAltered = false;
        $prefix = $this->redis->getOption(\Redis::OPT_PREFIX);
        if(null !== $prefix) {
            $prefixAltered = true;
            $this->redis->setOption(\Redis::OPT_PREFIX, "");
        }
        $iterator = null;
        
        while ($keys = $this->redis->scan($iterator, "*{$prefix}{$pattern}*", 3000))
            $this->delMultiple($keys);
        
        if($prefixAltered)
            $this->redis->setOption(\Redis::OPT_PREFIX, $prefix);
        
        return true;
    }
    
}
