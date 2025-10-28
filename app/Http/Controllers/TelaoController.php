<?php

namespace App\Http\Controllers;

use Cache;
use Illuminate\Http\Request;
use Illuminate\View\View; // Importe a classe View

class TelaoController extends Controller
{
    /**
     * Mostra a view do Telão Público.
     */
    public function show(): View
    {
        $layoutInicial = Cache::get('telao_layout', 'layout-inicial');
        $pautaId = Cache::get('telao_layout_data_pauta_id'); // Pega ID da pauta se houver
        $dadosPauta = null;

        if ($pautaId && ($layoutInicial === 'layout-pauta' || $layoutInicial === 'layout-votacao')) {
             $pauta = \App\Models\Pauta::find($pautaId);
             if($pauta) {
                $dadosPauta = $pauta->toArray();
             }
        }

        return view('telao', [
            'layoutInicial' => $layoutInicial,
            'dadosPautaInicial' => $dadosPauta, // Passa os dados da pauta inicial
            // Passar dados iniciais de presença e votação se necessário
        ]);
    }
}