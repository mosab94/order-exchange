<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
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
        Blueprint::macro('tinyForeignId', function ($column) {
            return $this->addColumnDefinition(new ForeignIdColumnDefinition($this, [
                'type' => 'tinyInteger',
                'name' => $column,
                'autoIncrement' => false,
                'unsigned' => true,
            ]));
        });
        Blueprint::macro('tinyId', function ($column = 'id') {
            return $this->tinyIncrements($column);
        });
    }
}
