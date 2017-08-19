<?php

namespace Codefocus\ManagedCache\Providers;

use Codefocus\ManagedCache\ManagedCache;
use Illuminate\Support\ServiceProvider;

class ManagedCacheProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    protected $managedcache;

    public function boot(ManagedCache $managedcache)
    {
        //  ManagedCache instance has been created.
        dump('ManagedCacheProvider boot');
        // dump($managedcache);

        $this->managedcache = $managedcache;
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        dump('ManagedCacheProvider register');

        $this->app->singleton('codefocus.managedcache', ManagedCache::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ManagedCache::class,
        ];
    }
}
