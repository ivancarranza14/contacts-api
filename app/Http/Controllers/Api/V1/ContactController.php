<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ContactController extends Controller implements HasMiddleware
{
    /**
     * ContactPolicy se revisa antes de entrar al método (y antes de validar):
     * si el contacto es de otro usuario, la respuesta es 403.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:view,contact', only: ['show']),
            new Middleware('can:update,contact', only: ['update']),
            new Middleware('can:delete,contact', only: ['destroy']),
        ];
    }

    /**
     * Lista los contactos del usuario autenticado.
     *
     * Paginado (?per_page=15, máximo 100) y con búsqueda opcional
     * por nombre, apellido, correo o teléfono (?search=).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $buscar = trim((string) $request->query('search', ''));
        $porPagina = min(max($request->integer('per_page', 15), 1), 100);

        $contacts = $request->user()->contacts()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($query) use ($buscar) {
                    $query->where('first_name', 'like', "%{$buscar}%")
                        ->orWhere('last_name', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('phone_number', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($porPagina)
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    /**
     * Crea un contacto para el usuario autenticado.
     */
    public function store(ContactRequest $request): JsonResponse
    {
        $contact = $request->user()->contacts()->create($request->validated());

        return (new ContactResource($contact))
            ->additional(['message' => 'Contacto creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Perfil de un contacto: toda su información.
     */
    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact);
    }

    /**
     * Actualiza un contacto. Con PUT se envían todos los datos; con PATCH solo los que cambian.
     */
    public function update(ContactRequest $request, Contact $contact): ContactResource
    {
        $contact->update($request->validated());

        return (new ContactResource($contact))->additional([
            'message' => 'Contacto actualizado correctamente.',
        ]);
    }

    /**
     * Elimina un contacto con borrado lógico (soft delete).
     */
    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }
}
