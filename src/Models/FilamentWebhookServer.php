<?php

namespace Marjose123\FilamentWebhookServer\Models;

use Illuminate\Database\Eloquent\Model;

class FilamentWebhookServer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'url',
        'method',
        'model',
        'header',
        'data_option',
        'verifySsl',
        'status',
        'events',
        'sync',
        'data_type',
        'ativo',
        'custom_data_option',
        'custom_events',
        'url_params',
        'regra_envio'
    ];

    protected $casts = [
        'header' => 'array',
        'events' => 'array',
        'custom_data_option' => 'array',
        'custom_events' => 'array',
        'url_params' => 'array',
        'regra_envio' => 'array'
    ];

    public function transactionlogs()
    {
        return $this->hasMany(FilamentWebhookServerHistory::class, 'webhook_client', 'id');
    }
}
