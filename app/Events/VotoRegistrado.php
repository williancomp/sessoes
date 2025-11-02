<?php

namespace App\Events;

use App\Models\Vereador;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;


class VotoRegistrado implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $vereadorId;
    public string $voto; // 'sim', 'nao', 'abst'
    public string $nomeVereador; // Enviar nome para o telão
    public int $pautaId; 

    public function __construct(Vereador $vereador, string $voto, int $pautaId) // Adicionar $pautaId aqui
    {
        $this->vereadorId = $vereador->id;
        $this->voto = $voto;
        $this->nomeVereador = $vereador->nome_parlamentar;
        $this->pautaId = $pautaId; // Atribuir
    }

    public function broadcastOn(): array
    {
        return [new Channel('sessao-plenaria')];
    }

    /**
     * Define o nome do evento no broadcast
     */
    public function broadcastAs(): string
    {
        return 'VotoRegistrado';
    }

    /**
     * Dados que serão enviados no broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'vereadorId' => $this->vereadorId,
            'voto' => $this->voto,
            'nomeVereador' => $this->nomeVereador,
            'pautaId' => $this->pautaId, // Adicionar esta linha
        ];
    }
}