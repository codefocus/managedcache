<?php

namespace Codefocus\ManagedCache;

use Exception;
use Illuminate\Database\Eloquent\Model;

class Condition
{
    const CACHE_TAG_PREFIX = 'ManagedCache:';
    const CACHE_TAG_SEPARATOR = '-';
    const CACHE_TAG_ID_OPEN = '(';
    const CACHE_TAG_ID_CLOSE = '(';
    const CACHE_TAG_RELATED = '->';

    protected $eventName = null;

    protected $modelName = null;

    protected $modelId = null;

    protected $relatedModelName = null;

    protected $relatedModelId = null;

    /**
     * Constructor.
     *
     * @param string $eventName
     * @param ?string $modelName (default: null)
     * @param ?integer $modelId (default: null)
     * @param ?string $relatedModelName (default: null)
     * @param ?integer $relatedModelId (default: null)
     */
    public function __construct(
        string $eventName,
        ?string $modelName = null,
        ?int $modelId = null,
        ?string $relatedModelName = null,
        ?int $relatedModelId = null
    ) {
        $this->eventName = $eventName;
        $this->modelName = $modelName;
        $this->modelId = $modelId;
        $this->relatedModelName = $relatedModelName;
        $this->relatedModelId = $relatedModelId;

        if (null !== $this->modelName) {
            $this->assertIsModel($this->modelName);
        }
    }

    /**
     * Return a string representation of this Condition.
     *
     * @return string
     */
    public function __toString(): string
    {
        if (null === $this->modelName) {
            //  All events of this type
            //  trigger a flush.
            return
                self::CACHE_TAG_PREFIX .
                $this->eventName;
        }
        if (null === $this->modelId) {
            if (null !== $this->relatedModelName and null !== $this->relatedModelId) {
                //  All events of this type
                //  linked to a model of this type
                //  and related to the specified related model
                //  trigger a flush.
                return
                    self::CACHE_TAG_PREFIX .
                    $this->eventName .
                    self::CACHE_TAG_SEPARATOR .
                    $this->modelName .
                    self::CACHE_TAG_RELATED .
                    $this->relatedModelName .
                    self::CACHE_TAG_ID_OPEN .
                    $this->relatedModelId .
                    self::CACHE_TAG_ID_CLOSE;
            }
            //  All events of this type
            //  linked to a model of this type
            //  trigger a flush.
            return
                self::CACHE_TAG_PREFIX .
                $this->eventName .
                self::CACHE_TAG_SEPARATOR .
                $this->modelName;
        }
        //  All events of this type
        //  linked to the specific model specified
        //  trigger a flush.
        return
            self::CACHE_TAG_PREFIX .
            $this->eventName .
            self::CACHE_TAG_SEPARATOR .
            $this->modelName .
            self::CACHE_TAG_ID_OPEN .
            $this->modelId .
            self::CACHE_TAG_ID_CLOSE;
    }

    /**
     * Throws an Exception if the specified class name is not an Eloquent Model.
     *
     * @param string $modelClassName
     */
    protected function assertIsModel(string $modelClassName): void
    {
        if ( ! is_subclass_of($modelClassName, Model::class)) {
            throw new Exception($modelClassName . ' is not an Eloquent Model class name.');
        }
    }
}
