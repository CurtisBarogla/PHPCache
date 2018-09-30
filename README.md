# PSR-6 - 16 Cache Component

This library provides you a simple way to cache values for optimizing performances of your application

This library is compliant with both PSR-6 and PSR-16 interfaces

0. [How to install](#0-installing-the-component)
1. [Why ?](#1-why)
2. [How to use](#2-how-to-use)
3. [Cache adapter](#3-cache-adapter)
4. [Serializer](#4-serializer)
5. [[PSR-6] CacheItemPool](#5-cacheitempool-psr-6)
6. [[PSR-16] Cache](#6-cache-psr-16)
7. [Contributing](#7-contributing)
8. [License](#8-license)

## 0. Installing the component

Cache library can be installed via composer

~~~bash
$ composer require ness/cache
~~~

## 1. Why ?

This library provides you multiple ways to cache values for whatever reasons. Most of the time for optimisation's matters, but can be extended to an API restricting you to a certain number of requests... it can be anything.

Simple to use and easily extendable via an implementation of a new CacheAdapter fitting your needs.

This library is fully based on the **recommendations** published by the [PHP-FIG](https://www.php-fig.org/), under two PSR : [PSR-6](https://www.php-fig.org/psr/psr-6/) and [PSR-16](https://www.php-fig.org/psr/psr-6/), therefore can be installed on every applications supporting this **PSR**.

Also supports, only for PSR-6 now (can be extended to PSR-16 if needed), a **tagging** feature via an implementation of [tag-interop](https://github.com/php-cache/tag-interop) which allows you to invalidate massively cached values not based on the cache key but on a tag associated to them in one call.

## 2. How to use

This library comes with already configured classes, acting as factory internally, allowing you to cache easily and rapidly your values into multiple stores without much configuration.

Depending of your needs, each cache can be **personnalized** via : 
- a global time to live applied to all your non explicitly setted cache values. Can be :
    - null, 
    - an integer (time in seconds)
    - a DateInterval.
- a specific namespace (which isolate your values),
- an instance of a PSR-3 implementation - useful for logging errors when a value cannot be stored.

All cache classes support both of the PSR (PSR-6 and PSR-16).

See : 
- [PSR-6 CacheItemPool](#5-cacheitempool-psr-6)
- [PSR-16 Cache](#6-cache-psr-16)

for more informations on how to use each of the PSR.

### 2.1 "Development" environment

Two specific caches are provided which each of them can be useful for setting a cache layer into your application for your development environment without really persisting your values.

#### 2.1.1 NullCache

It's the dummy by excellence. Nothing is persisted at all and it returns always a negative values not matter what.

~~~php
<?php
$nullCache = new NullCache(); // nothing is stored at all
~~~

#### 2.1.2 InMemoryCache

Can be percieved as a dummy cache too, but not at all. All values are actually stored in memory and later reusables while the cache is still alive.

~~~php
<?php
$inMemoryCache = new InMemoryCache(); // all features are available like any other cache
~~~

### 2.2 "Production" environment

For reals environments, the most common caches (in my opinion) are already implemented, but as already stated, a new cache class is easily implementable by providing your own implementation of CacheAdapter and simply extending it from AbstractCache.

#### 2.2.1 ApcuCache

It only requires apcu extension installed and enabled in your php environment ; nothing else is required.

~~~php
<?php
$inMemoryCache = new ApcuCache(); // it's done
~~~

#### 2.2.2 FilesystemCache

This cache use your local filesystem to store your cache values. It will create a file for each key.

Requires only a path to a writable directory. The final directory is created (recursively) if needed.

~~~php
<?php
$filesystemCache = new FilesystemCache("./foo/bar/cache"); // all values are store into directory ./foo/bar/cache
~~~

#### 2.2.3 MemcachedCache

Will use a memcached server to store your cached values.

Requires [memcached](http://php.net/manual/fr/memcached.installation.php) extension enabled

~~~php
<?php
$memcached = new Memcached();
$memcached->addServer("127.0.0.1", 11211); // default values

$memcachedCache = new MemcachedCache($memcache); // that's it
~~~

#### 2.2.4 RedisCache

Will use a redis server to store your cached values.

Only compatible with [phpredis](https://github.com/phpredis/phpredis) (\Redis class) extension.

~~~php
<?php
$redis = new Redis();
$redis->connect("127.0.0.1", 6379); // default values

$redisCache = new RedisCache($redis); // that's it
~~~

### 2.3 Personnalize your cache

As already stated, your cache is personnalizable if you provide more informations to the class's constructor.

(This behaviour is, for obvious reasons, disable for the NullCache).

A simple example, we want to cache our values into a specific (foo) namespace, with a shared default time to live over all our values (20 minutes as a DateInterval) and we want to log when a setting error happen.

~~~php
<?php
$defaultTime = new DateInterval(PT20M); // by default, all values with no explicitly provided expiration time will be setted to 20 minutes
$cacheNamespace = "foo"; // this cache has its own namespace, impossible to access, delete a cached values from the global or  another namespace
$logger = new PSR3LoggerImplementation(); // my logger

$yourCache = new MyCache($defaultTime, $cacheNamespace, $logger); // that's is, fully configured
~~~

### 2.4 Implementing your own cache

Implementing your own cache "factory" is pretty simple. 

It consists in a simple class extending from the AbstractCache class (which implements for you all the proxy methods communicating with a PSR-6 and a PSR-16 component) which register the CacheAdapter of your choice calling the parent constructor responsible of the initialization of the cache component.

~~~php
<?php
class MyOwnCache extends AbstractCache
{

    /**
     * MyOwnCache
     * 
     * @param int|null|\DateInterval $defaultTtl
     *   Default ttl to apply to the cache pool and the cache. Must be compatible
     * @param string|null $namespace
     *   Namespace of the cache. If setted to null, will register cache values into global namespace
     * @param LoggerInterface|null $logger
     *   If a logger is setted, will log errors when a cache value cannot be setted
     *   
     * @throws InvalidArgumentException
     *   When the default ttl is not compatible between PSR6 and PSR16
     */
    public function __construct($defaultTtl = null, ?string $namespace = null, ?LoggerInterface $logger = null)
    {
        $this->adapter = new MyCacheAdapterImplementation(); // my adapter
        parent::__construct($defaultTtl, $namespace, $logger);
    }

}

// that's it, your own cache class compatible with both PSR-6 and PSR-16
~~~

## 3. Cache adapter

Cache adapter describes how the values are stored into a cache store.

All cache adapters MUST comply CacheAdapterInterface.

### 3.1 Getting values

Adapter provides two ways to get values from a cache store.

Method **get()** which will return the value corresponding the given key or null if no value found

Method **getMultiple()** which will return for each given keys as a sequential array the cached value or null if no value found for the current key

Method **has()** just check if a value is stored under a given key.

~~~php
<?php
$adapter = new CacheAdapterImplementation();

// let's assume a value bar has been stored under foo key
$adapter->get("foo") // will return bar
$adapter->get("bar") // will return null, no value stored for bar key

// let's assume two values (bar, foo) have been stored under foo and bar keys
$adapter->getMultiple(["foo", "bar"]); // will return ["bar", "foo"];
$adapter->getMultiple(["foo", "kek", "bar"]); // will return ["bar", null, "foo"]

// let's assume foo key exists
$adapter->has("foo"); // will return true
$adapter->has("bar"); // will return false
~~~

**Note for implementors**

If the cache store does not handle natively a way to get multiple keys, AbstractCacheAdapter abstract class already provides the implemented getMultiple() method which simply iterate over get() method. 

### 3.2 Setting values

Setting values can be done for single or multiple values.

Method **set()** will set a value under the given key into the cache store. A time to live (time which the value is still considered valid) value in seconds can be provided. This method returns a boolean.

Method **setMultiple()** allows you to set multiple values into the cache store in one call. Values must be an indexed array (index is the cache key), each key referred a cache value (represented as an indexed array). <br />
This method will return null if all cache values have been stored with success or it will return an array filled with all cache keys that failed to be stored.

~~~php
<?php
$adapter = new CacheAdapterImplementation();

// let's assume a foo key is stored with success and a bar key not
$adapter->set("foo", "bar", 10); // returns true. bar value is stored under foo key with a ttl of 10 seconds
$adapter->set("bar", "foo"); // returns false

// let's assume all values have been store with success
$adapter->setMultiple([
    "foo"    =>    ["value" => "bar", "ttl" => 10],
    "bar"    =>    ["value" => "foo", "ttl" => null],
]); 
// returns null
// bar value is store under foo key with a ttl of 10 seconds and foo value is store under bar key with no ttl setted

// let's assume bar failed to be stored
$adapter->setMultiple([
    "foo"    =>    ["value" => "bar", "ttl" => 10],
    "bar"    =>    ["value" => "foo", "ttl" => null],
]); 
// returns ["bar"]
~~~

**Note for implementors**

If the cache store does not handle natively a way to set multiple keys, AbstractCacheAdapter abstract class already provides the implemented setMultiple() method which simply iterate over set() method. 

### 3.3 Remove and clear values

Removing values can be done for a single value or multiple values.

Method **delete()** will try to delete a cache value identified by its key from the cache store and will true if the value has been successfully removed or false if the value has been not previously setted or cannot be removed.

Method **deleteMultiple()** will try to delete multiple values identified by their respective key and returns null if all values have been removed or an array filled with all keys that failed to be removed.

Method **clear()** will clear the cache store. Can take as parameter a pattern which represent only a set of cache keys to clear.

~~~php
<?php
$adapter = new CacheAdapterImplementation();

// let's assume a foo key is stored
$adapter->delete("foo"); // returns true
$adapter->delete("bar"); // return false

// let's assume a foo and bar keys are stored and both removed successfully
$adapter->deleteMultiple(["foo", "bar"]) // returns null

// let's assume a foo and bar keys are stored and bar failed to be removed
$adapter->deleteMultiple(["bar", "bar"]) // return ["bar"]

// this will clear the cache store
$adapter->clear(null);

// this will remove only keys containing _foo_
$adapter->clear("_foo_");
~~~

**Note for implementors**

If the cache store does not handle natively a way to delete multiple keys, AbstractCacheAdapter abstract class already provides the implemented deleteMultiple() method which simply iterate over set() method.

### 3.4 Provided implementations

This library provides you natively some adapters to interact with a cache store.

#### 3.4.1 NullCacheAdapter and InMemoryCacheAdapter

This two adapters in a first look could seem equals, but they are not.

InMemoryCacheAdapter actually acts as a cache store itself in contrary of NullCacheAdapter which will always return a fail no matter what.

InMemoryCacheAdapter will store and keep values until it has been destroyed which its state will be reset to its null state.

~~~php
<?php
$dummyAdapter = new NullCacheAdapter();
$inMemoryAdapter = new InMemoryCacheAdapter();

$dummyAdapter->set("foo", "bar") // will always return false
$dummyAdapter->get("foo") // will always return null

$inMemoryAdapter->set("foo", "bar") // will return true
$inMemoryAdapter->get("foo") // will really return bar

// same behaviour is applied to all methods
~~~

#### 3.4.2 ApcuCacheAdapter

This adapter only required acpu extension enabled to work. No configuration needed at all.

#### 3.4.3 RedisCacheAdapter

RedisCacheAdapter needs a connection to a redis server which will act as a cache store.

Supports only \Redis class provided by the [phpredis](https://github.com/phpredis/phpredis) extension.

~~~php
<?php
$redis = new Redis();
$redis->connect("127.0.0.1", 6379);

$adapter = new RedisCacheAdapter($redis);

// fully functionnal
~~~

#### 3.4.4 MemcachedCacheAdapter

MemcachedCacheAdapter required the memcached extension enabled.

This adapter only requires a connection to a [memcached](http://php.net/manual/fr/memcached.installation.php) server which will act as a cache store.

~~~php
<?php
$memcached = new Memcached();
$memcached->addServer("127.0.0.1", 11211);

$adapter = new MemcachedCacheAdapter($memcached);

// fully functionnal
~~~

#### 3.4.5 FilesystemCacheAdapter

FilesystemCacheAdapter will use a path given into the constructor to store cache values. <br />
If the path given does not correspond to an existed directory, it will be created (recursively if needed).

**A CacheException is thrown if the cache directory cannot be created.**

Each value will be represented as a single file.

~~~php
<?php
$adapter = new FilesystemCacheAdapter(./foo/bar);

// a namespace can be assigned

$adapterNamespaced = new FilesystemCacheAdapter(./foo/bar, "foo");

// values stored via $adapter cannot be accessed via $adapterNamespaced and vice versa
~~~

#### 3.4.6 CacheAdapterCollection

The CacheAdapterCollection allows you to set multiple CacheAdapterInterface implementations into one place.

All cache values are lazy loaded, except for the setting operations which values are store into the main adapter and dumped into all registered one.

All operations are performed on the adapters depending the order of registration.

~~~php
<?php
$fastAdapter = new CacheAdapterImplementation();
$mediumAdapter = new CacheAdapterImplementation();
$slowAdapter = new CacheAdapterImplementation();

$adapter = new CacheAdapterCollection($fastAdapter); // default adapter. should be the fastest one
$adapter->addAdapter($mediumAdapter);
$adapter->addAdapter($slowAdapter);
~~~

Let's take a look over some specification of this adapter and what is the "lazy load" thing.

All exemples given here depend of the CacheAdapterCollection configuration setted above.

##### 3.4.6.1 Getting values from the collection

~~~php
<?php
// let's assume the foo key is not found into the $fastAdapter but into the $mediumAdapter
$adapter->get("foo");
~~~

For this specific case, the adapter will ask in first to the fastest one if it contains the cached value : result a null. Therefore, the adapter dump to the medium one which return a valid value. The slowest one is not even in the game right now.

~~~php
<?php
/*
 * Let's assume here : 
 * foo key is found into the fast adapter
 * bar key is not found into the fast adapter, but into the medium adapter
 * moz key is not found into the fast adapter, not into the medium, but into the slowest
*/ 
$adapter->getMultiple(["foo", "bar", "moz"]);
~~~

In this case : 
- the fastest adapter is pinged with ["foo", "bar", "moz"], returns only value attached to the foo key. <br />
- the medium adapter is pinged only with ["bar", "moz"], returns only value attached to the bar key. <br />
- the slowest is pinged with only ["moz"] key and returns its value.

##### 3.4.6.2 Setting values into the collection

No matter what, all values will pinged to all setted adapters.

~~~php
<?php 
$adapter->set("foo", "bar");
$adapter->setMultiple([
    "foo"    =>    ["value" => "bar", "ttl" => null],
    "bar"    =>    ["value" => "foo", "ttl" => 10]
]);
~~~

If the value can be stored successfully at least into one adapter, this will return true.

##### 3.4.6.3 Removing values from the collection

Like the setting process, delete process pinged all adapters.

~~~php
<?php 
$adapter->delete("foo"); // will return true if the value has been removed from at least one adapter
$adapter->deleteMultiple(["foo", "bar"]); // returns null if all keys have been removed no matter the adapter concerned
~~~

### 3.5 Logging errors

This library provides you a way to log errors happening during an adapter operation via an implementation (acting as a wrapper) of CacheAdapterInterface.

~~~php
<?php 
$adapter = new CacheAdapterImplementation();

// this class required an PSR-3 Logger implementation
$loggableAdapter = new LoggingWrapperCacheAdapter($adapter);
$loggableAdapter->setLogger(new PSR3LoggerImplementation());

// that's it. now all setting errors (by default - can be personnalized) from the wrapped adapter will be logged
~~~

This implementation can be personnalized in multiple ways via its constructor.

- The CacheAdapterInterface implementation to log.
- An identifier which the name will be used to represent the wrapped adapter. This parameter is not a mandatory and can be setted to null ; therefore, the class name of the adapter will be used to identify the adapter.
- LogLevel used provided by PSR-3
- Finally a bit mask representing the errors to log. By default, it will logs only the errors from setting (set() and setMultiple()) operations. <br />
Values accepted are :
    - LogAdapterLevel::LOG_GET
    - LogAdapterLevel::LOG_SET
    - LogAdapterLevel::LOG_DELETE

A fully configured LogAdapter : 

~~~php
<?php 
$adapter = new CacheAdapterImplementation();
// this will log delete and set errors
$loggableAdapter = new LoggingWrapperCacheAdapter(
    $adapter,                                            // adapter wrapped
    null,                                                // let the log adapter handled the creation of the identifier
    LogLevel::ERROR,                                     // log level used by PSR-3
    LogAdapterLevel::LOG_DELETE|LogAdapterLevel::LOG_SET // log delete and set errors
);
$loggableAdapter->setLogger(new PSR3LoggerImplementation());
~~~

## 4. Serializer

A serializer is a simple interface linked between your cache implementation and a cache store. It allows you to manipulate values before stored or fetched. 

It consists in two simple methods : 
- **serialize()** which will normalize your values before they are sended into the cache store,
- **unserialize()** which will restore your values fetched from the cache store.

Failing to serialize or restore a value MUST raise a SerializerException which your cache component must handle.

**! Important !**

A string MUST never be processed by the serialize method !

### 4.1 NativeSerializer

This library provides you a simple but complete implementation of SerializerInterface. 

~~~php
<?php 
$serializer = new NativeSerializer();

$myValues = [
    42,
    42.42,
    true,
    false,
    new stdClass(),
    ["foo" => "bar", "bar" => "foo", "moz" => new stdClass(), "poz" => 42.42],
    null
];

$serialized = [];
// serializing
foreach($myValues as $value) {
    $serialized[] = $serializer->serialize($value);
}

$unserialized = [];
// unserializing
foreach($serializer as $value) {
    $unserialized[] = $serializer->unserialize($value);
}

// $myValues and $unserialized are basically same
~~~

**! Note !**

If you intend to serialize an object, I higly encourage you to make it compliant with [SerializableInterface](http://php.net/manual/en/class.serializable.php) to sanitize it.

**! Important !**

Trying to serialize resources, anonymous functions, anonymous classes will result a SerializerException. If you intend to serialize this kind of values, feel free to implement your own serializer.

## 5. CacheItemPool (PSR-6)

CacheItemPool is a complete implementation of [PSR-6](https://www.php-fig.org/psr/psr-6/)

**! Important !**

A serializer MUST be provided **before** even the instantiation of the CacheItemPool.

To do so, a simple a call to **CacheItemPool::registerSerializer()** needs to be done.

~~~php
$serializer = new SerializerImplementation();
CacheItemPool::registerSerializer($serializer);
~~~

Trying to overwrite an already setted serializer via CacheItemPool::registerSerializer() means nothing as once it's setted, the pool is locked.

To overwrite a serializer, it MUST before be unregistered via **CacheItemPool::unregisterSerializer()**.

~~~php
$serializer = new SerializerImplementation();
CacheItemPool::registerSerializer($serializer);

$newSerializer = new SerializerImplementation();
CacheItemPool::unregisterSerializer(); // serializer is detached, trying to save/get a new CacheItem will result an error
CacheItemPool::registerSerializer($newSerializer);
~~~

When the serializer component fails to restore a value, a null CacheItem will be returned.

When the serializer component fails to normalize value stored into the CacheItem, it will not even be sent to the CacheAdapter.

**! Note !**

Once a serializer is setted, I highly discourage an overwriting procedure.

### 5.1 CacheItem

The CacheItem is how the PSR-6 Cache handle cache mechanism.

#### 5.1.1 Interacting with the cache item

This interface provides some basic methods to access informations about the CacheItem, independent the fact the CacheItem is provided by a cache store or not.

~~~php
<?php 
// let's initialize a simple CacheItem
$item = new CacheItem("FooItem");

// getting the cache key
$item->getKey(); // will return FooItem

// setting value into it via set method
$item->set("Foo");

// getting the value
$item->get(); // will return Foo

// isHit will check if the item is actually a cache hit ot not
$item->isHit(); // return true is it's a cache hit, false if not
~~~

**! Important !**

If you intend to store a non string value, **it MUST be compatible with your serializer !**

No verification are done whatsoever on the value setted into the CacheItem, it's up to you to check what you are storing !

#### 5.1.2 Applying expiration time

Expiration time can be applied on a CacheItem in multiple ways.

Method expiresAt() requires a DateTime instance (date which the item is not valid anymore) or null.

Method expiresAfter() requires a DateInterval, an integer (representing the time to live of the item in seconds) or null.

~~~php
<?php 
// apply an expiration time on this item can be done in two differents ways. By default, all cache items have no expiration time
// expiresAt method which takes as parameter a DateTimeInterface or null
$item->expiresAt(new DateTime("NOW + 10 seconds")) // given the parameter, the item is valid for 10 seconds
$item->expiresAt(null) // given this, the item has no expiration time

// expiresAfter which takes as parameter a DateInterface, a time in seconds or null
$item->expiresAfter(new DateInterval("PT10S")) // given the parameter, the item is valid for 10 seconds
$item->expiresAfter(20) // given the parameter, the item is valid for 20 seconds
$item->expiresAfter(null) // given this, the item has no expiration time
~~~

**! Important !**

A CacheItem SHOULD/MUST **never** be instantiated by the user. It's initialization SHOULD/MUST be delegated to a CacheItemPool.

### 5.2 CacheItemPool

The **CacheItemPool** is the main and should be only way to fetch (from a cache store or not) a CacheItem.

- CacheItemPool must be provided with the CacheAdapterInterface implementation of your choice, 
- A default ttl can be setted which will be applied to all CacheItem which the expiration time has been not explicitly setted to null ; this expiration parameter can be
    - null,
    - a DateTime instance
    - an integer (time in seconds)
    - a DateInterval
- A namespace allowing you to store your CacheItem in an isolated environment.

~~~php
<?php 
$adapter = new CacheAdapterImplementation();

$poolGlobal = new CacheItemPool($adapter); 
// all CacheItem are stored with an indeterminate expiration time into the global namespace

$poolFoo = new CacheItemPool($adapter, new DateInterval("PT20M"), "foo") 
// for this CachePool all CacheItem non explicitly configured via expires methods are stored into a foo namespace
~~~

A namespaced CachePool is unable to interact with the global namespace nor another one in any case. 

**! Important !**

Each method implying usage of cache key(s), given key(s) must respect pattern ([a-zA-Z0-9_.]) imposed by the PSR-6 or a InvalidArgumentException will be thrown.

#### 5.2.1 Getting items

Fetching items can be done in three different ways.

Method **getItem()** will return an initialized CacheItem no matter the answer of the CacheAdapter.

Method **getItems()** will return an indexed array which each key corresponds to an asked cache key. Each key is assigned, no matter what, an initialized CacheItem().

Method **hasItem()** which will simply check if a CacheItem is actually stored for the given key. 

~~~php
<?php 
$adapter = new CacheAdapterImplementation();

// let's assume a key foo is found with a bar value setted
$pool = new CacheItemPool($adapter);
$item = $pool->getItem("foo"); // a CacheItem

$item->isHit(); // true
$item->get(); // bar

// try to get a non registered item
$item = $pool->getItem("bar"); // a CacheItem
$item->isHit(); // false
$item->get(); // null

// getting multiple items in one call
$items = $pool->getItems(["foo", "bar"]); // an array of CacheItem
$items["foo"]->isHit(); // true
$items["foo"]->get(); // bar

$items["bar"]->isHit(); // false
$items["bar"]->get(); // null

// simply checking the presence in cache of a CacheItem
$pool->hasItem("foo"); // true
$pool->hasItem("bar"); false
~~~

**! Important !**

It is highly discouraged to use method **isHit()** for retrieving a CacheItem from a cache store.

~~~php
<?php 
$adapter = new CacheAdapterImplementation();
$pool = new CacheItemPool($adapter); 

// the recommended way to get a CacheItem and perform some actions on it depending of its state
if(!( $item = $pool->getItem("foo") )->isHit()) {
   // actions when the item is not stored
} else {
    $value = $item->get(); // value stored into the CacheItem
}
~~~

#### 5.2.2 Saving items

Like getting, saving a CacheItem can be done in multiple ways.

Method **save()** which will directly store a CacheItem into a cache store. This method will return true if the value has been store with success.

Method **saveDeferred()** and **commit()** work often together. Mostly useful if you want to store multiple items in one call allowing you to optimize (depending of the cache store and the implementation of the CacheAdapter) the caching process.

~~~php
<?php 
$adapter = new CacheAdapterImplementation();
$pool = new CacheItemPool($adapter); 

// let's see how to store a simple item the recommanded way :) assume the pool returns a null CacheItem
if(!( $item = $pool->getItem("foo") )->isHit()) {
   // process the value to store. can be anything : slow api call (hello youtube), long process...
   $value = mySlowProcess();
   $item->set($value)->expiresAfter(new DateInterval(PT20)); // expires after 20 minutes
   $pool->save($item); // return true if the value has been cached with success
   // that's it, next call will considered the CacheItem a cache hit
} else {
    $value = $item->get(); // value stored into the CacheItem
}

// here do whatever you want with your value
~~~

Now, let's see a simple usecase of the duo saveDeferred() and commit().

~~~php
$adapter = new CacheAdapterImplementation();
$pool = new CacheItemPool($adapter); 

// we want multiple keys from the CachePool
foreach($pool->getItems(["foo", "bar", "moz", "poz"]) as $key => $item) {
    if(!$item->isHit()) {
        $values[$key] = mySlowProcess($key); // a slow process which take an id making differents actions depending of it
        // imagine determining a specific expiration time from a repository which will return a DateInterval for a key
        $expiration = getExpirationForKey($key);
        $item->set($values[$key])->expiresAfter($expiration);
        $pool->saveDeferred($item); // returns true if the CacheItem has been correctly queued
    } else {
        $values[$key] = $item->get(); // value stored into the CacheItem
    }
    // commit
    $pool->commit(); // return true if ALL CacheItem have been committed with success
}

// here, you have a fully initialized $values array indexed by your keys
~~~

#### 5.2.3 Delete items

The most simple operation doable on a CachePool.

Like the other operations, delete processing allows you to remove cached values singularly or massively.

Method **deleteItem()** which will remove a single values by its key. Will return true if the given key corresponds to an actually stored value and this value has been removed successfully.

Method **deleteItems()** which takes as parameter a set of cache keys to remove. Will return true only if all cached values have been removed.

Method **clear()** which will clear all cached values from the current namespace.

~~~php
$adapter = new CacheAdapterImplementation();
$pool = new CacheItemPool($adapter);
 
// assume here that foo is actually stored
$pool->deleteItem("foo"); // will return true
$pool->deleteItem("bar"); // will return false

// assume here that foo is actually stored
$pool->deleteItems(["foo", "bar"]) // will return false as bar key does not correspond to an actually store value

// clear global namespace
$pool->clear();
~~~

### 5.3 TaggableCacheItem

A TaggableCacheItem is a simple **improvement** of the basic CacheItem allowing you to apply tags on the item.

It introduces two new methods.

Method **setTags()** taking as parameter an array of tags.

Method **getPreviousTags()** which returns all tags previously saved if the CacheItem is considered hitted.

~~~php
<?php 
$item = new TaggableCacheItem("foo");

// suppose this item has been previously saved with foo and bar tags
$item->setTags(["moz", "poz"]); // overwrite already setted tags
$item->getPreviousTags(); // will return ["foo", "bar"]
~~~

**! Important !**

A TaggableCacheItem SHOULD/MUST **never** be instantiated by the user. It's initialization SHOULD/MUST be delegated to a TaggableCacheItemPool.

### 5.4 TaggableCacheItemPool

TaggableCacheItemPool is an implementation of TaggableCacheItemPoolInterface extending the possibilities of the CacheItemPool proposed by [php-cache](https://github.com/php-cache/tag-interop) allowing you to apply tags on a CacheItem for later massively interacting on them.

For now, this interface provides a way to invalidate a set of CacheItem not based on their keys but on a/multiple tags associate to them via two methods : 
- **invalidateTag()** which invalidate all items based on one tag
- **invalidateTags()** which invalidate all items based on multiple tags

All CacheItem cached via a "simple" CacheItemPool are compatible with the TaggableCacheItemPool and will the converted if needed.

This pool is configurable like a CacheItemPool could be. <br /> 
A default time to live can be applied and the values could be isolated in a specific namespace.

~~~php
<?php
$adapter = new CacheAdapterImplementation(); 
$pool = new TaggableCacheItemPool($adapter); // my taggable pool

// let's assume two items are stored, one tagged with ["foo", "bar"] and the other with ["bar"]
$pool->invalidateTag("foo"); // return true if all items have been successfully removed
$pool->getItem("foo"); // returns a null TaggableCacheItem
$pool->getItem("bar"); // still cached

// multiple tags can be invalidated in one call via invalidateTags()
$pool->invalidateTags(["foo", "bar"]); // remove all items with foo and bar tags associted to it
~~~

**! Important !**

Tags are also fully isolated via the pool's namespace.

Each method implying usage of cache key(s), given key(s) must respect pattern ([a-zA-Z0-9_.]) imposed by the PSR-6 or a InvalidArgumentException will be thrown.

## 6. Cache (PSR-16)

Cache is a complete implementation of [PSR-16](https://www.php-fig.org/psr/psr-16/).

PSR-16 answers to the same needs than PSR-6 (caching). 
In opposition to PSR-6, which can be percieved kinda complexe to use, PSR-16 is more **straightforward** in its usage - you're dealing directly with your values. <br />
There is no layer to configure/interact between your cached values and the cache store.

Despite this differences, Cache **allows** too :
- the configuration of a default time to live applied to all values which the expiration time has been not explictly setted - can be :
    - null, 
    - an integer (time in seconds),
    - a DateInterval.
- a complete isolation of your values via a namespace.

Cache does not support tagging for now. If it's a need, it can be implemented pretty easily, i'm open to your doléances.

Cache requires a CacheAdapterInterface which makes the connections between a cache store and the cache component.

~~~php
$adapter = new CacheAdapterImplementation();
$cacheGlobal = new Cache($adapter); // all values are registered into global namespace with no default time to live
$cacheFoo = new Cache($adapter, 20, "foo"); // all values are registered into the namespace foo with a default ttl of 20 seconds
~~~

**! Important !**

A serializer MUST be provided **before** even the instantiation of the Cache component.

To do so, a simple a call to **Cache::registerSerializer()** needs to be done.

~~~php
$serializer = new SerializerImplementation();
Cache::registerSerializer($serializer);
~~~

Trying to overwrite an already setted serializer via Cache::registerSerializer() means nothing as once it's setted, the cache is locked.

To overwrite a serializer, it MUST before be unregistered via **Cache::unregisterSerializer()**.

~~~php
$serializer = new SerializerImplementation();
Cache::registerSerializer($serializer);

$newSerializer = new SerializerImplementation();
Cache::unregisterSerializer(); // serializer is detached, at this point trying to save/get further values will result an error
Cache::registerSerializer($newSerializer);
~~~

When the serializer component fails to restore a value, default value will be displayed.

When the serializer component fails to normalize the value, the CacheAdapter will not even be pinged.

**! Important !**

Each method implying usage of cache key(s), given key(s) must respect pattern ([a-zA-Z0-9_.]) imposed by the PSR-16 or a InvalidArgumentException will be thrown.

### 6.1 Getting values

Cache component provides you multiple methods to get a cached value from a cache store.

Method **get()** which will simply fetch a value by its key or returns its default.

Method **getMultiple()** which will fetch from the store a set of keys or returns its default for each key missed.

Method **has()** which will simply check if a value corresponds to a key into the cache store.

~~~php
$adapter = new CacheAdapterImplementation();
$cache = new Cache($adapter);

// let's assume foo is stored with a bar value
$cache->get("foo", "default") // returns bar
$cache->get("bar", "default") // returns default

// let's assume foo is stored with a bar value
$cache->getMultiple(["foo", "bar"], "default"); // returns ["foo" => "bar", "bar" => "default"]

// let's assume foo is stored with a bar value
$cache->has("foo"); // true
$cache->has("bar"); // bar
~~~

### 6.2 Setting values

Setting values can be done in two different ways.

Methods **set()** will store a single value into the cache store.

Methods **setMultiple()** will store multiple values in one call.

Each methods allows you to set a time to live for your value : 
- **null** : will register your value with unlimited time to live no matter the default ttl setted into your cache
- **integer** : a time in seconds
- **DateInterval** : a DateInterval

~~~php
$adapter = new CacheAdapterImplementation();
$cache = new Cache($adapter);

$cache->set("foo", "bar"); // store bar value under key foo with default cache ttl. returns true if value stored with success
$cache->set("bar", "foo", null); // store foo value under bar key with unlimited ttl no matter the defaut ttl

$cache->setMultiple(["foo" => "bar", "bar" => "foo"], new DateInterval("PT20S")); 
// store bar and foo values under respectively foo and bar key with an expiration time of 20 seconds
// returns true if all values have been stored with success
~~~

**! Important !**

If you intend to store a non string value, **it MUST be compatible with your serializer !**

No verification are done whatsoever on the value setted into the CacheItem, it's up to you to check what you are storing !

### 6.3 Remove values

Cache provides you multiple ways to invalidate cached values from a cache store.

Method **delete()** which will simply remove a single value.

Method **deleteMultiple()** which will remove a set of cache keys.

Method **clear()** which clears all cached values.

~~~php
$adapter = new CacheAdapterImplementation();
$cache = new Cache($adapter);

// let's assume foo is stored
$cache->delete("foo"); // returns true
$cache->delete("bar"); // returns false

$cache->deleteMultiple(["foo", "bar"]); // return true on if all values have been cleared

$cache->clear(); // returns true if the cache has been cleared with success
~~~

## 7. Contributing

Found something **wrong** (nothing is perfect) ? Wanna talk or participate ? <br />
Issue the case or contact me at [curtis_barogla@outlook.fr](mailto:curtis_barogla@outlook.fr)

## 8. License

The Ness Cache component is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).