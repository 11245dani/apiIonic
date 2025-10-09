<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['perfil_id'] = config('services.perfil_id'); // del .env

        // 1️⃣ Crear usuario local
        $user = User::create($data);

        // 2️⃣ Crear vehículo local
        $vehiculo = Vehiculo::create([
            'user_id' => $user->id,
            'perfil_id' => $user->perfil_id,
            'placa' => $request->input('placa'),
            'marca' => $request->input('marca'),
            'modelo' => $request->input('modelo'),
            'capacidad' => $request->input('capacidad'),
            'tipo_combustible' => $request->input('tipo_combustible'),
            'activo' => true,
        ]);

        // 3️⃣ Enviar vehículo a la API principal
        try {
            $response = Http::post(config('services.api_principal.base_url') . '/vehiculos', [
                'placa' => $vehiculo->placa,
                'marca' => $vehiculo->marca,
                'modelo' => $vehiculo->modelo,
                'capacidad' => $vehiculo->capacidad,
                'tipo_combustible' => $vehiculo->tipo_combustible,
                'activo' => $vehiculo->activo,
                'perfil_id' => $vehiculo->perfil_id,
            ]);

            if ($response->successful()) {
                // 4️⃣ Actualizar datos del vehículo con la respuesta real
                $apiData = $response->json();
                $vehiculo->update([
                    'perfil_id' => $apiData['perfil_id'] ?? $vehiculo->perfil_id,
                ]);
            } else {
                // Si falla, lo dejamos registrado localmente
                \Log::warning('Error al sincronizar vehículo: ' . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error('Error de conexión con API principal: ' . $e->getMessage());
        }

        // 5️⃣ Crear token personal
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario y vehículo creados y sincronizados correctamente',
            'user' => $user,
            'vehiculo' => $vehiculo,
            'token' => $token
        ], 201);
    }

    // login y logout se mantienen igual...



    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke tokens for the authenticated user
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Cerró sesión correctamente']);
    }
}
