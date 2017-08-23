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
        $this->conditions[] = new Condition(
            Event::EVENT_ELOQUENT_CREATED,
            $modelClassName
        );

        return $this;
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
        $this->conditions[] = new Condition(
            Event::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );

        return $this;
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
        $this->confitions[] = new Condition(
            Event::EVENT_ELOQUENT_SAVED,
            $modelClassName,
            $modelId
        );

        return $this;
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
        $this->conditions[] = new Condition(
            Event::EVENT_ELOQUENT_DELETED,
            $modelClassName,
            $modelId
        );

        return $this;
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
        $this->conditions[] = new Condition(
            Event::EVENT_ELOQUENT_RESTORED,
            $modelClassName,
            $modelId
        );

        return $this;
    }

    public function current()
    {
        return current($this->conditions);
    }

    public function key()
    {
        return key($this->conditions);
    }

    public function next(): void
    {
        next($this->conditions);
    }

    public function rewind(): void
    {
        reset($this->conditions);
    }

    public function valid(): bool
    {
        return (current($this->conditions) !== false);
    }
}
