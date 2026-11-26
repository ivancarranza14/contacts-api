<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Crea una cuenta y entrega un token para la app.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->except('device_name'));

        $token = $user->createToken($request->validated('device_name'))->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Inicia sesión y entrega un token para la app.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // 401 con el mismo mensaje si el correo no existe o la contraseña no coincide:
        // así nadie puede averiguar qué correos están registrados.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // device_name es opcional: si la app no lo envía, el token se llama "app".
        $token = $user->createToken($request->validated('device_name') ?? 'app')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada exitosamente',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Cierra la sesión y revoca el token actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }
}
