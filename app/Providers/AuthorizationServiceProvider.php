<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->defineRoleGate('users.manage', UserRole::Admin, UserRole::Manager);
        $this->defineRoleGate('users.view', UserRole::Admin, UserRole::Manager);

        Gate::define('users.update-target', function (User $user, User $targetUser): bool {
            if ($user->role === UserRole::Admin) {
                return true;
            }

            if ($user->role === UserRole::Manager) {
                return $targetUser->role !== UserRole::Admin;
            }

            return false;
        });

        Gate::define('users.delete-target', function (User $user, User $targetUser): bool {
            if ($user->role === UserRole::Admin) {
                return true;
            }

            if ($user->role === UserRole::Manager) {
                return $targetUser->role !== UserRole::Admin;
            }

            return false;
        });

        $this->defineRoleGate('products.manage', UserRole::Admin, UserRole::Manager, UserRole::Finance);
        $this->defineRoleGate('products.view', UserRole::Admin, UserRole::Manager, UserRole::Finance);
        $this->defineRoleGate('products.delete', UserRole::Admin, UserRole::Manager, UserRole::Finance);

        $this->defineRoleGate('clients.view', UserRole::Admin);

        $this->defineRoleGate('gateways.manage', UserRole::Admin);

        $this->defineRoleGate('transactions.view', UserRole::Admin);
        $this->defineRoleGate('transactions.refund', UserRole::Admin, UserRole::Finance);
    }

    private function defineRoleGate(string $ability, UserRole ...$roles): void
    {
        Gate::define($ability, function (User $user) use ($roles): bool {
            return in_array($user->role, $roles, true);
        });
    }
}
