<?php

namespace Codefocus\ManagedCache\Tests;

use Codefocus\ManagedCache\Providers\ManagedCacheProvider;
use Codefocus\ManagedCache\Tests\Mock\CacheServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ManagedCacheProvider::class,
            // CacheServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            // 'Cache' => \Illuminate\Support\Facades\Cache::class
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.stores.testbench', [
            'driver' => 'memcached',
            'servers' => [
              [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 100,
              ],
            ],
        ]);
        $app['config']->set('cache.default', 'testbench');
    }
}
