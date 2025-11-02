<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class VotacaoEncerrada implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pautaId;

    public function __construct(int $pautaId)
    {
        $this->pautaId = $pautaId;
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    public function broadcastAs(): string
    {
        return 'VotacaoEncerrada';
    }

    public function broadcastWith(): array
    {
        return [
            'pautaId' => $this->pautaId,
        ];
    }
}