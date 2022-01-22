<?php

it('has dynamodb key', function () {
    expect(config('cache.stores.dynamodb.attributes.key', 'key'))->not()->toBeEmpty();    
});

it('has dynamodb sort_key', function () {
    expect(config('cache.stores.dynamodb.attributes.sort_key'))->not()->toBeEmpty();
});