<?php

namespace App\Http\Middleware;

use App\Services\EstadoGlobalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar e sincronizar estado global
 */
class VerificarEstadoGlobal
{
    public function __construct(
        private EstadoGlobalService $estadoGlobal
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o estado está sincronizado
        if (!$this->estadoGlobal->verificarSincronizacao()) {
            // Se não estiver sincronizado, força atualização
            $this->estadoGlobal->atualizarEstadoDoBanco();
        }

        $response = $next($request);

        // Para requisições AJAX/Livewire, adiciona o estado atual nos headers
        // APENAS se a resposta for válida
        if (($request->ajax() || $request->wantsJson()) && $response instanceof Response) {
            try {
                $estado = $this->estadoGlobal->getEstadoCompleto();
                $response->headers->set('X-Estado-Global', json_encode($estado));
            } catch (\Exception $e) {
                // Log do erro mas não interrompe a requisição
                \Log::error('Erro ao adicionar estado global no header', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $response;
    }
}