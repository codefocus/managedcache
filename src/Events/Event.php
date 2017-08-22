<?php

namespace Codefocus\ManagedCache\Events;

class Event
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
}
