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
        'custom_events'
    ];

    protected $casts = [
        'header' => 'array',
        'events' => 'array',
        'custom_data_option' => 'array',
        'custom_events' => 'array'
    ];

    public function transactionlogs()
    {
        return $this->hasMany(FilamentWebhookServerHistory::class, 'webhook_client', 'id');
    }
}
