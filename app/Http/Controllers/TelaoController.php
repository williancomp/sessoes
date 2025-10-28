<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View; // Importe a classe View

class TelaoController extends Controller
{
    /**
     * Mostra a view do Telão Público.
     */
    public function show(): View
    {
        // Futuramente, podemos passar dados iniciais para a view se necessário
        return view('telao');
    }
}