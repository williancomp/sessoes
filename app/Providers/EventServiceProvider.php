<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login; // Importe Login
use Illuminate\Auth\Events\Logout; // Importe Logout
use App\Listeners\RecordVereadorPresence; // Importe seu Listener
use App\Listeners\RecordVereadorLogout; // Importe o novo Listener
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
// ... outros imports ...

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ... outros listeners ...

        // Garanta que esta linha exista:
        Login::class => [
            RecordVereadorPresence::class,
        ],
        
        // Adiciona o listener de logout
        Logout::class => [
            RecordVereadorLogout::class,
        ],
    ];

    // ... restante do arquivo ...
}