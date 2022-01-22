<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use AsteroidStudio\LaravelDynamodbTaggedCacheDriver\LaravelDynamodbTaggedCacheDriverServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;
    
    protected function getPackageProviders($app)
    {
        return [
            LaravelDynamodbTaggedCacheDriverServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app->useEnvironmentPath(__DIR__.'/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
        parent::getEnvironmentSetUp($app);
        config()->set('database.default', 'testing');
        config()->set('cache.stores.dynamodb.attributes.sort_key', 'SK');
        config()->set('cache.stores.dynamodb.attributes.key', 'PK');
    }
}
