<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver;

use Illuminate\Support\Facades\Cache;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AsteroidStudio\LaravelDynamodbTaggedCacheDriver\Commands\LaravelDynamodbTaggedCacheDriverCommand;

class LaravelDynamodbTaggedCacheDriverServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-dynamodb-tagged-cache-driver');            
    }

    public function packageRegistered()
    {
        $this->app->booting(function () {
             Cache::extend('taggeddynamodb', function ($app) {
                 return Cache::repository(new MongoStore);
             });
         });
    }
}
