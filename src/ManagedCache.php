<?php

namespace Codefocus\ManagedCache;

use BadFunctionCallException;
use Exception;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\Repository as CacheRepository;
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

    const EVENT_ELOQUENT_ATTACHED = 'eloquent.attached';
    const EVENT_ELOQUENT_DETACHED = 'eloquent.detached';

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var StoreContract
     */
    protected $store;

    private $isDebugModeEnabled = false;

    /**
     * Constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->store = app('cache.store');
        if ( ! ($this->store->getStore() instanceof MemcachedStore)) {
            throw new Exception('Memcached not configured. Cache store is "' . class_basename($this->store) . '"');
        }
        $this->dispatcher = $dispatcher;
        $this->registerEventListener();
    }

    public function enableDebugMode()
    {
        $this->isDebugModeEnabled = true;

        return $this;
    }

    public function isDebugModeEnabled()
    {
        return $this->isDebugModeEnabled;
    }

    /**
     * Returns the Cache store instance.
     *
     * @return CacheRepository
     */
    public function getStore(): CacheRepository
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

        if ( ! preg_match($regex, $eventKey, $matches)) {
            return;
        }

        $eventName = $matches[1];
        $modelName = $matches[2];

        //  Ensure $payload is always an array.
        if ( ! is_array($payload)) {
            $payload = [$payload];
        }

        //  Flush items that are tagged with this event (and no model).
        //	i.e. items that should be flushed when this event happens to ANY instance of the model.
        $cacheTags = [];

        //  Create a tag to flush stores tagged with:
        //  -   this Eloquent event, AND
        //  -   this Model class
        $cacheTags[] = new Condition($eventName, $modelName);

        foreach ($payload as $model) {
            if ( ! is_object($model) || ! is_subclass_of($model, Model::class)) {
                continue;
            }
            $modelId = $model->getKey();
            if ( ! empty($modelId)) {
                //  Create a tag to flush stores tagged with:
                //  -   this Eloquent event, AND
                //  -   this Model instance
                $cacheTags[] = new Condition($eventName, $modelName, $modelId);
            }

            //  @TODO:  Related models.

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
                    $relatedEventName = 'attach.' . $eventName;
                }
                $cacheTags[] = new Condition($relatedEventName, $modelName, $modelId);
                $cacheTags[] = Condition::makeTag($relatedEventName, $modelName, null, $relatedModelName, $relatedModelId);
            }
        }

        //	Flush all stores with these tags
        $this->forgetWhen($cacheTags)->flush();
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
     * extractModelKeys function.
     *
     * @param array $attributeNames
     *
     * @return array
     */
    protected function extractModelKeys(array $attributeNames)
    {
        $modelKeys = [];
        foreach ($attributeNames as $attributeName => $value) {
            if (preg_match('/([^_]+)_id/', $attributeName, $matches)) {
                //	This field is a key
                $modelKeys[strtolower($matches[1])] = $value;
            }
        }
        //	Ensure our model keys are always in the same order.
        ksort($modelKeys);

        return $modelKeys;
    }

    //	function extractModelKeys

    /**
     * Returns a Condition instance that tags a cache to get invalidated
     * when a new Model of the specified class is created.
     *
     * @param string $modelClassName model class name
     *
     * @return Condition
     */
    public function created(string $modelClassName): Condition
    {
        return new Condition(
            self::EVENT_ELOQUENT_CREATED,
            $modelClassName
        );
    }

    /**
     * Returns a Condition instance that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is updated.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId The Model id
     *
     * @return Condition
     */
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

    /**
     * Returns a Condition instance that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is saved.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId The Model id
     *
     * @return Condition
     */
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

    /**
     * Returns a Condition instance that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is deleted.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId The Model id
     *
     * @return Condition
     */
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

    /**
     * Returns a Condition instance that tags a cache to get invalidated
     * when the specified Model instance, or any Model of the specified class
     * is restored.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId The Model id
     *
     * @return Condition
     */
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
     * Returns a Condition instance that tags a cache to get invalidated when
     * a related Model of the specified class is attached.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId the Model id, if $model is a class name
     * @param mixed $relatedModel the related Model instance or class name
     * @param ?int $relatedModelId the related Model id
     *
     * @return Condition
     */
    public function relationAttached($model, ?int $modelId, $relatedModel = null, ?int $relatedModelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if (is_object($relatedModel) && is_subclass_of($relatedModel, Model::class)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel->getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            self::EVENT_ELOQUENT_ATTACHED,
            $modelClassName,
            $modelId,
            $relatedModelClassName,
            $relatedModelId
        );
    }

    /**
     * Returns a Condition instance that tags a cache to get invalidated when
     * a related Model of the specified class is detached.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId the Model id, if $model is a class name
     * @param mixed $relatedModel the related Model instance or class name
     * @param ?int $relatedModelId the related Model id
     *
     * @return Condition
     */
    public function relationDetached($model, ?int $modelId, $relatedModel = null, ?int $relatedModelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if (is_object($relatedModel) && is_subclass_of($relatedModel, Model::class)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel->getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            self::EVENT_ELOQUENT_DETACHED,
            $modelClassName,
            $modelId,
            $relatedModelClassName,
            $relatedModelId
        );
    }

    /**
     * Returns a Condition instance that tags a cache to get invalidated when
     * a related Model of the specified class is updated.
     *
     * @param mixed $model model instance or class name
     * @param ?int $modelId the Model id, if $model is a class name
     * @param mixed $relatedModel the related Model instance or class name
     * @param ?int $relatedModelId the related Model id
     *
     * @return Condition
     */
    public function relationUpdated($model, ?int $modelId, $relatedModel = null, ?int $relatedModelId): Condition
    {
        if (is_object($model) && is_subclass_of($model, Model::class)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if (is_object($relatedModel) && is_subclass_of($relatedModel, Model::class)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel->getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            self::EVENT_ELOQUENT_UPDATED,
            $modelClassName,
            $modelId,
            $relatedModelClassName,
            $relatedModelId
        );
    }

    /**
     * Route function calls to a new DefinitionChain.
     *
     * @param string $name
     * @param array $arguments
     *
     * @throws BadFunctionCallException
     */
    public function __call(string $name, array $arguments)
    {
        $definitionChain = new DefinitionChain($this);
        if ( ! method_exists($definitionChain, $name)) {
            throw new BadFunctionCallException('Function ' . $name . ' does not exist.');
        }

        return call_user_func_array([$definitionChain, $name], $arguments);
    }
}
