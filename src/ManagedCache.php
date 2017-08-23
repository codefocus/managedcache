<?php

namespace Codefocus\ManagedCache;

use BadFunctionCallException;
use Codefocus\ManagedCache\Events\Event;
use Codefocus\ManagedCache\Traits\HandlesEloquentEvents;
use Exception;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

/**
 * ManagedCache.
 *
 * @method DefinitionChain setForgetConditions(array $conditions)
 */
class ManagedCache
{
    use HandlesEloquentEvents;

    //  Cache keys.
    const TAG_MAP_CACHE_KEY = 'ManagedCache_TagMap';

    /**
     * @var Application
     */
    protected $application;

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
        //  Inject Application.
        $this->application = $application;
        //  Get the configured cache store.
        $this->store = $this->application['cache.store'];
        if ( ! ($this->store->getStore() instanceof MemcachedStore)) {
            throw new Exception('Memcached not configured. Cache store is "' . class_basename($this->store) . '"');
        }
        //  Register the event listeners.
        $this->registerEventListener($this->application['events']);
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
            $relatedModelId = $relatedModel->/* @scrutinizer ignore-call */getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            Event::EVENT_ELOQUENT_ATTACHED,
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
            $relatedModelId = $relatedModel->/* @scrutinizer ignore-call */getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            Event::EVENT_ELOQUENT_DETACHED,
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
            $relatedModelId = $relatedModel->/* @scrutinizer ignore-call */getKey();
        } else {
            $relatedModelClassName = $relatedModel;
        }

        return new Condition(
            Event::EVENT_ELOQUENT_UPDATED,
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
