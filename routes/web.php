<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelaoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telao', [TelaoController::class, 'show'])->name('telao.show');

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
