<?php

namespace Marjose123\FilamentWebhookServer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Marjose123\FilamentWebhookServer\Traits\helper;
use Spatie\WebhookServer\WebhookCall;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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
            $send = true;

            if (sizeof($webhookClient->regra_envio) > 0) {
                $expressionLanguage = new ExpressionLanguage();

                foreach ($webhookClient->regra_envio as $regra) {
                    $res = $expressionLanguage->evaluate($regra['regra'], ['model' => $this->model]);

                    if ($send !== false) {
                        $send = $res;
                    }
                }
            }

            if ($send) {
                $payload = $this->payload($this->model, $this->event, $this->module, $webhookClient->data_option, $webhookClient->data_type, $webhookClient->custom_data_option);

                $responseBuilder = new ApiResponseBuilder();
                $responseBuilder->setModel($this->model);

                activity('Webhook')
                    ->causedBy($this->model)
                    ->performedOn($webhookClient)
                    ->withProperties($payload)
                    ->event('process')
                    ->log('Webhook process');

                $url = $webhookClient->url;

                $params = $webhookClient->url_params;

                if ($webhookClient->url_params !== null) {
                    $url = preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($params, $payload) {
                        $key = $matches[1]; // Chave encontrada dentro do {}
                        return $payload[$params[$key]] ?? $matches[0]; // Substitui pelo valor do payload
                    }, $url);
                }

                $webhook = WebhookCall::create()
                    ->url($url)
                    ->meta(['webhookClient' => $webhookClient->id, 'model' => $this->model->id])
                    ->doNotSign()
                    ->useHttpVerb($webhookClient->method)
                    ->verifySsl((bool) $webhookClient->verifySsl)
                    ->withHeaders($webhookClient->header)
                    ->payload($payload)
                    ->throwExceptionOnFailure();

                activity('Webhook Model Execution')
                    ->causedBy($this->model)
                    ->performedOn($webhookClient)
                    ->withProperties(['uuid' => $webhook->getUuid()])
                    ->event('integracao')
                    ->log('Webhook model process');

                if ($webhookClient->sync) {
                    $webhook->dispatchSync();
                } else {
                    $webhook->dispatch();
                }

                // dd($webhook);
            }
        }
    }
}
