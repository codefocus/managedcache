<?php

namespace Codefocus\ManagedCache\Facades;

use Illuminate\Support\Facades\Facade;

class ManagedCache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'codefocus.managedcache';
    }
}
