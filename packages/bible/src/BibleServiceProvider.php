<?php

declare(strict_types=1);

namespace DailyDash\Bible;

use Illuminate\Support\ServiceProvider;

class BibleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'bible');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
