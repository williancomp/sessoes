<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class LayoutTelaoAlterado implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Propriedades públicas são enviadas automaticamente
    public string $layout;
    public ?array $dados; // Dados opcionais (ex: dados da pauta)

    /**
     * Create a new event instance.
     */
    public function __construct(string $layout, ?array $dados = null)
    {
        $this->layout = $layout;
        $this->dados = $dados;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Canal público que o telao.blade.php está ouvindo
        return [
            new Channel('sessao-plenaria'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'LayoutTelaoAlterado';
    }
}
