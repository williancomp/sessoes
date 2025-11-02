<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class ContagemVotosAtualizada implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $pautaId;
    public int $sim;
    public int $nao;
    public int $abst;

    public function __construct(int $pautaId, int $sim, int $nao, int $abst)
    {
        $this->pautaId = $pautaId;
        $this->sim = $sim;
        $this->nao = $nao;
        $this->abst = $abst;
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    public function broadcastAs(): string
    {
        return 'ContagemVotosAtualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'pautaId' => $this->pautaId,
            'sim' => $this->sim,
            'nao' => $this->nao,
            'abst' => $this->abst,
        ];
    }
}