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
 * Use a Redis as a store
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCacheAdapter extends AbstractCacheAdapter
{
    
    /**
     * Redis store
     * 
     * @var \Redis
     */
    private $redis;
    
    /**
     * Initialize redis cache adapter
     * 
     * @param \Redis $redis
     *   Redis connection
     */
    public function __construct($redis)
    {
        $this->redis = $redis;
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::get()
     */
    public function get(string $key): ?string
    {
        return (false !== $value = $this->redis->get($key)) ? $value : null;
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::set()
     */
    public function set(string $key, string $value, ?int $ttl): bool
    {
        return (null === $ttl) ? $this->redis->set($key, $value) : $this->redis->setex($key, $ttl, $value);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::delete()
     */
    public function delete(string $key): bool
    {
        return 0 !== $this->redis->del($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::exists()
     */
    public function exists(string $key): bool
    {
        return 0 !== $this->redis->exists($key);
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::getMultiple()
     */
    public function getMultiple(iterable $keys): \Generator
    {
        foreach ($this->pipeline(function() use ($keys): void {
            foreach ($keys as $key)
                $this->redis->get($key);
        }, $keys) as $key => $value) {
            yield $key => (false === $value) ? null : $value;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::setMultiple()
     */
    public function setMultiple($values): ?array
    {
        return $this->log($this->pipeline(function() use ($values): void {
            foreach ($values as $value)
                (null === $value->ttl) 
                    ? $this->redis->set($value->key, $value->value) 
                    : $this->redis->setex($value->key, $value->ttl, $value->value);
        }, $values, true));
    }
    
    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::deleteMultiple()
     */
    public function deleteMultiple(iterable $keys): ?array
    {
        return $this->log($this->pipeline(function() use ($keys): void {
            foreach ($keys as $key)
                $this->redis->del($key);
        }, $keys));    
    }

    /**
     * {@inheritDoc}
     * @see \Zoe\Component\Cache\Adapter\CacheAdapterInterface::clear()
     */
    public function clear(?string $pattern): bool
    {
        $iterator = null;
        $errors = null;
        
        $hasPrefix = (null !== $prefix = $this->redis->getOption(\Redis::OPT_PREFIX));
        if($hasPrefix)
            $this->redis->setOption(\Redis::OPT_PREFIX, "");
        
        while($keys = $this->redis->scan($iterator, "*{$pattern}*", 3000))
            $this->deleteMultiple($keys, $errors);

        if($hasPrefix)
            $this->redis->setOption(\Redis::OPT_PREFIX, $prefix);
        
        return true;
    }
    
    /**
     * Pipeline action on redis
     * 
     * @param \Closure $action
     *   Action to perform into pipeline scope
     * @param array $values
     *   Values to process
     * @param bool $extractKey
     *   If keys from given values must be extracted for forming returned array
     *  
     * @return array
     *   Result for each call to redis over the pipeline indexed by the key
     */
    private function pipeline(\Closure $action, array $values, bool $extractKey = false): array
    {
        $this->redis->multi(\Redis::PIPELINE);
        $action->call($this);
        
        return \array_combine( ($extractKey) ? \array_keys($values) : $values, $this->redis->exec());
    }

}
