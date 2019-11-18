<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('importa-veiculos', 'Import\ImportaVeiculos@run');

Route::get('filtra-veiculos', 'Read\RetornaVeiculos@filtraVeiculos');
Route::get('veiculo-especifico', 'Read\RetornaVeiculos@veiculoEspecifico');