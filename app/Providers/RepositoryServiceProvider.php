<?php

namespace App\Providers;

use App\Repositories\ClientRepository;
use App\Repositories\GatewayRepository;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use App\Repositories\Interfaces\PaymentAttemptRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\PaymentAttemptRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\Payment\GatewayRegistry;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(GatewayRepositoryInterface::class, GatewayRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(PaymentAttemptRepositoryInterface::class, PaymentAttemptRepository::class);

        $gatewayClients = config('payment.gateway_clients', []);

        if (is_array($gatewayClients) && !empty($gatewayClients)) {
            $this->app->tag($gatewayClients, 'payment-gateway-clients');
        }

        $this->app
            ->when(GatewayRegistry::class)
            ->needs('$clients')
            ->giveTagged('payment-gateway-clients');
    }
}