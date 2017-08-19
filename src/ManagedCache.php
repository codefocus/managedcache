<?php

namespace Codefocus\ManagedCache;

use BadFunctionCallException;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Contracts\Cache\Store as StoreContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

class ManagedCache
{
    const EVENT_ELOQUENT_CREATED = 'eloquent.created';
    const EVENT_ELOQUENT_UPDATED = 'eloquent.updated';
    const EVENT_ELOQUENT_SAVED = 'eloquent.saved';
    const EVENT_ELOQUENT_DELETED = 'eloquent.deleted';
    const EVENT_ELOQUENT_RESTORED = 'eloquent.restored';
    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var StoreContract
     */
    protected $store;

    /**
     * Constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->store = app('cache')->store()->getStore();
        if ( ! ($this->store instanceof MemcachedStore)) {
            throw new Exception('Memcached not configured. Cache store is "' . class_basename($this->store) . '"');
        }
        $this->dispatcher = $dispatcher;
        $this->registerEventListener();
    }

    /**
     * Returns the Cache store instance.
     *
     * @return StoreContract
     */
    public function getStore(): StoreContract
    {
        return $this->store;
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListener(): void
    {
        //  Register Eloquent event listeners.
        foreach ($this->getObservableEvents() as $eventKey) {
            $this->dispatcher->listen($eventKey . ':*', [$this, 'handleEloquentEvent']);
        }
        // $this->dispatcher->listen('*', [$this, 'handleOtherEvent']);
    }

    /**
     * Handle an Eloquent event.
     *
     * @param string $eventKey
     * @param mixed $payload
     */
    public function handleEloquentEvent($eventKey, $payload): void
    {
        $regex = '/^(' . implode('|', $this->getObservableEvents()) . '): ([a-zA-Z0-9\\\\]+)$/';

        if (preg_match($regex, $eventKey, $matches)) {
            $eventName = $matches[1];
            $modelName = $matches[2];

            dump($eventName);
            dump($modelName);
            // dump($matches);
        }

        // dump('handleEvent: ' . $a);
        // dump($a);
        // dump(get_class($b));
    }

    /**
     * Get the observable event names.
     *
     * @return array
     */
    protected function getObservableEvents(): array
    {
        return [
            static::EVENT_ELOQUENT_CREATED,
            static::EVENT_ELOQUENT_UPDATED,
            static::EVENT_ELOQUENT_SAVED,
            static::EVENT_ELOQUENT_DELETED,
            static::EVENT_ELOQUENT_RESTORED,
        ];
    }

    /**
     * Flush all cached items tagged with this event.
     * Called by the ManagedCacheObserver.
     *
     * @param string $eventName
     * @param Model $model	An instance of a Model
     */
    public function handleModelEvent(string $eventName, Model $model): void
    {
        //	Simplify model name, to use in a cache tag.
        $modelName = $this->simpleModelName($model);

        //  Flush items that are tagged with this event (and no model).
        //	i.e. items that should be flushed when this event happens to ANY instance of the model.
        $cacheTags = [];
        $cacheTags[] = $this->createConditionTag($eventName, $modelName);
        if ($model instanceof \App\Model) {
            if ( ! empty($model->id)) {
                //  Flush items that are tagged with this event and this specific model.
                $cacheTags[] = $this->createConditionTag($eventName, $modelName, $model->id);
            }

            //	Flush cache for related models.
            //		E.g.
            //			-	A ballot has user_id = 30
            //			-	Flush cache tagged "ManagedCache:forget:attach-ballot-user=30"
            $modelKeys = $this->extractModelKeys($model->getAttributes());
            foreach ($modelKeys as $relatedModelName => $relatedModelId) {
                //	Flush cached items that are tagged through a relation
                //	with this model.
                if ('delete' === $eventName) {
                    $relatedEventName = 'detach';
                } else {
                    $relatedEventName = 'attach';
                }
                $cacheTags[] = $this->createConditionTag(
                    $relatedEventName,
                    $modelName,
                    null,
                    $relatedModelName,
                    $relatedModelId
                );
            }
        }
        //	Flush all items with these tags
        Cache::tags($cacheTags)->flush();
    }

    public function created(string $modelClassName): Condition
    {
        return new Condition(
            self::EVENT_ELOQUENT_CREATED,
            $modelClassName
        );
    }

    public function updated($model, ?int $modelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );
    }

    public function saved($model, ?int $modelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );
    }

    public function deleted($model, ?int $modelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );
    }

    public function restored($model, ?int $modelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * [__call description].
     *
     * @param string $name
     * @param array $arguments
     *
     * @throws BadFunctionCallException
     *
     * @return DefinitionChain
     */
    public function __call(string $name, array $arguments): DefinitionChain
    {
        $definitionChain = new DefinitionChain($this);
        if ( ! method_exists($definitionChain, $name)) {
            throw new BadFunctionCallException();
        }

        return call_user_func_array([$definitionChain, $name], $arguments);
    }
}
