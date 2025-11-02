<?php

namespace App\Events;

use App\Models\Vereador;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class PalavraEstadoAlterado implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $status; // 'iniciada', 'pausada', 'retomada', 'encerrada'
    public ?array $vereador;
    public int $segundosRestantes;
    public ?int $timestampInicio; // Para sincronia

    /**
     * @param string $status 'iniciada', 'pausada', 'retomada', 'encerrada'
     * @param ?Vereador $vereador Opcional, nulo se 'encerrada'
     * @param int $segundosRestantes
     * @param ?int $timestampInicio Timestamp de quando o timer (re)iniciou
     */
    public function __construct(
        string $status, 
        ?Vereador $vereador, 
        int $segundosRestantes, 
        ?int $timestampInicio = null
    ) {
        $this->status = $status;
        $this->segundosRestantes = $segundosRestantes;
        $this->timestampInicio = $timestampInicio;
        
        if ($vereador) {
            $this->vereador = [
                'id' => $vereador->id,
                'nome_parlamentar' => $vereador->nome_parlamentar,
            ];
        } else {
            $this->vereador = null;
        }
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    public function broadcastAs(): string
    {
        return 'PalavraEstadoAlterado';
    }
}