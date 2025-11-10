<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Ruta;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\LocalDataController;
use App\Http\Controllers\Api\RutaController;
use App\Http\Controllers\Api\RecorridoController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

 // Vehículos
    Route::get('/vehiculos', [VehiculoController::class, 'index']);
    Route::post('/vehiculos', [VehiculoController::class, 'store']);
    Route::get('/vehiculos/sync', [App\Http\Controllers\Api\VehiculoController::class, 'syncFromPrincipal']);
    Route::delete('/vehiculos/{id}', [App\Http\Controllers\Api\VehiculoController::class, 'destroy']);

// Rutas
    Route::get('/rutas', [RutaController::class, 'index']);
    Route::post('/rutas', [RutaController::class, 'store']);
    Route::post('/rutas/sync', [RutaController::class, 'syncFromPrincipal']);


// rutas protegidas con token Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/sync/calles', [SyncController::class,'syncCalles']);
    Route::get('/calles', [LocalDataController::class,'calles']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/misrecorridos', [RecorridoController::class, 'misRecorridos']);
    //Route::post('/recorridos/iniciar', [RecorridoController::class, 'iniciar']);
    //Route::post('/recorridos/{recorrido}/posiciones', [RecorridoController::class, 'registrarPosicion']);
    Route::get('/recorridos/{recorrido}/posiciones', [RecorridoController::class, 'obtenerPosiciones']);
    //Route::post('/recorridos/{recorrido}/finalizar', [RecorridoController::class, 'finalizar']);
    Route::get('/recorridos/sincronizar', [RecorridoController::class, 'sincronizarEstados']);
});


Route::middleware(['auth:sanctum', 'conductor'])->group(function () {
    Route::post('/recorridos/iniciar', [RecorridoController::class, 'iniciar']);
    Route::post('/recorridos/{id}/posiciones', [RecorridoController::class, 'registrarPosicion']);
    Route::post('/recorridos/{id}/finalizar', [RecorridoController::class, 'finalizar']);
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();

    $user->load(['role', 'vehiculos', 'rutas']); // 👈 agrega aquí 'role'

    return response()->json($user);
});


Route::options('{any}', function (Request $request) {
    return response()->noContent(204);
})->where('any', '.*');


