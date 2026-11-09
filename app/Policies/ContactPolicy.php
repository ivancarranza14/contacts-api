<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Un contacto solo lo puede ver, editar o eliminar su dueño.
 * Si existe pero es de otro usuario, la API responde 403.
 */
class ContactPolicy
{
    public function view(User $user, Contact $contact): Response
    {
        return $this->esDueno($user, $contact);
    }

    public function update(User $user, Contact $contact): Response
    {
        return $this->esDueno($user, $contact);
    }

    public function delete(User $user, Contact $contact): Response
    {
        return $this->esDueno($user, $contact);
    }

    private function esDueno(User $user, Contact $contact): Response
    {
        return $contact->user_id === $user->id
            ? Response::allow()
            : Response::deny('Este contacto no te pertenece.');
    }
}
