<?php

namespace App\Providers;

use App\Repositories\UserRepository;
use App\Repositories\GatewayRepository;
use App\Repositories\ClientRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Services\Payment\PaymentGatewayClientResolver;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(GatewayRepositoryInterface::class, GatewayRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);

        $gatewayClients = config('payment.gateway_clients', []);

        if (is_array($gatewayClients) && !empty($gatewayClients)) {
            $this->app->tag($gatewayClients, 'payment-gateway-clients');
        }

        $this->app
            ->when(PaymentGatewayClientResolver::class)
            ->needs('$clients')
            ->giveTagged('payment-gateway-clients');
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(480)->by($request->user()?->id ?: $request->ip());
        });
    }
}
