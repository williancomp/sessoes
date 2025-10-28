<?php

namespace App\Listeners;

use App\Events\PresencaAtualizada;
use App\Models\Presenca;
use App\Models\Sessao;
use App\Models\Vereador;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class RecordVereadorPresence
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
    public function handle(Login $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        // 1. Verifica se o login ocorreu no painel 'vereador' E se o usuário tem o papel 'Vereador'
        if (Filament::getCurrentPanel()?->getId() === 'vereador' && $user->hasRole('Vereador')) {

            // 2. Encontra a sessão ativa do dia (ou a mais recente agendada/em andamento)
            $sessaoAtual = Sessao::where('data', '<=', Carbon::today()) // <= hoje para pegar sessões passadas não concluídas
                                ->whereIn('status', ['agendada', 'em_andamento'])
                                ->orderBy('data', 'desc') // Pega a mais recente se houver mais de uma
                                ->first();

            if (!$sessaoAtual) {
                \Log::warning("Tentativa de login de vereador ({$user->email}) sem sessão ativa encontrada.");
                return; // Não faz nada se não houver sessão ativa
            }

            // 3. Encontra o registro Vereador associado ao User
            //    (Assumindo que a relação se chama 'vereador' no Model User, se não, ajuste)
            //    Se a relação não existir no User model, adicione:
            //    public function vereador(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Vereador::class); }
            $vereador = Vereador::where('user_id', $user->id)->first(); // Busca pelo user_id

            if (!$vereador) {
                \Log::error("Usuário {$user->email} com papel Vereador logou, mas não foi encontrado registro Vereador associado.");
                return; // Vereador não encontrado, erro de consistência de dados
            }

            // 4. Registra ou atualiza a presença
            Presenca::updateOrCreate(
                [
                    'sessao_id' => $sessaoAtual->id,
                    'vereador_id' => $vereador->id,
                ],
                [
                    'presente' => true,
                    'horario_login' => now(), // Hora atual
                ]
            );

            // 5. Calcula as contagens de presença para a sessão atual
            $totalVereadores = Vereador::count(); // Ou conte apenas os da legislatura atual se necessário
            $presentes = Presenca::where('sessao_id', $sessaoAtual->id)->where('presente', true)->count();
            $ausentes = $totalVereadores - $presentes;

            // 6. Dispara o evento para atualizar o telão (e outros clientes)
            broadcast(new PresencaAtualizada($sessaoAtual->id, $presentes, $ausentes))->toOthers();

            \Log::info("Presença registrada para Vereador ID {$vereador->id} na Sessão ID {$sessaoAtual->id}. Presentes: {$presentes}, Ausentes: {$ausentes}");
        }
    }
}
