<?php

namespace Marjose123\FilamentWebhookServer\Observers;

use Illuminate\Database\Eloquent\Model;
use Marjose123\FilamentWebhookServer\HookJobProcess;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Spatie\ModelInfo\ModelInfo;

class ModelObserver
{
    public function created(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class;
            /*
             * Search on the DB that want to receive webhook from this model
             */
            $webhook = $this->getWebhookServer(['created'], $module);

            if ($webhook) {
                /*
                * Send to Job Process
                */
                (new HookJobProcess($webhook, $model, 'created', $module))->send();
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public function updated(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class;
            /*
             * Search on the DB that want to receive webhook from this model
             */
            $webhook = $this->getWebhookServer(['updated'], $module);

            if ($webhook) {
                /*
                * Send to Job Process
                */
                (new HookJobProcess($webhook, $model, 'updated', $module))->send();
            }
        } catch (\Exception $e) {
        }
    }

    public function deleted(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class;
            /*
             * Search on the DB that want to receive webhook from this model
             */
            $webhook = $this->getWebhookServer(['deleted'], $module);

            /*
            * Send to Job Process
            */
            if ($webhook) {
                (new HookJobProcess($webhook, $model, 'deleted', $module))->send();
            }
        } catch (\Exception $e) {
        }
    }

    public function restored(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class;
            /*
            * Search on the DB that want to receive webhook from this model
            */
            $webhook = $this->getWebhookServer(['restored'], $module);

            /*
            * Send to Job Process
            */
            if ($webhook) {
                (new HookJobProcess($webhook, $model, 'restored', $module))->send();
            }
        } catch (\Exception $e) {
        }
    }

    public function forceDeleted(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class;
            /*
             * Search on the DB that want to receive webhook from this model
             */
            $webhook = $this->getWebhookServer(['forceDeleted'], $module);

            /*
            * Send to Job Process
            */
            if ($webhook) {
                (new HookJobProcess($webhook, $model, 'forceDeleted', $module))->send();
            }
        } catch (\Exception $e) {
        }
    }

    private function getWebhookServer(array $events, $model)
    {
        return FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', $events)->where('model', '=', $model)->get();
    }
}
