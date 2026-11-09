<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Perfil del usuario autenticado.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Actualiza el perfil del usuario autenticado.
     *
     * Con PUT se envían todos los datos; con PATCH solo los que cambian.
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $user->update($request->validated());

        return (new UserResource($user))->additional([
            'message' => 'Perfil actualizado correctamente.',
        ]);
    }
}
