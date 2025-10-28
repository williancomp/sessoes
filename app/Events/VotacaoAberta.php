<?php

namespace App\Events;

use App\Models\Pauta;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
// Presença/Canal Privado não são necessários aqui
// use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels; 

class VotacaoAberta implements ShouldBroadcast
{
    // Use os traits necessários, incluindo SerializesModels
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Declare a propriedade pública com o tipo correto
    public Pauta $pauta;

    /**
     * Create a new event instance.
     * Use a tipagem consistente no construtor.
     */
    public function __construct(Pauta $pauta)
    {
        $this->pauta = $pauta;
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