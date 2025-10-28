<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VotacaoEncerrada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pautaId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $pautaId)
    {
        $this->pautaId = $pautaId;
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }
}
