<?php

namespace Marjose123\FilamentWebhookServer\Observers;

use Illuminate\Database\Eloquent\Model;
use Marjose123\FilamentWebhookServer\HookJobProcess;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Spatie\Activitylog\Models\Activity;
use Spatie\ModelInfo\ModelInfo;

class ModelObserver
{
    public function created(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class; //ucfirst(str_replace("App\Models\\", '', $modelInfo->class));
            /*
             * Search on the DB that want to receive webhook from this model
             */
            // $search = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['created'])->where('model', '=', $module)->get();
            // $syncable = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['created'])->where('name', '=', 'sync')->get();
            $webhook = $this->getWebhookServer(['created'], $module);
            $syncable = $this->getWebhookServer(['created'], 'sync');

            $hasSyncableTrait = $modelInfo->traits->first(function (string $value) {
                return $value === \App\Traits\SyncableModel::class;
            });

            $sync = true;

            if ($hasSyncableTrait !== null) {
                $sync = $model->shouldSync;
            }

            if ($sync) {
                if ($webhook) {
                    /*
                * Send to Job Process
                */
                    (new HookJobProcess($webhook, $model, 'created', $module))->send();
                }

                /*
                * Send to Job Process sync
                */
                if ($syncable !== null) {
                    (new HookJobProcess($syncable, $model, 'created', $module))->send();
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function updated(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class; //ucfirst(str_replace("App\Models\\", '', $modelInfo->class));
            /*
             * Search on the DB that want to receive webhook from this model
             */
            // $search = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['updated'])->where('model', '=', $module)->get();
            // $syncable = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['updated'])->where('name', '=', 'sync')->get();
            $webhook = $this->getWebhookServer(['updated'], $module);
            $syncable = $this->getWebhookServer(['updated'], 'sync');

            $sync = true;

            if (property_exists($model, 'shouldSync')) {
                $sync = $model->shouldSync;
            }

            // dispara evento se nao for update do campo deleted do evento delete
            if (sizeof(array_keys($model->getChanges())) === 1) {
                if (array_keys($model->getChanges())[0] === 'deleted_at_unix') {
                    return;
                }
            }

            if ($sync) {
                if ($webhook) {
                    /*
                    * Send to Job Process
                    */
                    (new HookJobProcess($webhook, $model, 'updated', $module))->send();
                }

                /*
                * Send to Job Process sync
                */
                if ($syncable !== null) {
                    (new HookJobProcess($syncable, $model, 'updated', $module))->send();
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function deleted(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class; //ucfirst(str_replace("App\Models\\", '', $modelInfo->class));
            /*
             * Search on the DB that want to receive webhook from this model
             */
            // $search = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['deleted'])->where('model', '=', $module)->get();
            // $syncable = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['deleted'])->where('name', '=', 'sync')->get();
            $webhook = $this->getWebhookServer(['deleted'], $module);
            $syncable = $this->getWebhookServer(['deleted'], 'sync');

            $hasSyncableTrait = $modelInfo->traits->first(function (string $value) {
                return $value === \App\Traits\SyncableModel::class;
            });

            $sync = true;

            if ($hasSyncableTrait !== null) {
                $sync = $model->shouldSync;
            }

            if ($sync) {
                /*
                * Send to Job Process
                */
                if ($webhook) {
                    (new HookJobProcess($webhook, $model, 'deleted', $module))->send();
                }

                /*
                * Send to Job Process sync
                */
                if ($syncable !== null) {
                    (new HookJobProcess($syncable, $model, 'deleted', $module))->send();
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function restored(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class; //ucfirst(str_replace("App\Models\\", '', $modelInfo->class));
            /*
            * Search on the DB that want to receive webhook from this model
            */
            // $search = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['restored'])->where('model', '=', $module)->get();
            // $syncable = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['restored'])->where('name', '=', 'sync')->get();
            $webhook = $this->getWebhookServer(['restored'], $module);
            $syncable = $this->getWebhookServer(['restored'], 'sync');

            $hasSyncableTrait = $modelInfo->traits->first(function (string $value) {
                return $value === \App\Traits\SyncableModel::class;
            });

            $sync = true;

            if ($hasSyncableTrait !== null) {
                $sync = $model->shouldSync;
            }

            if ($sync) {
                /*
                * Send to Job Process
                */
                if ($webhook) {
                    (new HookJobProcess($webhook, $model, 'restored', $module))->send();
                }

                if ($syncable !== null) {
                    /*
                    * Send to Job Process sync
                    */
                    (new HookJobProcess($syncable, $model, 'restored', $module))->send();
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function forceDeleted(Model $model)
    {
        try {
            $modelInfo = ModelInfo::forModel($model::class);
            $module = $modelInfo->class; //ucfirst(str_replace("App\Models\\", '', $modelInfo->class));
            /*
             * Search on the DB that want to receive webhook from this model
             */
            // $search = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['forceDeleted'])->where('model', '=', $module)->get();
            // $syncable = FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', ['forceDeleted'])->where('name', '=', 'sync')->get();
            $webhook = $this->getWebhookServer(['forceDeleted'], $module);
            $syncable = $this->getWebhookServer(['forceDeleted'], 'sync');

            $hasSyncableTrait = $modelInfo->traits->first(function (string $value) {
                return $value === \App\Traits\SyncableModel::class;
            });

            $sync = true;

            if ($hasSyncableTrait !== null) {
                $sync = $model->shouldSync;
            }

            if ($sync) {
                /*
                * Send to Job Process
                */
                if ($webhook) {
                    (new HookJobProcess($webhook, $model, 'forceDeleted', $module))->send();
                }

                /*
                * Send to Job Process sync
                */
                if ($syncable !== null) {
                    (new HookJobProcess($syncable, $model, 'forceDeleted', $module))->send();
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function getWebhookServer(array $events, $model)
    {
        return FilamentWebhookServer::query()->where('ativo', true)->whereJsonContains('events', $events)->where('model', '=', $model)->get();
    }
}
