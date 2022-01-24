<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use AsteroidStudio\LaravelDynamodbTaggedCacheDriver\LaravelDynamodbTaggedCacheDriverServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class TestCase extends Orchestra
{
    protected $loadEnvironmentVariables = true;

    protected function resolveApplicationConfiguration($app)
    {
        $app->useEnvironmentPath(__DIR__.'/..');
        parent::resolveApplicationConfiguration($app);
    }

    
    /**
    * Setup the test environment.
    */
    protected function setUp(): void
    {
        $app = $this->resolveApplication();
        
        parent::setUp();

        // Code after application created.
    }
    
    protected function getPackageProviders($app)
    {
        return [
            LaravelDynamodbTaggedCacheDriverServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        
        // $app->bootstrapWith([LoadEnvironmentVariables::class]);
        parent::getEnvironmentSetUp($app);
        // dd(config('cache.stores.dynamodb'));
        config()->set('database.default', 'testing');
        config()->set('cache.stores.dynamodb.attributes.key', 'key');
        config()->set('cache.stores.dynamodb.attributes.sort_key', 'sort_key');
    }
}
