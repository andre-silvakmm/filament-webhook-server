<?php

namespace Marjose123\FilamentWebhookServer\Listeners;

use Illuminate\Support\Facades\Log;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServerHistory;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;

class WebhookFailedListener
{
    public function __construct()
    {
    }

    public function handle($event)
    {
        if (config('filament-webhook-server.webhook.keep_history')) {
            try {
                $webhookClientHistory = new FilamentWebhookServerHistory();
                $webhookClientHistory->webhook_client = $event->meta['webhookClient'];
                $webhookClientHistory->uuid = $event->uuid;
                $webhookClientHistory->status_code = $event->response !== null ? $event->response->getStatusCode() : null;
                $webhookClientHistory->errorMessage = $event->response !== null ? $event->response->getReasonPhrase() : null;
                $webhookClientHistory->errorType = $event->errorType;
                $webhookClientHistory->attempt = $event->attempt;

                $res = $webhookClientHistory->save();
            } catch (\Exception $error) {
                Log::info(print_r($error, true));
            }
        }
    }
}
