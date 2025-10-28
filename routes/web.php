<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelaoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telao', [TelaoController::class, 'show'])->name('telao.show');
