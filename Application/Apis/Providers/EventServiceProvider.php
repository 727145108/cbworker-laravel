<?php

namespace Application\Apis\Providers;

use Cbworker\Core\Providers\EventServiceProvider as ServiceProvider;


class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
      \Illuminate\Database\Events\QueryExecuted::class => [
        'Application\Apis\Listeners\QueryListener'
      ],
      \Application\Apis\Events\Warehouse::class => [
        'Application\Apis\Listeners\WarehouseListener'
      ]
    ];
    
    protected $subscribe = [
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
