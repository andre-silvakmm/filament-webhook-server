<?php

namespace Marjose123\FilamentWebhookServer;

use Andresilva\JsonDatamapping\JsonDataMapping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ApiResponseBuilder
{
    private Model $model;

    private ?string $message;

    private ?string $dataOption;

    private ?string $dataType;

    private ?string $event;

    private ?string $module;

    private ?array $customDataOption;

    public static function create(): ApiResponseBuilder
    {
        return (new static())
            ->setMessage(null);
    }

    public function generate()
    {
        $payload = match ($this->dataOption) {
            'summary' => [
                'id'         => $this->model->id ?? $this->model->uuid ?? null,
                'created_at' => $this->model->created_at ?? Carbon::now()->timezone(config('app.timezone')),
                'updated_at' => $this->model->updated_at ?? null,
            ],
            'all' => (object)$this->model, //(object)$this->model->attributesToArray(),
            // 'custom' => method_exists($this->model, 'toWebhookPayload')
            //     ? (object)$this->model->toWebhookPayload() : [],
            'custom' => $this->createCustomDataOption(),
            default => [],
        };

        if ($this->event === 'created') {
            $payload = (object)$this->model; //(object)$this->model->attributesToArray();
        }

        if ($this->event === 'sync') {
            $payload = (object)$this->model;
        }

        $apiResponse = [
            'event' => $this->event ?? null,
            'module' => $this->module,
            'triggered_at' => Carbon::now()->timezone(config('app.timezone')),
            'data' => $payload,
        ];

        if ($this->dataType === 'webhook') {
            return json_decode(json_encode($apiResponse), true);
        }

        return json_decode(json_encode($payload), true); //$apiReponse;
    }

    public function createCustomDataOption()
    {
        return JsonDataMapping::make($this->customDataOption, $this->model);
    }

    public function setModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function setModule($module): static
    {
        $this->module = $module;

        return $this;
    }

    public function setEvent($event): static
    {
        $this->event = $event;

        return $this;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function setDataOption(string $dataOption): static
    {
        $this->dataOption = $dataOption;

        return $this;
    }

    /**
     * Set the value of dataType
     *
     * @return  self
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;

        return $this;
    }

    /**
     * Set the value of customDataOpion
     *
     * @return  self
     */
    public function setCustomDataOption($customDataOption)
    {
        $this->customDataOption = $customDataOption;

        return $this;
    }
}
