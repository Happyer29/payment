<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Http\Resources\Bank\Card;
use App\Http\Resources\Bank\Order;
use App\Http\Resources\Bank\PaymentLink;
use App\Http\Resources\Bank\Shop;
use App\Http\Resources\Bank\Withdraw;
use App\Models\User;
use App\Policies\Admin\Crud\CardPolicy;
use App\Policies\Admin\Crud\OrderPolicy;
use App\Policies\Admin\Crud\PaymentLinkPolicy;
use App\Policies\Admin\Crud\ShopPolicy;
use App\Policies\Admin\Crud\UserPolicy;
use App\Policies\Admin\Crud\WithdrawPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Shop::class => ShopPolicy::class,
        Order::class => OrderPolicy::class,
        PaymentLink::class => PaymentLinkPolicy::class,
        Card::class => CardPolicy::class,
        User::class => UserPolicy::class,
        Withdraw::class => WithdrawPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // cards
        Gate::define('view-any-card', function (User $user){
            return $user?->can('viewAny', Card::class);
        });

        // users
        Gate::define('view-any-user', function (User $user){
            return $user?->can('viewAny', User::class);
        });
        Gate::define('create-user', function (User $user){
            return $user?->can('create', User::class);
        });

        // bank messages
        Gate::define('use-bank-messages', function (User $user){
            return $user?->isAnyManager();
        });
        Gate::define('approve-bank-messages', function (User $user){
            return $user?->isAnyManager();
        });
        Gate::define('decline-bank-messages', function (User $user){
            return $user?->isAnyManager();
        });

        // Admin Analytics
        Gate::define('view-analytics', function (User $user){
            return $user->isAnyManager();
        });

    }
}
