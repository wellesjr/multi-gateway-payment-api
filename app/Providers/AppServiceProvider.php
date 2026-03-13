<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\GatewayRepository;
use App\Repositories\ClientRepository;
use App\Repositories\PaymentAttemptRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Services\Payment\GatewayRegistry;
use App\Repositories\Interfaces\PaymentAttemptRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\GatewayRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(480)->by($request->user()?->id ?: $request->ip());
        });

        $this->configureAuthorizationGates();
    }

    private function configureAuthorizationGates(): void
    {
        Gate::define('users.create', function (User $user): bool {
            return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
        });

        Gate::define('users.update', function (User $user, User $targetUser): bool {
            if ($user->role === UserRole::Admin) {
                return true;
            }

            if ($user->role === UserRole::Manager) {
                return $targetUser->role !== UserRole::Admin;
            }

            return $user->id === $targetUser->id;
        });

        Gate::define('products.manage', function (User $user): bool {
            return in_array($user->role, [UserRole::Admin, UserRole::Manager], true);
        });

        Gate::define('gateways.manage', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });
    }
}
