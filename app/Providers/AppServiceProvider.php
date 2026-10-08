<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.gapoktan');

        Gate::define('kelola', fn (User $pengguna) => $pengguna->bisaKelola());
        Gate::define('kelola-pengguna', fn (User $pengguna) => $pengguna->bisaKelolaPengguna());
        Gate::define('lihat-pengaturan', fn (User $pengguna) => $pengguna->bisaLihatPengaturan());
        Gate::define('kelola-pengaturan', fn (User $pengguna) => $pengguna->bisaKelolaPengaturan());
        Gate::define('lihat-pengurus', fn (User $pengguna) => $pengguna->bisaLihatMenuPengurus());
    }
}
