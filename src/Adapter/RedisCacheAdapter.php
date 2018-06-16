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
 * Use a redis connection as a cache store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCacheAdapter implements CacheAdapterInterface
{
    
    /**
     * A redis connection
     * 
     * @var \Redis
     */
    private $redis;
    
    /**
     * Initialize adapter
     * 
     * @param \Redis $redis
     *   Redis connection
     */
    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false === $value = $this->redis->get($key)) ? null : $value;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(array $keys): array
    {
        return \array_map(function($result) {
            return (false === $result) ? null : $result; 
        }, $this->pipeline(function() use ($keys): void {
            foreach ($keys as $key)
                $this->redis->get($key);
        }));
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return (null === $ttl) ? $this->redis->set($key, $value) : $this->redis->setEx($key, $ttl, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple(array $values): ?array
    {
        $results = null;
        foreach (\array_combine(\array_keys($values), $this->pipeline(function() use ($values): void {
            foreach ($values as $key => $value)
                (null === $value["ttl"]) ? $this->redis->set($key, $value["value"]) : $this->redis->setex($key, $value["ttl"], $value["value"]);
        })) as $key => $result) {
            if(!$result)
                $results[] = $key;
        }
        
        return $results;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        return 0 !== $this->redis->delete($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(array $keys): ?array
    {
        $misses = \array_filter(\array_map(function(bool $result, string $key): ?string{
            if(!$result)
                return $key;
            return null;
        }, $this->pipeline(function() use ($keys): void {
            foreach ($keys as $key)
                $this->redis->delete($key);
        }), $keys));

        return empty($misses) ? null : \array_values($misses);
    }

    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::has()
     */
    public function has(string $key): bool
    {
        return 0 !== $this->redis->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\CacheAdapterInterface::purge()
     */
    public function purge(?string $pattern): void
    {
        if(null === $pattern) {
            $this->redis->flushAll();
            
            return;
        }

        // https://github.com/phpredis/phpredis/issues/548
        if(null !== $prefix = $this->redis->getOption(\Redis::OPT_PREFIX))
            $this->redis->setOption(\Redis::OPT_PREFIX, "");
        
        while ($keys = $this->redis->scan($iterator, "*{$pattern}*", 1000))
            $this->deleteMultiple($keys);
        
        if(null !== $prefix)
            $this->redis->setOption(\Redis::OPT_PREFIX, $prefix);
    }
    
    /**
     * Execute multiple operations over redis pipeline
     * 
     * @param \Closure $action
     *   Action to execute
     * 
     * @return array
     *   Result of execution
     */
    private function pipeline(\Closure $action): array
    {
        $this->redis->multi(\Redis::PIPELINE);
        $action->call($this);
        
        return $this->redis->exec();
    }

}
