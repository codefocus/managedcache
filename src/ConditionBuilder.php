<?php

namespace Codefocus\ManagedCache;

use Codefocus\ManagedCache\Events\Event;
use Illuminate\Database\Eloquent\Model;
use Iterator;

/**
 * ConditionBuilder.
 */
class ConditionBuilder implements Iterator
{
    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * Adds a Condition that tags a cache to get invalidated
     * when a new Model of the specified class is created.
     *
     * @param string $modelClassName model class name
     *
     * @return self
     */
    public function modelCreated(string $modelClassName): self
    {
        return $this->addEloquentEventCondition(
            Event::EVENT_ELOQUENT_CREATED,
            $modelClassName
        );
    }

    /**
     * Adds a Condition that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is updated.
     *
     * @param mixed $model model instance or class name
     * @param int|null $modelId (default: null) The Model id
     *
     * @return self
     */
    public function modelUpdated($model, ?int $modelId = null): self
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return $this->addEloquentEventCondition(
            Event::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * Adds a Condition that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is saved.
     *
     * @param mixed $model model instance or class name
     * @param int|null $modelId (default: null) The Model id
     *
     * @return self
     */
    public function modelSaved($model, ?int $modelId = null): self
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return $this->addEloquentEventCondition(
            Event::EVENT_ELOQUENT_SAVED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * Adds a Condition that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is deleted.
     *
     * @param mixed $model model instance or class name
     * @param int|null $modelId (default: null) The Model id
     *
     * @return self
     */
    public function modelDeleted($model, ?int $modelId = null): self
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return $this->addEloquentEventCondition(
            Event::EVENT_ELOQUENT_DELETED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * Adds a Condition that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is restored.
     *
     * @param mixed $model model instance or class name
     * @param int|null $modelId (default: null) The Model id
     *
     * @return self
     */
    public function modelRestored($model, ?int $modelId = null): self
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return $this->addEloquentEventCondition(
            Event::EVENT_ELOQUENT_RESTORED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * Add an Eloquent event Condition.
     *
     * @param string $eventName
     * @param string|null $modelClassName (default: null)
     * @param int|null $modelId (default: null)
     * @param string|null $relatedModelClassName (default: null)
     * @param int|null $relatedModelId (default: null)
     *
     * @return self
     */
    private function addEloquentEventCondition(
        string $eventName,
        string $modelClassName,
        ?int $modelId = null,
        ?string $relatedModelClassName = null,
        ?int $relatedModelId = null
    ): self {
        $this->conditions[] = new Condition(
            $eventName,
            $modelClassName,
            $modelId,
            $relatedModelClassName,
            $relatedModelId
        );

        return $this;
    }




    /**
     * Return the value of the current item in the conditions array.
     *
     * @return Condition|false
     */
    public function current()
    {
        return current($this->conditions);
    }

    /**
     * Return the key of the current item in the conditions array.
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->conditions);
    }

    /**
     * Move the conditions array pointer to the next item.
     */
    public function next(): void
    {
        next($this->conditions);
    }

    /**
     * Move the conditions array pointer to the first item.
     */
    public function rewind(): void
    {
        reset($this->conditions);
    }

    /**
     * Returns whether the current item in the conditions array is valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return (current($this->conditions) !== false);
    }
}
