<?php

namespace Marjose123\FilamentWebhookServer\Observers;

use Marjose123\FilamentWebhookServer\HookJobProcess;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Spatie\ModelInfo\ModelInfo;

class CustomEventObserver
{
    public function handle($model, $evento)
    {
        $modelInfo = ModelInfo::forModel($model::class);
        $module = $modelInfo->class;

        $webhookEvents = FilamentWebhookServer::where('ativo', true)->whereJsonContains('custom_events', [((string) $evento->id)])->get();
        (new HookJobProcess($webhookEvents, $model, $model->descricao, $module))->send();
    }
}
