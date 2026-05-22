<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\FeeVoucher;
use App\Models\FeeStructure;
use App\Observers\VoucherObserver;
use App\Observers\VoucherEditHistoryObserver;
use App\Observers\FeeStructureObserver;

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
        Vite::prefetch(concurrency: 3);

        // Register model observers for financial integrity
        FeeVoucher::observe(VoucherObserver::class);
        FeeVoucher::observe(VoucherEditHistoryObserver::class);
        FeeStructure::observe(FeeStructureObserver::class);
    }
}
