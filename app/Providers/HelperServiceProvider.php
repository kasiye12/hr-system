<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register helper functions
    }

    public function register()
    {
        require_once app_path('Helpers/helpers.php');
    }
}