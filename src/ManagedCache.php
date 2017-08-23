<?php

namespace Codefocus\ManagedCache;

use BadFunctionCallException;
use Codefocus\ManagedCache\Events\Event;
use Codefocus\ManagedCache\Traits\HandlesEloquentEvents;
use Exception;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\Repository as CacheRepository;
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
