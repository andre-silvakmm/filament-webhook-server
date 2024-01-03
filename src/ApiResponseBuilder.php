<?php

namespace Marjose123\FilamentWebhookServer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use stdClass;

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

    private function createCustomDataOption()
    {
        $obj = [];
        foreach ($this->customDataOption as $key => $value) {
            $this->checkKeyType($key, $value, $obj, $this->customDataOption, $this->model);
        }

        return $obj;
    }

    private function checkKeyType($key, $value, &$obj, $customDataOption, $model, $globalModel = false)
    {
        $varType = explode(':', $key);

        $valueKey = $customDataOption[$key];

        switch ($varType[1]) {
            case 'int':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                $obj[$varType[0]] = (int) $value;
                break;
            case 'float':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                $obj[$varType[0]] = (float) $value;
                break;
            case 'string':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                $obj[$varType[0]] = (string) $value;
                break;
            case 'bool':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                $obj[$varType[0]] = (bool) $value;
                break;
            case 'object':
                $obj[$varType[0]] = [];

                $datasource = $valueKey['datasource'];
                $mapping = $valueKey['mapping'];

                $data = data_get($globalModel ? $this->model : $model, $datasource, null);

                $o = [];
                foreach ($mapping as $keyMap => $mapItem) {
                    $this->checkKeyType($keyMap, $mapItem, $o, $mapping, $data, false);
                }

                $obj[$varType[0]] = $o;

                break;
            case 'array':
                $obj[$varType[0]] = [];

                $datasource = $valueKey['datasource'];
                $mapping = $valueKey['mapping'];

                $data = data_get($globalModel ? $this->model : $model, $datasource, null) ?? [];

                foreach ($data as $dataItem) {
                    $o = [];
                    foreach ($mapping as $keyMap => $mapItem) {
                        $this->checkKeyType($keyMap, $mapItem, $o, $mapping, $dataItem, false);
                    }

                    $obj[$varType[0]][] = $o;
                }

                break;
            case 'raw':
                $obj[$varType[0]] = $valueKey;
                break;
            case 'custom':
                $obj[$varType[0]] = [];

                $o = [];
                foreach ($valueKey as $key => $value) {
                    $this->checkKeyType($key, $value, $o, $valueKey, $this->model, true);
                }

                $obj[$varType[0]] = $o;

                break;
            case 'customArray':
                $obj[$varType[0]] = [];

                foreach ($valueKey as $value) {
                    $o = [];
                    foreach ($value as $keyMap => $mapItem) {
                        $this->checkKeyType($keyMap, $mapItem, $o, $value,  $this->model, true);
                    }

                    $obj[$varType[0]] = $o;
                }

                break;
            default:
                break;
        }

        return $obj;
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
