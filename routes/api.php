<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\LocalDataController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// rutas protegidas con token Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/vehiculos/{userId}', [VehiculoController::class, 'showByUser']);
    Route::get('/sync/calles', [SyncController::class,'syncCalles']);
    Route::get('/sync/vehiculos', [SyncController::class,'syncVehiculos']);
    Route::get('/calles', [LocalDataController::class,'calles']);
    Route::get('/vehiculos', [LocalDataController::class,'vehiculos']);
    Route::get('/vehiculos/usuario/{userId}', [LocalDataController::class,'vehiculoPorUsuario']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

