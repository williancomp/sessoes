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
        $layoutInicial = $estadoCompleto['telao_layout']['layout'] ?? 'layout-normal';
        $dadosPautaInicial = $estadoCompleto['telao_layout']['dados'] ?? null;
        
        // CORREÇÃO: Palavra ativa tem PRIORIDADE MÁXIMA sobre votação
        if (isset($estadoCompleto['palavra_ativa']['vereador'])) {
            $layoutInicial = 'layout-normal';
            $dadosPautaInicial = [
                'vereador' => $estadoCompleto['palavra_ativa']['vereador']
            ];
            
            // Se há também uma pauta em discussão no telao_layout, inclui os dados da pauta
            if (isset($estadoCompleto['telao_layout']['dados']) && 
                is_array($estadoCompleto['telao_layout']['dados']) && 
                !isset($estadoCompleto['telao_layout']['dados']['vereador'])) {
                $dadosPautaInicial = array_merge($dadosPautaInicial, $estadoCompleto['telao_layout']['dados']);
            }
        }
        // Se há votação ativa (mas não há palavra ativa), usa os dados da votação
        elseif ($estadoCompleto['votacao_ativa']) {
            $layoutInicial = 'layout-voting';
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