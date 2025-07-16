<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XMLController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/xml', [XMLController::class, 'convertirFacturaXmlACsv'])
    ->name('xml');

Route::post('/hash-password', [XMLController::class, 'hashPassword']);

Route::get('csrf', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});