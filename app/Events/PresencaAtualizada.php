<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresencaAtualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $contagemPresentes;
    public int $contagemAusentes;
    public int $sessaoId;

    public function __construct(int $sessaoId, int $contagemPresentes, int $contagemAusentes)
    {
        $this->sessaoId = $sessaoId;
        $this->contagemPresentes = $contagemPresentes;
        $this->contagemAusentes = $contagemAusentes;
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    public function broadcastAs(): string
    {
        return 'PresencaAtualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'sessaoId' => $this->sessaoId,
            'contagemPresentes' => $this->contagemPresentes,
            'contagemAusentes' => $this->contagemAusentes,
        ];
    }
}