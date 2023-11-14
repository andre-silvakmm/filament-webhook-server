<?php

namespace Marjose123\FilamentWebhookServer\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use Tests\ReflectionHelper;

trait SyncableModel
{

    public $shouldSync = true;

    public function initializeSyncableModel()
    {
        $this->fillable[] = 'id';
    }

    public function syncCreated(): array
    {
        $properties = ReflectionHelper::getProperty($this, 'fillable');

        return $properties;
    }

    public function syncUpdated(): array
    {
        $properties = ReflectionHelper::getProperty($this, 'fillable');

        return $properties;
    }

    public function syncDeleted(): array
    {
        $properties = ReflectionHelper::getProperty($this, 'fillable');

        return [];
    }

    public function syncRestored(): array
    {
        $properties = ReflectionHelper::getProperty($this, 'fillable');

        return [];
    }

    public function syncForceDeleted(): array
    {
        $properties = ReflectionHelper::getProperty($this, 'fillable');

        return [];
    }

    public function toWebhookPayload(): array
    {
        $activity = Activity::where('subject_type', $this::class)->where('subject_id', $this->id)->latest('id')->first();

        $changes = [];

        if ($activity !== null) {
            $changes = $activity->getChangesAttribute()->toArray()['attributes'];
        }

        $changes['id'] = $this->id;

        unset($changes['updated_at']);
        unset($changes['created_at']);
        unset($changes['deleted_at']);
        unset($changes['deleted_at_unix']);

        return $changes;
    }
}
