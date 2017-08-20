<?php

namespace Codefocus\ManagedCache\Tests;

use Codefocus\ManagedCache\Facades\ManagedCache;
use Event;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Cache;

class UntaggedTest extends TestCase
{
    private $data = [
        'value' => '1',
        'minutes' => 10,
        'key' => 'test-123',
        'tags' => [],
    ];

    public function setUp()
    {
        parent::setUp();

        $this->data = [
            'value' => '1',
            'minutes' => 10,
            'key' => 'test-123',
            'tags' => [],
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
        $result = $this->getEmptyResult();

        Event::listen(KeyWritten::class, function ($event) use (&$result) {
            foreach ($result as $key => $value) {
                $result[$key] = $event->$key;
            }
        });
        ManagedCache::put($this->data['key'], $this->data['value'], $this->data['minutes']);

        $this->assertEquals($this->data, $result);
    }

    /**
     * Test that data is read from cache.
     */
    public function testCacheIsRead()
    {
        $result = ManagedCache::get($this->data['key']);

        $this->assertEquals($this->data['value'], $result);
    }
}
