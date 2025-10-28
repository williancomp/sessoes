<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContagemVotosAtualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pautaId;
    public int $sim;
    public int $nao;
    public int $abst;

    /**
     * Create a new event instance.
     */
    public function __construct(int $pautaId, int $sim, int $nao, int $abst)
    {
        $this->pautaId = $pautaId;
        $this->sim = $sim;
        $this->nao = $nao;
        $this->abst = $abst;
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
