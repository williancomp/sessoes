<?php

namespace App\Events;

use App\Models\Vereador;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VotoRegistrado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $vereadorId;
    public string $voto; // 'sim', 'nao', 'abst'
    public string $nomeVereador; // Enviar nome para o telão

    /**
     * Create a new event instance.
     */
    public function __construct(Vereador $vereador, string $voto)
    {
        $this->vereadorId = $vereador->id;
        $this->voto = $voto;
        $this->nomeVereador = $vereador->nome_parlamentar; // Pega o nome
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }
}
