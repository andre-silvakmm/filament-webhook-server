<?php

namespace Marjose123\FilamentWebhookServer\Observers;

use Marjose123\FilamentWebhookServer\HookJobProcess;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Spatie\ModelInfo\ModelInfo;

class CustomEventObserver
{
    public function handle($model)
    {
        $modelInfo = ModelInfo::forModel($model::class);
        $module = $modelInfo->class;

        $webhookEvents = FilamentWebhookServer::whereJsonContains('custom_events', [((string) $model->id)])->get();
        (new HookJobProcess($webhookEvents, $model, $model->descricao, $module))->send();
    }
}
