<?php

namespace App\Providers;

use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Commercial;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\ContractorProject;
use App\Models\TrendAgent\House;
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
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'complex' => Complex::class,
            'apartment' => Apartment::class,
            'parking' => \App\Models\TrendAgent\Parking::class,
            'house' => House::class,
            'commercial' => Commercial::class,
            'contractor_project' => ContractorProject::class,
        ]);
    }
}
