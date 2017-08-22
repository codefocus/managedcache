<?php

namespace Codefocus\ManagedCache;

use Exception;
use Illuminate\Database\Eloquent\Model;

class Condition
{
    const CACHE_TAG_PREFIX = 'ManagedCache:';
    const CACHE_TAG_SEPARATOR = '-';
    const CACHE_TAG_ID_OPEN = '(';
    const CACHE_TAG_ID_CLOSE = ')';
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
     * @param string|null $modelName (default: null)
     * @param int|null $modelId (default: null)
     * @param string|null $relatedModelName (default: null)
     * @param int|null $relatedModelId (default: null)
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
        $modelTagPart = $this->getModelTagPart();
        $relationTagPart = $this->getRelationTagPart();
        if (null === $relationTagPart) {
            return
                self::CACHE_TAG_PREFIX .
                $this->eventName .
                self::CACHE_TAG_SEPARATOR .
                $modelTagPart .
                self::CACHE_TAG_SEPARATOR .
                $relationTagPart;
        }

        return
            self::CACHE_TAG_PREFIX .
            $this->eventName .
            self::CACHE_TAG_SEPARATOR .
            $modelTagPart;
    }

    /**
     * Get Relation Tag Part
     *
     * @return string
     */
    protected function getModelTagPart(): string
    {
        if (null === $this->modelId) {
            //  Any instance of this model.
            return $this->modelName;
        }

        //  Only the instance of this model with this id.
        return $this->modelName .
            self::CACHE_TAG_ID_OPEN .
            $this->modelId .
            self::CACHE_TAG_ID_CLOSE;
    }

    /**
     * Get Relation Tag Part
     *
     * @return string|null
     */
    protected function getRelationTagPart(): string|null
    {
        if (null === $this->relatedModelId) {
            if (null === $this->relatedModelName) {
                //  No relation specified.
                return null;
            }
            //  Any instance of this related model.
            return $this->relatedModelName;
        }

        //  Only the instance of this related model with this id.
        return $this->relatedModelName .
            self::CACHE_TAG_ID_OPEN .
            $this->relatedModelId .
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
