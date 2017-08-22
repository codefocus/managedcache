<?php

namespace Codefocus\ManagedCache\Traits;

use Illuminate\Database\Eloquent\Model;

trait IdentifiesEloquentModels
{
    /**
     * Return whether the specified class name is an Eloquent Model.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isModel($value): bool
    {
        return is_object($value) && is_subclass_of($value, Model::class);
    }
}
