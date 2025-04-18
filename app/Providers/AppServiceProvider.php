<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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
    public function boot()
    {
        Relation::morphMap([
            'books' => \App\Models\Book::class,
            'members' => \App\Models\Member::class,
            'borrowing' => \App\Models\Borrowing::class,
            // tambahkan model lain kalau perlu
        ]);
    }
}
