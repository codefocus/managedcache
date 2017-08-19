<?php

namespace Codefocus\ManagedCache\Observers;

/**
 * ManagedCacheObserver class.
 *
 * Hook into events on Models that implement ManagedCacheTrait.
 * Available events: creating, created, updating, updated, saving, saved, deleting, deleted, restoring, restored
 */
class ManagedCacheObserver
{
    public function saved($model)
    {
        dump('Model saved');
        // //    A new Model that implements ManagedCacheTrait was created.
        // $model->publicCache()->handleModelEvent('save', $model);

        return true;
    }

    public function deleted($model)
    {
        dump('Model deleted');

        //    A Model that implements ManagedCacheTrait was deleted.
        $model->publicCache()->handleModelEvent('delete', $model);

        return true;
    }
}
