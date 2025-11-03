<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\EstadoGlobalService;
use App\Http\Controllers\TelaoApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rota de teste simples
Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'API funcionando']);
});

// Rotas do Telão Vue.js
Route::prefix('telao')->group(function () {
    Route::get('/estado', [TelaoApiController::class, 'getEstado']);
    Route::get('/layout/{layout}', [TelaoApiController::class, 'getDadosLayout']);
});

// Rota para obter estado global (usado pelo telão para sincronização)
Route::get('/estado-global', function () {
    try {
        $estadoGlobal = app(EstadoGlobalService::class);
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