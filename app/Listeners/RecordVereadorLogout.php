<?php

namespace App\Listeners;

use App\Events\PresencaAtualizada;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\Vereador;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RecordVereadorLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        // 1. Verifica se o logout ocorreu no painel 'vereador' E se o usuário tem o papel 'Vereador'
        if (Filament::getCurrentPanel()?->getId() === 'vereador' && $user->hasRole('Vereador')) {

            // 2. Encontra a sessão ativa do dia (ou a mais recente agendada/em andamento)
            $sessaoAtual = Sessao::where('data', '<=', Carbon::today())
                                ->whereIn('status', ['agendada', 'em_andamento'])
                                ->orderBy('data', 'desc')
                                ->first();

            if (!$sessaoAtual) {
                Log::warning("Tentativa de logout de vereador ({$user->email}) sem sessão ativa encontrada.");
                return;
            }

            // 3. Encontra o registro Vereador associado ao User
            $vereador = Vereador::where('user_id', $user->id)->first();

            if (!$vereador) {
                Log::error("Usuário {$user->email} com papel Vereador fez logout, mas não foi encontrado registro Vereador associado.");
                return;
            }

            // 4. Atualiza a presença para ausente
            Presenca::updateOrCreate(
                [
                    'sessao_id' => $sessaoAtual->id,
                    'vereador_id' => $vereador->id,
                ],
                [
                    'presente' => false,
                    'horario_login' => null, // Remove o horário de login
                ]
            );

            // 5. Calcula as contagens de presença para a sessão atual
            $totalVereadores = Vereador::count();
            $presentes = Presenca::where('sessao_id', $sessaoAtual->id)->where('presente', true)->count();
            $ausentes = $totalVereadores - $presentes;

            // 6. Dispara o evento para atualizar o telão (e outros clientes)
            broadcast(new PresencaAtualizada($sessaoAtual->id, $presentes, $ausentes))->toOthers();

            Log::info("Presença removida para Vereador ID {$vereador->id} na Sessão ID {$sessaoAtual->id}. Presentes: {$presentes}, Ausentes: {$ausentes}");
        }
    }
}