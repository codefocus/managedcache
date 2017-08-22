<?php

namespace Codefocus\ManagedCache\Tests;

use Codefocus\ManagedCache\Facades\ManagedCache;
use Codefocus\ManagedCache\Tests\Models\User;
// use Codefocus\ManagedCache\ManagedCache;
use Event;
use Faker\Generator;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;

class TaggedTest extends TestCase
{
    private $data = [];

    public function setUp()
    {
        parent::setUp();

        $this->data = [
            'value' => 'test', //User::all()->toArray(),
            'minutes' => 10,
            'key' => 'test-users-all',
            'tags' => [
                'ManagedCache:eloquent.created-Codefocus\ManagedCache\Tests\Models\User',
                'ManagedCache:eloquent.saved-Codefocus\ManagedCache\Tests\Models\User',
                'ManagedCache:eloquent.deleted-Codefocus\ManagedCache\Tests\Models\User',
                'ManagedCache:eloquent.restored-Codefocus\ManagedCache\Tests\Models\User',
            ],
        ];
    }

    private function getEmptyResult()
    {
        return [
            'value' => null,
            'minutes' => null,
            'key' => null,
            'tags' => null,
        ];
    }

    /**
     * Test that data is written to cache.
     */
    public function testCacheIsWritten()
    {
        // $users = User::all();
        //
        // foreach($users as $user) {
        //     dump($user->toArray());
        // }

        // $user = new User();
        // Generator::

        $result = $this->getEmptyResult();

        Event::listen(KeyWritten::class, function ($event) use (&$result) {
            foreach ($result as $key => $value) {
                $result[$key] = $event->$key;
            }
        });
        ManagedCache::forgetWhen([
            ManagedCache::created(User::class),
            ManagedCache::saved(User::class),
            ManagedCache::deleted(User::class),
            ManagedCache::restored(User::class),
        ])
        ->put($this->data['key'], $this->data['value'], $this->data['minutes']);

        dump(1);

        $this->assertEquals($this->data, $result);
    }

    /**
     * Test that data is read from cache, without repeating the tags.
     */
    public function testCacheIsReadWithoutTags()
    {
        //  Write data to cache.
        ManagedCache::forgetWhen([
            ManagedCache::created(User::class),
            ManagedCache::saved(User::class),
            ManagedCache::deleted(User::class),
            ManagedCache::restored(User::class),
        ])
        ->put($this->data['key'], $this->data['value'], $this->data['minutes']);

        //  Retrieve value from cache, without specifying the tags.
        $value = ManagedCache::get($this->data['key']);

        $this->assertEquals($this->data['value'], $value);
    }

    /**
     * Test that data is read from cache, using a callback.
     */
    public function testExistingCacheIsReadWithCallback()
    {
        //  Write data to cache.
        ManagedCache::forgetWhen([
            ManagedCache::created(User::class),
            ManagedCache::saved(User::class),
            ManagedCache::deleted(User::class),
            ManagedCache::restored(User::class),
        ])
        ->put($this->data['key'], $this->data['value'], $this->data['minutes']);

        //  Retrieve value from cache, without specifying the tags.
        $value = ManagedCache::get($this->data['key'], function () {
            return 'incorrect';
        });

        $this->assertEquals($this->data['value'], $value);
    }

    /**
     * Test that the callback is called when the cache misses.
     */
    public function testMissingCacheIsReadWithCallback()
    {
        //  Attempt to retrieve missing value from cache.
        $value = ManagedCache::get(random_bytes(32), function () {
            return 'missing';
        });

        $this->assertEquals('missing', $value);
    }

    /**
     * Test that the cache is cleared when a new User is created.
     */
    public function testCacheEventIsTriggered()
    {
        //  Write data to cache.
        ManagedCache::forgetWhen([
            ManagedCache::created(User::class),
            ManagedCache::saved(User::class),
            ManagedCache::deleted(User::class),
            ManagedCache::restored(User::class),
        ])
        ->put($this->data['key'], $this->data['value'], $this->data['minutes']);

        //  Retrieve value from cache, without specifying the tags.
        $value = ManagedCache::get($this->data['key']);
        $this->assertEquals($this->data['value'], $value);

        //  Creating a new User should clear the cache.
        factory(User::class)->create();

        $value = ManagedCache::get($this->data['key']);
        $this->assertEquals(null, $value);
    }
}
