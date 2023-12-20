<?php

namespace Marjose123\FilamentWebhookServer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Marjose123\FilamentWebhookServer\Traits\helper;
use Spatie\WebhookServer\WebhookCall;

class HookJobProcess
{
    use helper;

    private ?Collection $search;

    private ?Model $model;

    private ?string $event;

    private ?string $module;

    public function __construct(Collection $search, Model $model, $event, $module)
    {
        $this->model = $model;
        $this->search = $search;
        $this->event = $event;
        $this->module = $module;
    }

    public function send(): void
    {
        foreach ($this->search as $webhookClient) {
            $payload = $this->payload($this->model, $this->event, $this->module, $webhookClient->data_option, $webhookClient->data_type, $webhookClient->custom_data_option);

            $webhook = WebhookCall::create()
                ->url($webhookClient->url)
                ->meta(['webhookClient' => $webhookClient->id])
                ->doNotSign()
                ->useHttpVerb($webhookClient->method)
                ->verifySsl((bool) $webhookClient->verifySsl)
                ->withHeaders($webhookClient->header)
                ->payload($payload)
                ->throwExceptionOnFailure();

            if ($webhookClient->sync) {
                $webhook->dispatchSync();
            } else {
                $webhook->dispatch();
            }
        }
    }
}
