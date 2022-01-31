<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver;

use Illuminate\Cache\TagSet;
use Illuminate\Cache\TaggedCache as ParentTaggedCache;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Cache\RetrievesMultipleKeys;

class TaggedCache extends ParentTaggedCache
{
    protected $tagKeyPrefix = 'TAG-';
    protected $tagSortKeyPrefix = 'TAG-';
    
    /**
     * Get a fully qualified key for a tagged item.
     *
     * @param  string  $key
     * @return string
     */
    public function taggedItemKey($key)
    {
        return $key;
    }   

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        if ($ttl === null) {
            return $this->forever($key, $value);
        }

        $this->pushKeys($this->tags->getNames(), $key);

        return $this->store->put($key, $value, $ttl);
    }

    protected function pushKeys($names, $key)
    {
        $keyPrefix = $this->store->getKeyPrefix();
        $sortKeyPrefix = $this->store->getSortKeyPrefix();
        
        foreach ($names as $name) {
            $this->store->setKeyPrefix($this->tagKeyPrefix);
            $this->store->setSortKeyPrefix($this->tagSortKeyPrefix);
            $this->store->forever($name, '');
            $this->store->setSortKeyPrefix($sortKeyPrefix);
            $this->store->putRelation($name, $key);
        }

        $this->store->setKeyPrefix($keyPrefix);
        $this->store->setSortKeyPrefix($sortKeyPrefix);
    }

    
}
