<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresencaAtualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Propriedades públicas que serão enviadas no evento
    public int $contagemPresentes;
    public int $contagemAusentes;
    public int $sessaoId; // Pode ser útil para o frontend saber a sessão

    /**
     * Create a new event instance.
     */
    public function __construct(int $sessaoId, int $contagemPresentes, int $contagemAusentes)
    {
        $this->sessaoId = $sessaoId;
        $this->contagemPresentes = $contagemPresentes;
        $this->contagemAusentes = $contagemAusentes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Envia para o canal público que o telão está ouvindo
        return [
            new Channel('sessao-plenaria'),
        ];
    }
}
