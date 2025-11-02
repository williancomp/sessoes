<?php

namespace App\Http\Controllers;

use App\Services\EstadoGlobalService;
use Cache;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelaoController extends Controller
{
    public function __construct(
        private EstadoGlobalService $estadoGlobal
    ) {}

    /**
     * Mostra a view do Telão Público com estado global sincronizado.
     */
    public function show(): View
    {
        // Obtém estado global completo
        $estadoCompleto = $this->estadoGlobal->getEstadoCompleto();
        
        // Extrai dados específicos para compatibilidade
        $layoutInicial = $estadoCompleto['telao_layout']['layout'] ?? 'layout-inicial';
        $dadosPautaInicial = $estadoCompleto['telao_layout']['dados'] ?? null;
        
        // Se há votação ativa, usa os dados da votação
        if ($estadoCompleto['votacao_ativa']) {
            // ATUALIZADO: Adicionado '?? null' para evitar erros
            $dadosPautaInicial = [
                'id' => $estadoCompleto['votacao_ativa']['pauta_id'] ?? null,
                'numero' => $estadoCompleto['votacao_ativa']['pauta_numero'] ?? null,
                'descricao' => $estadoCompleto['votacao_ativa']['pauta_descricao'] ?? null,
                'autor' => $estadoCompleto['votacao_ativa']['pauta_autor'] ?? null,
            ];
        }

        return view('telao', [
            'layoutInicial' => $layoutInicial,
            'dadosPautaInicial' => $dadosPautaInicial,
            'estadoGlobal' => $estadoCompleto,
        ]);
    }
}