<?php

use App\Http\Controllers\SimulacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('analise');
});

Route::get('/clientes', fn () => view('clientes'));

Route::get('/simulacao/{id}', [SimulacaoController::class, 'show']);
