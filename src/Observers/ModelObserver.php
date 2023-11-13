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
            $search = FilamentWebhookServer::query()->whereJsonContains('events', ['created'])->where('model', '=', $module)->get();
            $syncable = FilamentWebhookServer::query()->whereJsonContains('events', ['created'])->where('name', '=', 'sync')->get();

            $hasSyncableTrait = $modelInfo->traits->first(function (string $value) {
                return $value === \App\Traits\SyncableModel::class;
            });

            $sync = true;

            if ($hasSyncableTrait !== null) {
                $sync = $model->shouldSync;
            }

            if ($sync) {
                if ($search) {
                    /*
                * Send to Job Process
                */
                    (new HookJobProcess($search, $model, 'created', $module))->send();
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
            $search = FilamentWebhookServer::query()->whereJsonContains('events', ['updated'])->where('model', '=', $module)->get();
            $syncable = FilamentWebhookServer::query()->whereJsonContains('events', ['updated'])->where('name', '=', 'sync')->get();

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
                if ($search) {
                    /*
                    * Send to Job Process
                    */
                    (new HookJobProcess($search, $model, 'updated', $module))->send();
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
            $search = FilamentWebhookServer::query()->whereJsonContains('events', ['deleted'])->where('model', '=', $module)->get();
            $syncable = FilamentWebhookServer::query()->whereJsonContains('events', ['deleted'])->where('name', '=', 'sync')->get();

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
                if ($search) {
                    (new HookJobProcess($search, $model, 'deleted', $module))->send();
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
            $search = FilamentWebhookServer::query()->whereJsonContains('events', ['restored'])->where('model', '=', $module)->get();
            $syncable = FilamentWebhookServer::query()->whereJsonContains('events', ['restored'])->where('name', '=', 'sync')->get();

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
                if ($search) {
                    (new HookJobProcess($search, $model, 'restored', $module))->send();
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
            $search = FilamentWebhookServer::query()->whereJsonContains('events', ['forceDeleted'])->where('model', '=', $module)->get();
            $syncable = FilamentWebhookServer::query()->whereJsonContains('events', ['forceDeleted'])->where('name', '=', 'sync')->get();

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
                if ($search) {
                    (new HookJobProcess($search, $model, 'forceDeleted', $module))->send();
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
}
