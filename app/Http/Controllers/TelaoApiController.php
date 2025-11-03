<?php

namespace App\Http\Controllers;

use App\Services\EstadoGlobalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TelaoApiController extends Controller
{
    protected EstadoGlobalService $estadoGlobalService;

    public function __construct(EstadoGlobalService $estadoGlobalService)
    {
        $this->estadoGlobalService = $estadoGlobalService;
    }

    /**
     * Retorna o estado atual do telão
     */
    public function getEstado(): JsonResponse
    {
        $layoutData = $this->estadoGlobalService->getTelaoLayout();
        $palavraAtiva = $this->estadoGlobalService->getPalavraAtiva();
        
        return response()->json([
            'layout' => $layoutData['layout'] ?? 'layout-normal',
            'dados' => $layoutData['dados'] ?? [],
            'palavra_ativa' => $palavraAtiva,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Retorna dados específicos para cada layout
     */
    public function getDadosLayout(string $layout): JsonResponse
    {
        $dados = [];
        
        switch ($layout) {
            case 'layout-normal':
                $layoutData = $this->estadoGlobalService->getTelaoLayout();
                $dados = $layoutData['dados'] ?? [];
                break;
                
            case 'layout-voting':
                // Dados de votação se necessário
                break;
                
            default:
                $dados = [];
        }
        
        return response()->json([
            'layout' => $layout,
            'dados' => $dados,
            'timestamp' => now()->toISOString(),
        ]);
    }
}