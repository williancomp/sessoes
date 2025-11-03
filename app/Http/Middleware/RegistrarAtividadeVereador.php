<?php

namespace App\Http\Middleware;

use App\Events\PresencaAtualizada;
use App\Models\Presenca;
use App\Models\Vereador;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegistrarAtividadeVereador
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Só processa se o usuário estiver autenticado
        if (!Auth::check()) {
            return $response;
        }

        $user = Auth::user();
        
        // Verifica se o usuário é um vereador
        $vereador = Vereador::where('user_id', $user->id)->first();
        if (!$vereador) {
            return $response;
        }

        // Atualiza o timestamp de atividade na presença ativa
        $presencaAtiva = Presenca::where('vereador_id', $vereador->id)
            ->where('presente', true)
            ->whereHas('sessao', function ($query) {
                $query->whereIn('status', ['agendada', 'em_andamento']);
            })
            ->first();

        if ($presencaAtiva) {
            $presencaAtiva->update([
                'horario_login' => Carbon::now()
            ]);

            // Dispara evento de presença atualizada para atualizar o widget em tempo real
            $sessaoId = $presencaAtiva->sessao_id;
            $totalVereadores = Vereador::count();
            $presentes = Presenca::where('sessao_id', $sessaoId)->where('presente', true)->count();
            $ausentes = $totalVereadores - $presentes;

            broadcast(new PresencaAtualizada($sessaoId, $presentes, $ausentes));

            Log::debug('RegistrarAtividadeVereador: Atividade registrada e evento disparado', [
                'vereador_id' => $vereador->id,
                'nome_parlamentar' => $vereador->nome_parlamentar,
                'timestamp' => Carbon::now()->toDateTimeString(),
                'rota' => $request->route()?->getName(),
                'url' => $request->url(),
                'sessao_id' => $sessaoId,
                'presentes' => $presentes,
                'ausentes' => $ausentes
            ]);
        }

        return $response;
    }
}