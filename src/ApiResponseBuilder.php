<?php

namespace Marjose123\FilamentWebhookServer;

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
        $obj = [];
        foreach ($this->customDataOption as $key => $value) {
            $this->checkKeyType($key, $value, $obj, $this->customDataOption, $this->model);
        }

        return $obj;
    }

    public function checkKeyType($key, $value, &$obj, $customDataOption, $model, $globalModel = false)
    {
        $varType = explode(':', $key);

        if (sizeof($varType) === 1) {
            $varType[1] = 'string';
        }

        $valueKey = $customDataOption[$key];

        switch ($varType[1]) {
            case 'int':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                if (gettype($value) === 'array') {
                    $val = 0;
                    foreach ($value as $key => $v) {
                        $val += $v;
                    }

                    $value = $val;
                }

                $obj[$varType[0]] = (int) $value;
                break;
            case 'float':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                if (gettype($value) === 'array') {
                    $val = 0.0;
                    foreach ($value as $key => $v) {
                        $val += $v;
                    }

                    $value = $val;
                }

                $obj[$varType[0]] = (float) $value;
                break;
            case 'string':
                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                if (gettype($value) === 'array') {
                    $val = '';
                    foreach ($value as $key => $v) {
                        $val .= $v;

                        if (($key + 1) < sizeof($value)) {
                            $separator = '|';

                            if (sizeof($varType) > 2) {
                                $separator = $varType[2];
                            }

                            $val .= $separator;
                        }
                    }

                    $value = $val;
                }

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

                    $this->iterateArray($dataItem, $mapping, $o);

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
            case 'math':
                $obj[$varType[0]] = [];
                $math = $varType[2];

                $value = data_get($globalModel ? $this->model : $model, $valueKey, null);

                if (gettype($value) === 'array') {
                    $val = [];
                    foreach ($value as $key => $v) {
                        $val[] = $v;
                    }

                    $value = $val;
                }

                $obj[$varType[0]] = $math($value);

                break;
            case 'expr':
                $obj[$varType[0]] = [];

                // obtem variaveis decladas da expressao
                $vars = $valueKey['vars'];

                $v = [];
                foreach ($vars as $key => $var) {
                    $v[$key] =  data_get($this->model, $var, null);
                }

                $expressionLanguage = new ExpressionLanguage();
                $res = $expressionLanguage->evaluate($valueKey['expr'], $v);

                // verifica se existe extrator
                if (array_key_exists('extract', $valueKey)) {
                    $extractKey = $valueKey['extract'];

                    $ex = null;

                    if (gettype($extractKey) === 'string') {
                        $ex = function ($r) use ($extractKey) {
                            return $r->$extractKey;
                        };
                    }

                    if (gettype($extractKey) === 'array') {
                        $ex = function ($r) use ($extractKey) {
                            $o = [];

                            foreach ($extractKey as $key => $value) {
                                $this->checkKeyType($key, $value, $o, $extractKey, $r, false);
                            }

                            return $o;
                        };
                    }

                    if (gettype($res) === 'array') {
                        $v = [];

                        foreach ($res as $key => $r) {
                            $v[] = $ex($r);
                        }

                        $res = $v;
                    } else {
                        $res = $res->$extractKey;
                    }
                }

                $obj[$varType[0]] = $res;

                break;
            default:
                break;
        }

        return $obj;
    }

    private function iterateArray($dataItem, $mapping, &$o)
    {
        if ($dataItem instanceof \Illuminate\Database\Eloquent\Collection) {
            foreach ($dataItem as $subDataItem) {
                $this->iterateArray($subDataItem, $mapping, $o);
            }
        } else {
            foreach ($mapping as $keyMap => $mapItem) {
                $this->checkKeyType($keyMap, $mapItem, $o, $mapping, $dataItem, false);
            }
        }
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
