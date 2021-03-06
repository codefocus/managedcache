<?php

namespace Codefocus\ManagedCache;

use Closure;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store as StoreContract;

class DefinitionChain implements StoreContract
{
    protected $managedCache = null;

    protected $store = null;

    /**
     * @var ConditionBuilder
     */
    protected $conditions = null;

    protected $conditionTags = null;

    /**
     * Constructor.
     *
     * @param ManagedCache $managedCache
     */
    public function __construct(ManagedCache $managedCache)
    {
        $this->managedCache = $managedCache;
        $this->store = $managedCache->getStore();
        $this->conditions = new ConditionBuilder();
    }

    /**
     * Sets the array of Conditions that trigger the cache key to get flushed.
     *
     * @param Closure $closure
     *
     * @return self
     */
    public function setForgetConditions(Closure $closure): self
    {
        $this->conditions = new ConditionBuilder();

        return $this->addForgetConditions($closure);
    }

    /**
     * Adds one or more Conditions that triggers the cache key to get flushed.
     *
     * @param Closure $closure
     *
     * @return self
     */
    public function addForgetConditions(Closure $closure): self
    {
        $this->conditionTags = null;
        $closure($this->conditions);

        return $this;
    }

    /**
     * Returns the ConditionBuilder.
     *
     * @return ConditionBuilder
     */
    public function getConditions(): ConditionBuilder
    {
        return $this->conditions;
    }

    /**
     * Return an array of cache tags generated from our Conditions.
     *
     * @return array
     */
    public function getConditionTags(): array
    {
        if (empty($this->conditionTags)) {
            $tags = [];
            foreach ($this->conditions as $condition) {
                $tags[] = (string) $condition;
            }
            //  @TODO:  Remove this.
            //          Potentially replace with a call to ManagedCache::log()
            if ($this->managedCache->isDebugModeEnabled()) {
                dump($tags);
            }
            $this->conditionTags = $tags;
        }

        return $this->conditionTags;
    }

    /**
     * Return the cache store, after applying our conditions to it, as tags.
     *
     * @param array|null $tags (default: null)
     *
     * @return TaggedCache
     */
    public function getTaggedStore(?array $tags = null): TaggedCache
    {
        if ($tags !== null) {
            return $this->store->tags($tags);
        }

        return $this->store->tags($this->getConditionTags());
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        $value = $this->getTaggedStore($this->managedCache->getTagsForKey($key))->get($key);
        // If we could not find the cache value, we will get the default value
        // for this cache key. This default may be a callback.
        if (is_null($value)) {
            $value = value($default);
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function many(array $keys)
    {
        //  @TODO:  $this->managedCache->getTagsForKeys plural
        return $this->getTaggedStore()->many($keys);
    }

    /**
     * @inheritdoc
     */
    public function put($key, $value, $minutes)
    {
        //  Store the cache tags for this key,
        //  so that we can GET it without specifying the tags.
        $this->managedCache->setTagsForKey($key, $this->getConditionTags());
        $this->getTaggedStore()->put($key, $value, $minutes);
    }

    /**
     * @inheritdoc
     */
    public function putMany(array $values, $minutes)
    {
        //  @TODO:Store tags for keys plural.
        $this->getTaggedStore()->putMany($values, $minutes);
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $value = 1)
    {
        $this->getTaggedStore()->increment($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $value = 1)
    {
        $this->getTaggedStore()->decrement($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function forever($key, $value)
    {
        $this->getTaggedStore()->forever($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function forget($key)
    {
        $this->managedCache->deleteTagsForKey($key);
        $this->getTaggedStore()->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->getTaggedStore()->flush();
    }

    /**
     * @inheritdoc
     */
    public function getPrefix(): string
    {
        return (string) $this->getTaggedStore()->getPrefix();
    }
}
