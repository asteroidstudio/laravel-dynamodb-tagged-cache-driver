<?php

namespace AsteroidStudio\LaravelDynamodbTaggedCacheDriver\Tests;

use Illuminate\Support\Carbon;
use AsteroidStudio\LaravelDynamodbTaggedCacheDriver\TaggedDynamodbStore;
use stdClass;

class StoreTest extends TestCase
{
    /*
    public function testItemsCanBeSetAndRetrieved()
    {
        $store = new TaggedDynamodbStore;
        $result = $store->put('foo', 'bar', 10);
        $this->assertTrue($result);
        $this->assertSame('bar', $store->get('foo'));
    }

    public function testMultipleItemsCanBeSetAndRetrieved()
    {
        $store = new TaggedDynamodbStore;
        $result = $store->put('foo', 'bar', 10);
        $resultMany = $store->putMany([
            'fizz' => 'buz',
            'quz' => 'baz',
        ], 10);
        $this->assertTrue($result);
        $this->assertTrue($resultMany);
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $store->many(['foo', 'fizz', 'quz', 'norf']));
    }

    public function testMoreThan25ItemsCanBeSetAndRetrieved()
    {
        $store = new TaggedDynamodbStore;
        $items = [];

        for ($i=0; $i < 60; $i++) { 
            $items['key' . $i] = 'value' . $i;
        }

        $resultMany = $store->putMany($items, 10);
        $this->assertTrue($resultMany);
        $this->assertEquals($items, $store->many(array_keys($items)));
    }

    public function testItemsCanExpire()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new TaggedDynamodbStore;

        $store->put('foo', 'bar', 10);
        Carbon::setTestNow(Carbon::now()->addSeconds(10)->addSecond());
        $result = $store->get('foo');

        $this->assertNull($result);
        Carbon::setTestNow(null);
    }

    public function testValuesCanBeIncremented()
    {
        $store = new TaggedDynamodbStore;
        $store->put('foo', 1, 10);
        $result = $store->increment('foo');
        $this->assertEquals(2, $result);
        $this->assertEquals(2, $store->get('foo'));
    }

    public function testValuesCanBeDecremented()
    {
        $store = new TaggedDynamodbStore;
        $store->put('foo', 1, 10);
        $result = $store->decrement('foo');
        $this->assertEquals(0, $result);
        $this->assertEquals(0, $store->get('foo'));
    }

    public function testItemsCanBeRemoved()
    {
        $store = new TaggedDynamodbStore;
        $store->put('foo', 'bar', 10);
        $this->assertTrue($store->forget('foo'));
        $this->assertNull($store->get('foo'));        
    }

    public function testCannotAcquireLockTwice()
    {
        $store = new TaggedDynamodbStore;
        $store->forget('foo');
        $lock = $store->lock('foo', 10, 'owner');
        $this->assertTrue($lock->acquire());
        $this->assertFalse($lock->acquire());
        // $lock->release();
    }

    public function testCanAcquireLockAgainAfterExpiry()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new TaggedDynamodbStore;
        $store->forget('foo');
        $lock = $store->lock('foo', 10, 'owner');
        $lock->acquire();
        Carbon::setTestNow(Carbon::now()->addSeconds(11));

        $this->assertTrue($lock->acquire());
        $lock->release();
    }

    public function testLockExpirationLowerBoundary()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new TaggedDynamodbStore;
        $store->forget('foo');
        $lock = $store->lock('foo', 10, 'owner');
        $lock->acquire();
        Carbon::setTestNow(Carbon::now()->addSeconds(10)->subMicrosecond());

        $this->assertFalse($lock->acquire());
        $lock->release();
    }

    public function testCanAcquireLockAfterRelease()
    {
        $store = new TaggedDynamodbStore;
        $store->forget('foo');
        $lock = $store->lock('foo', 10, 'owner');
        $lock->acquire();

        $this->assertTrue($lock->release());
        $this->assertTrue($lock->acquire());
        $lock->release();
    }

    public function testAnotherOwnerCannotReleaseLock()
    {
        $store = new TaggedDynamodbStore;
        $store->forget('foo');
        $owner = $store->lock('foo', 10, 'owner');
        $wannabeOwner = $store->lock('foo', 10, 'wannabeOwner');
        $owner->acquire();
        $this->assertFalse($wannabeOwner->release());
        $owner->release();
        $wannabeOwner->release();
    }

    public function testFlush()
    {
        $store = new TaggedDynamodbStore;
        $store->forever('foo', 'bar');
        $this->assertEquals($store->get('foo'), 'bar');
        $store->flush();
        $this->assertNull($store->get('foo'));
    }
    */
   

    public function testCacheCanBeSavedWithMultipleTags()
    {
        $store = new TaggedDynamodbStore;
        $tags = ['bop', 'zap'];
        $store->tags($tags)->put('foo', 'bar', 10);
        $this->assertSame('bar', $store->tags($tags)->get('foo'));
    }

    // public function testCacheCanBeSetWithDatetimeArgument()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $tags = ['bop', 'zap'];
    //     $duration = new DateTime;
    //     $duration->add(new DateInterval('PT10M'));
    //     $store->tags($tags)->put('foo', 'bar', $duration);
    //     $this->assertSame('bar', $store->tags($tags)->get('foo'));
    // }

    // public function testCacheSavedWithMultipleTagsCanBeFlushed()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $tags1 = ['bop', 'zap'];
    //     $store->tags($tags1)->put('foo', 'bar', 10);
    //     $tags2 = ['bam', 'pow'];
    //     $store->tags($tags2)->put('foo', 'bar', 10);
    //     $store->tags('zap')->flush();
    //     $this->assertNull($store->tags($tags1)->get('foo'));
    //     $this->assertSame('bar', $store->tags($tags2)->get('foo'));
    // }

    // public function testTagsWithStringArgument()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $store->tags('bop')->put('foo', 'bar', 10);
    //     $this->assertSame('bar', $store->tags('bop')->get('foo'));
    // }

    // public function testWithIncrement()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $taggableStore = $store->tags('bop');

    //     $taggableStore->put('foo', 5, 10);

    //     $value = $taggableStore->increment('foo');
    //     $this->assertSame(6, $value);

    //     $value = $taggableStore->increment('foo');
    //     $this->assertSame(7, $value);

    //     $value = $taggableStore->increment('foo', 3);
    //     $this->assertSame(10, $value);

    //     $value = $taggableStore->increment('foo', -2);
    //     $this->assertSame(8, $value);

    //     $value = $taggableStore->increment('x');
    //     $this->assertSame(1, $value);

    //     $value = $taggableStore->increment('y', 10);
    //     $this->assertSame(10, $value);
    // }

    // public function testWithDecrement()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $taggableStore = $store->tags('bop');

    //     $taggableStore->put('foo', 50, 10);

    //     $value = $taggableStore->decrement('foo');
    //     $this->assertSame(49, $value);

    //     $value = $taggableStore->decrement('foo');
    //     $this->assertSame(48, $value);

    //     $value = $taggableStore->decrement('foo', 3);
    //     $this->assertSame(45, $value);

    //     $value = $taggableStore->decrement('foo', -2);
    //     $this->assertSame(47, $value);

    //     $value = $taggableStore->decrement('x');
    //     $this->assertSame(-1, $value);

    //     $value = $taggableStore->decrement('y', 10);
    //     $this->assertSame(-10, $value);
    // }

    // public function testMany()
    // {
    //     $store = $this->getTestCacheStoreWithTagValues();

    //     $values = $store->tags(['fruit'])->many(['a', 'e', 'b', 'd', 'c']);
    //     $this->assertSame([
    //         'a' => 'apple',
    //         'e' => null,
    //         'b' => 'banana',
    //         'd' => null,
    //         'c' => 'orange',
    //     ], $values);
    // }

    // public function testManyWithDefaultValues()
    // {
    //     $store = $this->getTestCacheStoreWithTagValues();

    //     $values = $store->tags(['fruit'])->many([
    //         'a' => 147,
    //         'e' => 547,
    //         'b' => 'hello world!',
    //         'x' => 'hello world!',
    //         'd',
    //         'c',
    //     ]);
    //     $this->assertSame([
    //         'a' => 'apple',
    //         'e' => 547,
    //         'b' => 'banana',
    //         'x' => 'hello world!',
    //         'd' => null,
    //         'c' => 'orange',
    //     ], $values);
    // }

    // public function testGetMultiple()
    // {
    //     $store = $this->getTestCacheStoreWithTagValues();

    //     $values = $store->tags(['fruit'])->getMultiple(['a', 'e', 'b', 'd', 'c']);
    //     $this->assertSame([
    //         'a' => 'apple',
    //         'e' => null,
    //         'b' => 'banana',
    //         'd' => null,
    //         'c' => 'orange',
    //     ], $values);

    //     $values = $store->tags(['fruit', 'color'])->getMultiple(['a', 'e', 'b', 'd', 'c']);
    //     $this->assertSame([
    //         'a' => 'red',
    //         'e' => 'blue',
    //         'b' => null,
    //         'd' => 'yellow',
    //         'c' => null,
    //     ], $values);
    // }

    // public function testGetMultipleWithDefaultValue()
    // {
    //     $store = $this->getTestCacheStoreWithTagValues();

    //     $values = $store->tags(['fruit', 'color'])->getMultiple(['a', 'e', 'b', 'd', 'c'], 547);
    //     $this->assertSame([
    //         'a' => 'red',
    //         'e' => 'blue',
    //         'b' => 547,
    //         'd' => 'yellow',
    //         'c' => 547,
    //     ], $values);
    // }

    // public function testTagsWithIncrementCanBeFlushed()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $store->tags('bop')->increment('foo', 5);
    //     $this->assertEquals(5, $store->tags('bop')->get('foo'));
    //     $store->tags('bop')->flush();
    //     $this->assertNull($store->tags('bop')->get('foo'));
    // }

    // public function testTagsWithDecrementCanBeFlushed()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $store->tags('bop')->decrement('foo', 5);
    //     $this->assertEquals(-5, $store->tags('bop')->get('foo'));
    //     $store->tags('bop')->flush();
    //     $this->assertNull($store->tags('bop')->get('foo'));
    // }

    // public function testTagsCacheForever()
    // {
    //     $store = new TaggedDynamodbStore;
    //     $tags = ['bop', 'zap'];
    //     $store->tags($tags)->forever('foo', 'bar');
    //     $this->assertSame('bar', $store->tags($tags)->get('foo'));
    // }

    // private function getTestCacheStoreWithTagValues(): TaggedDynamodbStore
    // {
    //     $store = new TaggedDynamodbStore;

    //     $tags = ['fruit'];
    //     $store->tags($tags)->put('a', 'apple', 10);
    //     $store->tags($tags)->put('b', 'banana', 10);
    //     $store->tags($tags)->put('c', 'orange', 10);

    //     $tags = ['fruit', 'color'];
    //     $store->tags($tags)->putMany([
    //         'a' => 'red',
    //         'd' => 'yellow',
    //         'e' => 'blue',
    //     ], 10);

    //     $tags = ['sizes', 'shirt'];
    //     $store->tags($tags)->putMany([
    //         'a' => 'small',
    //         'b' => 'medium',
    //         'c' => 'large',
    //     ], 10);

    //     return $store;
    // }
}