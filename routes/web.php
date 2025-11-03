<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\TelaoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telao', [TelaoController::class, 'show'])->name('telao.show');



// Rota de heartbeat para vereadores
Route::get('/api/vereadores-votacao', function () {
    $votacaoAtiva = app(\App\Services\EstadoGlobalService::class)->getVotacaoAtiva();
    
    if (!$votacaoAtiva) {
        return response()->json(['error' => 'Nenhuma votação ativa'], 404);
    }
    
    $vereadores = \App\Models\Vereador::with('partido')
        ->orderBy('nome_parlamentar')
        ->get();
    
    $votos = \App\Models\Voto::where('pauta_id', $votacaoAtiva['pauta_id'])
        ->pluck('voto', 'vereador_id')
        ->toArray();
    
    $resultado = $vereadores->map(function ($vereador) use ($votos) {
        return [
            'id' => $vereador->id,
            'nome_parlamentar' => $vereador->nome_parlamentar,
            'partido' => $vereador->partido?->sigla ?? 'S/P',
            'voto' => $votos[$vereador->id] ?? null,
        ];
    });
    
    return response()->json([
        'pauta_id' => $votacaoAtiva['pauta_id'],
        'vereadores' => $resultado
    ]);
});

Route::post('/vereador/heartbeat', function () {
    if (!Auth::check()) {
        return response()->json(['error' => 'Não autenticado'], 401);
    }

    $user = Auth::user();
    $vereador = \App\Models\Vereador::where('user_id', $user->id)->first();
    
    if (!$vereador) {
        return response()->json(['error' => 'Usuário não é vereador'], 403);
    }

    // Atualiza timestamp de atividade
    $presencaAtiva = \App\Models\Presenca::where('vereador_id', $vereador->id)
        ->where('presente', true)
        ->whereHas('sessao', function ($query) {
            $query->whereIn('status', ['agendada', 'em_andamento']);
        })
        ->first();

    if ($presencaAtiva) {
        $presencaAtiva->update([
            'horario_login' => \Carbon\Carbon::now()
        ]);
        
        return response()->json(['status' => 'heartbeat_registered']);
    }

    return response()->json(['status' => 'no_active_session']);
})->name('vereador.heartbeat')->middleware('auth');

// Rota de teste para estado global via web
Route::get('/test-estado-global', function () {
    try {
        $estadoGlobal = app(\App\Services\EstadoGlobalService::class);
        return response()->json([
            'status' => 'success',
            'data' => $estadoGlobal->getEstadoCompleto()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
