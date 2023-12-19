<?php

namespace Marjose123\FilamentWebhookServer;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Marjose123\FilamentWebhookServer\HookJobProcess;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob as SpatieProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessWebhookSyncJob extends SpatieProcessWebhookJob
{

    public $queue = 'SYNC';

    public function __construct(
        public WebhookCall $webhookCall,
    ) {
    }

    public function handle()
    {
        $payload = $this->webhookCall->payload;

        $event = $payload['event'];

        $this->$event();
    }

    private function sync()
    {
        $this->updated();
    }

    private function retrieveToCreate()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        $validator = Validator::make($data, [
            'id' => 'required|int'
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->getMessageBag()->toArray()));
        }

        $instance = $model::withTrashed()->where('id', $data['id'])->first();

        if ($instance === null) {
            throw new \Exception($model . ' não localizado para id: ' . $data['id']);
        }

        $search = FilamentWebhookServer::query()->where('name', '=', 'sync')->get();

        (new HookJobProcess($search, $instance, 'created', $model))->send();
    }

    private function created()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        $instance = $model::withTrashed()->where('id', $data['id'])->first();

        if ($instance !== null) {
            $this->updated();
        } else {
            $instance = new $model();
            $instance->fill($data);
            $instance->shouldSync = false;
            $instance->save();
        }
    }

    private function updated()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        // update só execute se houver mais de 1 campo
        if (sizeof(array_keys($data)) > 1) {
            $instance = $model::withTrashed()->where('id', $data['id'])->first();

            if ($instance === null) {
                if ($payload['event'] === 'sync') {
                    $this->created();
                } else {
                    $instance = new $model();
                    $instance->id = $data['id'];
                    $search = FilamentWebhookServer::query()->where('name', '=', 'sync')->get();

                    (new HookJobProcess($search, $instance, 'retrieveToCreate', $model))->send();
                }
            } else {
                // remover atributos de update quando data do evento for menor q data de atualizacao do objeto
                if (!$triggeredDate->greaterThan($instance->updated_at)) {
                    $changesKeys = array_keys($instance->toWebhookPayload());

                    foreach ($changesKeys as $changeKey) {
                        unset($data[$changeKey]);
                    }
                }

                $instance->fill($data);
                $instance->shouldSync = false;
                $instance->save();
            }
        }
    }

    private function deleted()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        $instance = $model::find($data['id']);
        if ($instance !== null) {
            $instance->shouldSync = false;
            $instance->delete();
        }
    }

    private function restored()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        $instance = $model::withTrashed()->where('id', $data['id']);
        $instance->shouldSync = false;
        $instance->restore();
    }

    private function forceDeleted()
    {
        $payload = $this->webhookCall->payload;

        $model = $payload['module'];
        $data = $payload['data'];
        $triggeredDate = new Carbon($payload['triggered_at']);

        // $instance = $model::withTrashed()->where('id', $data['id']);
        // $instance->shouldSync = false;
        // $instance->forceDelete();
    }
}
