<?php

namespace Codefocus\ManagedCache\Traits;

use Codefocus\ManagedCache\Condition;
use Codefocus\ManagedCache\Events\Event;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;

trait HandlesEloquentEvents
{
    use IdentifiesEloquentModels;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Register event listeners.
     *
     * @param Dispatcher $dispatcher
     */
    protected function registerEventListener(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
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
        $payload = (is_array($payload)) ? $payload : [$payload];
        //  Create a tag to flush stores tagged with:
        //  -   this Eloquent event, AND
        //  -   this Model class
        $cacheTags = [
            new Condition($eventName, $modelName),
        ];
        foreach ($payload as $model) {
            if ( ! $this->isModel($model)) {
                continue;
            }
            $cacheTags += $this->getModelEventTags($model, $eventName);
        }
        //	Flush all stores with these tags
        $this->/* @scrutinizer ignore-call */forgetWhen($cacheTags)->flush();
    }

    /**
     * Returns an array of tags based on a Model Event.
     *
     * @param Model $model
     * @param string $eventName
     *
     * @return array
     */
    private function getModelEventTags(Model $model, string $eventName): array
    {
        $modelId = $model->getKey();
        if (empty($modelId) || ! is_numeric($modelId)) {
            return [];
        }
        $modelId = (int) $modelId;
        $modelName = get_class($model);
        //  Create a tag to flush stores tagged with:
        //  -   this Eloquent event, AND
        //  -   this Model instance
        $cacheTags = [
            new Condition($eventName, $modelName, $modelId),
        ];
        //	Create tags for related models.
        foreach ($this->extractModelKeys($model) as $relatedModelName => $relatedModelId) {
            //	Flush cached items that are tagged through a relation
            //	with this model.
            $cacheTags[] = new Condition(
                (Event::EVENT_ELOQUENT_DELETED === $eventName) ? Event::EVENT_ELOQUENT_DETACHED : Event::EVENT_ELOQUENT_ATTACHED,
                $modelName,
                $modelId,
                $relatedModelName,
                $relatedModelId
            );
        }

        return $cacheTags;
    }

    /**
     * Get the observable event names.
     *
     * @return array
     */
    private function getObservableEvents(): array
    {
        return [
            Event::EVENT_ELOQUENT_CREATED,
            Event::EVENT_ELOQUENT_UPDATED,
            Event::EVENT_ELOQUENT_SAVED,
            Event::EVENT_ELOQUENT_DELETED,
            Event::EVENT_ELOQUENT_RESTORED,
            //  @TODO:  Verify that these are emitted by Laravel too.
            Event::EVENT_ELOQUENT_ATTACHED,
            Event::EVENT_ELOQUENT_DETACHED,
        ];
    }

    /**
     * Extract attributes that act as foreign keys.
     *
     * @param Model $model An Eloquent Model instance
     *
     * @return array
     */
    private function extractModelKeys(Model $model): array
    {
        $modelKeys = [];
        foreach ($model->getAttributes() as $attributeName => $value) {
            if (preg_match('/([^_]+)_id/', $attributeName, $matches)) {
                //	This field is a key
                $modelKeys[strtolower($matches[1])] = $value;
            }
        }
        //	Ensure our model keys are always in the same order.
        ksort($modelKeys);

        return $modelKeys;
    }
}
