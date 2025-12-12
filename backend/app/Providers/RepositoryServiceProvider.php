<?php

namespace App\Providers;

use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TradeRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentAssetRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentTradeRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository bindings
     */
    private array $repositoryBindings = [
        UserRepository::class => EloquentUserRepository::class,
        AssetRepository::class => EloquentAssetRepository::class,
        OrderRepository::class => EloquentOrderRepository::class,
        TradeRepository::class => EloquentTradeRepository::class,
    ];

    /**
     * Get repository bindings.
     */
    private function getBindings(): array
    {
        return $this->repositoryBindings;
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->getBindings() as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
