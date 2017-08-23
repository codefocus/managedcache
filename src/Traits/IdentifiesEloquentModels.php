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

    /**
     * Returns an array containing the Model class name, and the model id.
     * The id is null if $model is a string.
     *
     * @param Model|string $model
     *
     * @return array
     */
    protected function getModelClassNameAndId($model): array
    {
        if ($this->isModel($model)) {
            return [
                get_class($model),
                $model->getKey(),
            ];
        }

        return [
            $model,
            null,
        ];
    }
}
