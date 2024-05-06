<?php

namespace Marjose123\FilamentWebhookServer;

use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Marjose123\FilamentWebhookServer\Models\FilamentWebhookServer;
use Marjose123\FilamentWebhookServer\Observers\ModelObserver;
use ReflectionClass;
use Spatie\LaravelPackageTools\Exceptions\InvalidPackage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\ModelInfo\ModelFinder;
use Spatie\ModelInfo\ModelInfo;

class FilamentWebhookServerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-webhook-server';

    public function getPages(): array
    {
        return config('filament-webhook-server.pages');
    }

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_filament-webhook-server_table',
                '2023_01_19_144816_create_filament_webhook_server_histories_table',
                'alter_filament-webhook-server_table_add_columns',
                'alter_table_filament-webhook-server_add_custom_columns',
                '2024_01_04_125547_alter_table_filament_webhook_add_column_url_params',
                '2024_05_03_122249_alter_webhook_server_table_add_regra_envio_column'
            ])
            ->hasViews();
    }


    /**
     * @throws InvalidPackage
     */
    public function register(): void
    {
        parent::register();
        $this->app->register(EventServiceProvider::class);
    }

    public function boot(): void
    {
        parent::boot();
        self::registerGlobalObserver();
    }

    private static function registerGlobalObserver(): void
    {
        /** @var Model[] $MODELS */
        // $MODELS = [
        //     config('filament-webhook-server.models'),
        // ];

        $MODELS = [];

        $models = ModelFinder::all();
        foreach ($models as $m) {
            if (self::modelHasTrait($m, \Marjose123\FilamentWebhookServer\Traits\SyncableModel::class)) {
                $m::observe(ModelObserver::class);
                $MODELS[] = $m;
            }
            // $reflectionClass = new ReflectionClass($m);

            // $traits = array_keys($reflectionClass->getTraits());

            // if (in_array(\Marjose123\FilamentWebhookServer\Traits\SyncableModel::class, $traits)) {
            //     $MODELS[] = $m;
            // $m::observe(ModelObserver::class);
            // }
        }

        config(['filament-webhook-server.models' => $MODELS]);

        // foreach ($MODELS as $MODEL) {
        //     foreach ($MODEL as $model) {
        //         $model::observe(ModelObserver::class);
        //     }
        // }
    }

    private static function modelHasTrait($model, string $trait): bool
    {

        $reflectionClass = new ReflectionClass($model);

        $modelTraits = array_keys($reflectionClass->getTraits());

        if (in_array($trait, $modelTraits)) {
            return true;
        }

        return false;
    }
}
