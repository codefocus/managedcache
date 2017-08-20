<?php

namespace Codefocus\ManagedCache;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Store as StoreContract;

class DefinitionChain implements StoreContract
{
    protected $managedCache = null;

    protected $store = null;

    protected $conditions = [];

    /**
     * Constructor.
     *
     * @param ManagedCache $managedCache
     */
    public function __construct(ManagedCache $managedCache)
    {
        $this->managedCache = $managedCache;
        $this->store = $managedCache->getStore();
    }

    public function forgetWhen(array $conditions): self
    {
        $this->conditions = $conditions;

        // debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

        return $this;
    }

    public function addCondition(Condition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function addConditions(array $conditions): self
    {
        $this->conditions += $conditions;

        return $this;
    }

    public function getConditions(): array
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
        $tags = [];
        foreach ($this->conditions as $condition) {
            $tags[] = (string) $condition;
        }

        return $tags;
    }

    /**
     * Return the cache store, after applying our conditions to it, as tags.
     *
     * @return TaggedCache
     */
    public function getTaggedStore(): TaggedCache
    {
        return $this->store->tags($this->getConditionTags());
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return $this->getTaggedStore()->get($key);
    }

    /**
     * @inheritdoc
     */
    public function many(array $keys)
    {
        return $this->getTaggedStore()->many($keys);
    }

    /**
     * @inheritdoc
     */
    public function put($key, $value, $minutes)
    {
        return $this->getTaggedStore()->put($key, $value, $minutes);
    }

    /**
     * @inheritdoc
     */
    public function putMany(array $values, $minutes)
    {
        return $this->getTaggedStore()->putMany($values, $minutes);
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $value = 1)
    {
        return $this->getTaggedStore()->increment($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $value = 1)
    {
        return $this->getTaggedStore()->decrement($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function forever($key, $value)
    {
        return $this->getTaggedStore()->forever($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function forget($key)
    {
        return $this->getTaggedStore()->forget($key);
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        return $this->getTaggedStore()->flush();
    }

    /**
     * @inheritdoc
     */
    public function getPrefix()
    {
        return $this->getTaggedStore()->getPrefix();
    }
}
