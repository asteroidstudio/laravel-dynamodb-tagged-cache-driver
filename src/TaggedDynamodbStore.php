<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Cache\TaggableStore;

class TaggedDynamodbStore extends TaggableStore implements LockProvider 
{
    use InteractsWithTime;

    /**
     * The DynamoDB client instance.
     *
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    protected $dynamo;

    /**
     * The table name.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the attribute that should hold the key.
     *
     * @var string
     */
    protected $keyAttribute;

    /**
     * The name of the attribute that should hold the sort key.
     *
     * @var string
     */
    protected $sortKeyAttribute;

    /**
     * The name of the attribute that should hold the value.
     *
     * @var string
     */
    protected $valueAttribute;

    /**
     * The name of the attribute that should hold the expiration timestamp.
     *
     * @var string
     */
    protected $expirationAttribute;

    protected $itemPrefix;

    public function __construct()
    {
        $this->table = config('cache.stores.dynamodb.table', 'cache');
        $this->keyAttribute = config('cache.stores.dynamodb.attributes.key', 'key');
        $this->sortKeyAttribute = config('cache.stores.dynamodb.attributes.sort_key', 'sort_key');
        $this->valueAttribute = config('cache.stores.dynamodb.attributes.value', 'value');
        $this->expirationAttribute = config('cache.stores.dynamodb.attributes.expiration', 'expires_at');        
        $this->itemPrefix = 'ITEM-';
    }
    
    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        $client = $this->getClient();
        $response = $client->getItem([
            'TableName' => $this->table,
            'ConsistentRead' => false,
            'Key' => [
                $this->keyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
                $this->sortKeyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
            ],
        ]);

        if (! isset($response['Item'])) {
            return;
        }

        if ($this->isExpired($response['Item'])) {
            return;
        }

        if (isset($response['Item'][$this->valueAttribute])) {
            return $this->unserialize(
                $response['Item'][$this->valueAttribute]['S'] ??
                $response['Item'][$this->valueAttribute]['N'] ??
                null
            );
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $prefixedKeys = array_map(function ($key) {
            return $this->itemPrefix.$key;
        }, $keys);

        $client = $this->getClient();
        $response = $client->batchGetItem([
            'RequestItems' => [
                $this->table => [
                    'ConsistentRead' => false,
                    'Keys' => collect($prefixedKeys)->map(function ($key) {
                        return [
                            $this->keyAttribute => [
                                'S' => $key,
                            ],
                            $this->sortKeyAttribute => [
                                'S' => $key,
                            ],
                        ];
                    })->all(),
                ],
            ],
        ]);

        $now = Carbon::now();

        return array_merge(collect(array_flip($keys))->map(function () {
            //
        })->all(), collect($response['Responses'][$this->table])->mapWithKeys(function ($response) use ($now) {
            if ($this->isExpired($response, $now)) {
                $value = null;
            } else {
                $value = $this->unserialize(
                    $response[$this->valueAttribute]['S'] ??
                    $response[$this->valueAttribute]['N'] ??
                    null
                );
            }

            return [Str::replaceFirst($this->itemPrefix, '', $response[$this->keyAttribute]['S']) => $value];
        })->all());
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        $client = $this->getClient();
        $client->putItem([
            'TableName' => $this->table,
            'Item' => [
                $this->keyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
                $this->sortKeyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
                $this->valueAttribute => [
                    $this->type($value) => $this->serialize($value),
                ],
                $this->expirationAttribute => [
                    'N' => (string) $this->toTimestamp($seconds),
                ],
            ],
        ]);

        return true;
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  array  $values
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        $expiration = $this->toTimestamp($seconds);
        collect($values)->chunk(25)->each(function($items) use ($expiration) {
            $client = $this->getClient();
            $client->batchWriteItem([
                'RequestItems' => [
                    $this->table => collect($items)->map(function ($value, $key) use ($expiration) {
                        return [
                            'PutRequest' => [
                                'Item' => [
                                    $this->keyAttribute => [
                                        'S' => $this->itemPrefix.$key,
                                    ],
                                    $this->sortKeyAttribute => [
                                        'S' => $this->itemPrefix.$key,
                                    ],
                                    $this->valueAttribute => [
                                        $this->type($value) => $this->serialize($value),
                                    ],
                                    $this->expirationAttribute => [
                                        'N' => (string) $expiration,
                                    ],
                                ],
                            ],
                        ];
                    })->values()->all(),
                ],
            ]);            
        });

        return true;
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {        
        try {
            $client = $this->getClient();
            $response = $client->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    $this->keyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                    $this->sortKeyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                ],
                'ConditionExpression' => 'attribute_exists(#key) AND #expires_at > :now',
                'UpdateExpression' => 'SET #value = #value + :amount',
                'ExpressionAttributeNames' => [
                    '#key' => $this->keyAttribute,
                    '#value' => $this->valueAttribute,
                    '#expires_at' => $this->expirationAttribute,
                ],
                'ExpressionAttributeValues' => [
                    ':now' => [
                        'N' => (string) Carbon::now()->getTimestamp(),
                    ],
                    ':amount' => [
                        'N' => (string) $value,
                    ],
                ],
                'ReturnValues' => 'UPDATED_NEW',
            ]);

            return (int) $response['Attributes'][$this->valueAttribute]['N'];
        } catch (DynamoDbException $e) {
            if (Str::contains($e->getMessage(), 'ConditionalCheckFailed')) {
                return false;
            }

            throw $e;
        }    
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        try {
            $client = $this->getClient();
            $response = $this->dynamo->updateItem([
                'TableName' => $this->table,
                'Key' => [
                    $this->keyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                    $this->sortKeyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                ],
                'ConditionExpression' => 'attribute_exists(#key) AND #expires_at > :now',
                'UpdateExpression' => 'SET #value = #value - :amount',
                'ExpressionAttributeNames' => [
                    '#key' => $this->keyAttribute,
                    '#value' => $this->valueAttribute,
                    '#expires_at' => $this->expirationAttribute,
                ],
                'ExpressionAttributeValues' => [
                    ':now' => [
                        'N' => (string) Carbon::now()->getTimestamp(),
                    ],
                    ':amount' => [
                        'N' => (string) $value,
                    ],
                ],
                'ReturnValues' => 'UPDATED_NEW',
            ]);

            return (int) $response['Attributes'][$this->valueAttribute]['N'];
        } catch (DynamoDbException $e) {
            if (Str::contains($e->getMessage(), 'ConditionalCheckFailed')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->put($key, $value, Carbon::now()->addYears(5)->getTimestamp());
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        $client = $this->getClient();
        $client->deleteItem([
            'TableName' => $this->table,
            'Key' => [
                $this->keyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
                $this->sortKeyAttribute => [
                    'S' => $this->itemPrefix.$key,
                ],
            ],
        ]);

        return true;
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        throw new RuntimeException('DynamoDb does not support flushing an entire table. Please create a new table.');
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->itemPrefix;
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        try {
            $client = $this->getClient();
            $client->putItem([
                'TableName' => $this->table,
                'Item' => [
                    $this->keyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                    $this->sortKeyAttribute => [
                        'S' => $this->itemPrefix.$key,
                    ],
                    $this->valueAttribute => [
                        $this->type($value) => $this->serialize($value),
                    ],
                    $this->expirationAttribute => [
                        'N' => (string) $this->toTimestamp($seconds),
                    ],
                ],
                'ConditionExpression' => 'attribute_not_exists(#key) OR attribute_not_exists(#sortkey) OR #expires_at < :now',
                'ExpressionAttributeNames' => [
                    '#key' => $this->keyAttribute,
                    '#sortkey' => $this->sortKeyAttribute,
                    '#expires_at' => $this->expirationAttribute,
                ],
                'ExpressionAttributeValues' => [
                    ':now' => [
                        'N' => (string) Carbon::now()->getTimestamp(),
                    ],
                ],
            ]);

            return true;
        } catch (DynamoDbException $e) {
            if (Str::contains($e->getMessage(), 'ConditionalCheckFailed')) {
                return false;
            }

            throw $e;
        }
    }
    
    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null) 
    {
        return new DynamoDbLock($this, $name, $seconds, $owner);
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {

    }

    protected function getClient()
    {
        if ($this->dynamo) {
            return $this->dynamo;
        }

        $config = config('cache.stores.dynamodb');        
        $dynamoConfig = [
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'] ?? null,
        ];

        if (isset($config['key']) && isset($config['secret'])) {
            $dynamoConfig['credentials'] = Arr::only(
                $config, ['key', 'secret', 'token']
            );
        }

        $this->dynamo = new DynamoDbClient($dynamoConfig);        

        return $this->dynamo;
    }

    /**
     * Serialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function serialize($value)
    {
        return is_numeric($value) ? (string) $value : serialize($value);
    }

    /**
     * Unserialize the value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return unserialize($value);
    }

    /**
     * Get the DynamoDB type for the given value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function type($value)
    {
        return is_numeric($value) ? 'N' : 'S';
    }

    /**
     * Get the UNIX timestamp for the given number of seconds.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function toTimestamp($seconds)
    {
        return $seconds > 0 ? $this->availableAt($seconds) : Carbon::now()->getTimestamp();
    }

    /**
     * Determine if the given item is expired.
     *
     * @param  array  $item
     * @param  \DateTimeInterface|null  $expiration
     * @return bool
     */
    protected function isExpired(array $item, $expiration = null)
    {
        $expiration = $expiration ?: Carbon::now();

        return isset($item[$this->expirationAttribute]) && $expiration->getTimestamp() >= $item[$this->expirationAttribute]['N'];
    }
}

// class TaggedDynamodbStore extends TaggableStore implements LockProvider
// {
//     use InteractsWithTime, RetrievesMultipleKeys;

//     /**
//      * The array of stored values.
//      *
//      * @var array
//      */
//     protected $storage = [];

//     /**
//      * The array of locks.
//      *
//      * @var array
//      */
//     public $locks = [];

//     /**
//      * Indicates if values are serialized within the store.
//      *
//      * @var bool
//      */
//     protected $serializesValues;

//     /**
//      * Create a new Array store.
//      *
//      * @param  bool  $serializesValues
//      * @return void
//      */
//     public function __construct($serializesValues = false)
//     {
//         $this->serializesValues = $serializesValues;
//     }

//     /**
//      * Retrieve an item from the cache by key.
//      *
//      * @param  string|array  $key
//      * @return mixed
//      */
//     public function get($key)
//     {
//         if (! isset($this->storage[$key])) {
//             return;
//         }

//         $item = $this->storage[$key];

//         $expiresAt = $item['expiresAt'] ?? 0;

//         if ($expiresAt !== 0 && $this->currentTime() > $expiresAt) {
//             $this->forget($key);

//             return;
//         }

//         return $this->serializesValues ? unserialize($item['value']) : $item['value'];
//     }

//     /**
//      * Store an item in the cache for a given number of seconds.
//      *
//      * @param  string  $key
//      * @param  mixed  $value
//      * @param  int  $seconds
//      * @return bool
//      */
//     public function put($key, $value, $seconds)
//     {
//         $this->storage[$key] = [
//             'value' => $this->serializesValues ? serialize($value) : $value,
//             'expiresAt' => $this->calculateExpiration($seconds),
//         ];

//         return true;
//     }

//     /**
//      * Increment the value of an item in the cache.
//      *
//      * @param  string  $key
//      * @param  mixed  $value
//      * @return int
//      */
//     public function increment($key, $value = 1)
//     {
//         if (! is_null($existing = $this->get($key))) {
//             return tap(((int) $existing) + $value, function ($incremented) use ($key) {
//                 $value = $this->serializesValues ? serialize($incremented) : $incremented;

//                 $this->storage[$key]['value'] = $value;
//             });
//         }

//         $this->forever($key, $value);

//         return $value;
//     }

//     /**
//      * Decrement the value of an item in the cache.
//      *
//      * @param  string  $key
//      * @param  mixed  $value
//      * @return int
//      */
//     public function decrement($key, $value = 1)
//     {
//         return $this->increment($key, $value * -1);
//     }

//     /**
//      * Store an item in the cache indefinitely.
//      *
//      * @param  string  $key
//      * @param  mixed  $value
//      * @return bool
//      */
//     public function forever($key, $value)
//     {
//         return $this->put($key, $value, 0);
//     }

//     /**
//      * Remove an item from the cache.
//      *
//      * @param  string  $key
//      * @return bool
//      */
//     public function forget($key)
//     {
//         if (array_key_exists($key, $this->storage)) {
//             unset($this->storage[$key]);

//             return true;
//         }

//         return false;
//     }

//     /**
//      * Remove all items from the cache.
//      *
//      * @return bool
//      */
//     public function flush()
//     {
//         $this->storage = [];

//         return true;
//     }

//     /**
//      * Get the cache key prefix.
//      *
//      * @return string
//      */
//     public function getPrefix()
//     {
//         return '';
//     }

//     /**
//      * Get the expiration time of the key.
//      *
//      * @param  int  $seconds
//      * @return int
//      */
//     protected function calculateExpiration($seconds)
//     {
//         return $this->toTimestamp($seconds);
//     }

//     /**
//      * Get the UNIX timestamp for the given number of seconds.
//      *
//      * @param  int  $seconds
//      * @return int
//      */
//     protected function toTimestamp($seconds)
//     {
//         return $seconds > 0 ? $this->availableAt($seconds) : 0;
//     }

//     /**
//      * Get a lock instance.
//      *
//      * @param  string  $name
//      * @param  int  $seconds
//      * @param  string|null  $owner
//      * @return \Illuminate\Contracts\Cache\Lock
//      */
//     public function lock($name, $seconds = 0, $owner = null)
//     {
//         return new ArrayLock($this, $name, $seconds, $owner);
//     }

//     /**
//      * Restore a lock instance using the owner identifier.
//      *
//      * @param  string  $name
//      * @param  string  $owner
//      * @return \Illuminate\Contracts\Cache\Lock
//      */
//     public function restoreLock($name, $owner)
//     {
//         return $this->lock($name, 0, $owner);
//     }
// }
