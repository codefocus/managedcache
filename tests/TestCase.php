<?php

namespace Codefocus\ManagedCache\Tests;

use Illuminate\Database\Eloquent\Factory as ModelFactory;
use Codefocus\ManagedCache\Tests\Models\User;
use Codefocus\ManagedCache\Providers\ManagedCacheProvider;
use Codefocus\ManagedCache\Tests\Mock\CacheServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Orchestra\Testbench\Traits\WithLaravelMigrations;

abstract class TestCase extends BaseTestCase
{
    use WithLaravelMigrations;

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

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('database.default', 'testbench');
    }


    public function setUp()
    {
        parent::setUp();

        // $this->loadLaravelMigrations(['--database' => 'testbench']);
        //
        // // migrate database
        // $this->artisan('migrate');

        $this->loadLaravelMigrations('testbench');

        $this->app->make(ModelFactory::class)->load(__DIR__ . '/database/factories');
        // $this->withFactories(__DIR__ . '/database/factories/ModelFactory.php');

        //  Seed data.
        $numUsers = 10;
        $idxUser = 0;
        for ($idxUser = 0; $idxUser < $numUsers; ++$idxUser) {
            factory(User::class)->create();
        }
    }
}
