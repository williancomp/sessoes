<?php

namespace App\Events;

use App\Models\Pauta;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels; 
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class VotacaoAberta implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Pauta $pauta;

    public function __construct(Pauta $pauta)
    {
        $this->pauta = $pauta;
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    public function broadcastAs(): string
    {
        return 'VotacaoAberta';
    }

    public function broadcastWith(): array
    {
        return [
            'pauta' => [
                'id' => $this->pauta->id,
                'numero' => $this->pauta->numero,
                'descricao' => $this->pauta->descricao,
                'autor' => $this->pauta->autor,
                'sessao_id' => $this->pauta->sessao_id,
            ],
        ];
    }
}