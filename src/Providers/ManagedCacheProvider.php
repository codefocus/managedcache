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

    /**
     * Constructor.
     *
     * @param ManagedCache $managedcache
     */
    public function boot(ManagedCache $managedcache)
    {
        $this->managedcache = $managedcache;
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->app->singleton('codefocus.managedcache', function() {
            return $this->managedcache;
        });
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
