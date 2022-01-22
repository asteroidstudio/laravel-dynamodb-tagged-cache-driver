<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AsteroidStudio\LaravelDynamodbTaggedCacheDriver\LaravelDynamodbTaggedCacheDriver
 */
class LaravelDynamodbTaggedCacheDriver extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-dynamodb-tagged-cache-driver';
    }
}
