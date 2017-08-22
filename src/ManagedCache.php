<?php

namespace Codefocus\ManagedCache;

use BadFunctionCallException;
use Exception;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Store as StoreContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

/**
 * ManagedCache
 *
 * @method DefinitionChain forgetWhen(array $conditions)
 */
class ManagedCache
{
    //  Eloquent events.
    const EVENT_ELOQUENT_CREATED = 'eloquent.created';
    const EVENT_ELOQUENT_UPDATED = 'eloquent.updated';
    const EVENT_ELOQUENT_SAVED = 'eloquent.saved';
    const EVENT_ELOQUENT_DELETED = 'eloquent.deleted';
    const EVENT_ELOQUENT_RESTORED = 'eloquent.restored';
    //  Relation events.
    const EVENT_ELOQUENT_ATTACHED = 'eloquent.attached';
    const EVENT_ELOQUENT_DETACHED = 'eloquent.detached';
    //  Cache keys.
    const TAG_MAP_CACHE_KEY = 'ManagedCache_TagMap';

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var CacheRepository
     */
    protected $store;

    private $isDebugModeEnabled = false;

    /**
     * Maps cache keys to their tags.
     *
     * @var array
     */
    protected $tagMap = [];

    /**
     * Constructor.
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->store = $application['cache.store'];
        if ( ! ($this->store->getStore() instanceof MemcachedStore)) {
            throw new Exception('Memcached not configured. Cache store is "' . class_basename($this->store) . '"');
        }
        $this->dispatcher = $application['events'];
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

    public function getTagMap(): array
    {
        if (empty($this->tagMap)) {
            $this->tagMap = $this->store->get(self::TAG_MAP_CACHE_KEY, []);
            if ( ! is_array($this->tagMap)) {
                $this->tagMap = [];
            }
        }

        return $this->tagMap;
    }

    public function getTagsForKey(string $key): array
    {
        $tagMap = $this->getTagMap();
        if ( ! isset($tagMap[$key])) {
            return [];
        }

        return $tagMap[$key];
    }

    public function setTagsForKey(string $key, array $tags): void
    {
        $this->getTagMap();
        $this->tagMap[$key] = $tags;
        $this->store->forever(self::TAG_MAP_CACHE_KEY, $this->tagMap);
    }

    public function deleteTagsForKey(string $key)
    {
        $this->getTagMap();
        if (isset($this->tagMap[$key])) {
            unset($this->tagMap[$key]);
            $this->store->forever(self::TAG_MAP_CACHE_KEY, $this->tagMap);
        }
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
        //  Extract the basic event name and the model name from the event key.
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
            if ( ! $this->isModel($model)) {
                continue;
            }
            $modelId = $model->getKey();
            if ( ! empty($modelId)) {
                //  Create a tag to flush stores tagged with:
                //  -   this Eloquent event, AND
                //  -   this Model instance
                $cacheTags[] = new Condition($eventName, $modelName, $modelId);
            }
            //	Create tags for related models.
            foreach ($this->extractModelKeys($model->getAttributes()) as $relatedModelName => $relatedModelId) {
                //	Flush cached items that are tagged through a relation
                //	with this model.
                if ('delete' === $eventName) {
                    $relatedEventName = 'detach';
                } else {
                    $relatedEventName = 'attach';
                }
                $cacheTags[] = new Condition($relatedEventName, $modelName, $modelId, $relatedModelName, $relatedModelId);
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
     * @param int|null $modelId (default: null) The Model id
     *
     * @return Condition
     */
    public function updated($model, ?int $modelId = null): Condition
    {
        if ($this->isModel($model)) {
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
     * @param int|null $modelId (default: null) The Model id
     *
     * @return Condition
     */
    public function saved($model, ?int $modelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_SAVED,
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
     * @param int|null $modelId (default: null) The Model id
     *
     * @return Condition
     */
    public function deleted($model, ?int $modelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_DELETED,
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
     * @param int|null $modelId (default: null) The Model id
     *
     * @return Condition
     */
    public function restored($model, ?int $modelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }

        return new Condition(
            self::EVENT_ELOQUENT_RESTORED,
            $modelClassName,
            $modelId
        );
    }

    /**
     * Returns a Condition instance that tags a cache to get invalidated when
     * a related Model of the specified class is attached.
     *
     * @param mixed $model model instance or class name
     * @param int|null $modelId (default: null) the Model id, if $model is a class name
     * @param mixed|null $relatedModel (default: null) the related Model instance or class name
     * @param int|null $relatedModelId (default: null) the related Model id
     *
     * @return Condition
     */
    public function relationAttached($model, ?int $modelId = null, $relatedModel = null, ?int $relatedModelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if ($this->isModel($relatedModel)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel/** @scrutinizer ignore-call */->getKey();
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
     * @param int|null $modelId (default: null) the Model id, if $model is a class name
     * @param mixed|null $relatedModel (default: null) the related Model instance or class name
     * @param int|null $relatedModelId (default: null) the related Model id
     *
     * @return Condition
     */
    public function relationDetached($model, ?int $modelId = null, $relatedModel = null, ?int $relatedModelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if ($this->isModel($relatedModel)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel/** @scrutinizer ignore-call */->getKey();
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
     * @param int|null $modelId (default: null) the Model id, if $model is a class name
     * @param mixed|null $relatedModel (default: null) the related Model instance or class name
     * @param int|null $relatedModelId (default: null) the related Model id
     *
     * @return Condition
     */
    public function relationUpdated($model, ?int $modelId = null, $relatedModel = null, ?int $relatedModelId = null): Condition
    {
        if ($this->isModel($model)) {
            $modelClassName = get_class($model);
            $modelId = $model->getKey();
        } else {
            $modelClassName = $model;
        }
        if ($this->isModel($relatedModel)) {
            $relatedModelClassName = get_class($relatedModel);
            $relatedModelId = $relatedModel/** @scrutinizer ignore-call */->getKey();
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
     * Return whether the specified class name is an Eloquent Model.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function isModel($value): bool
    {
        return (is_object($value) && is_subclass_of($value, Model::class));
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
