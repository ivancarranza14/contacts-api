<?php

namespace App\Http\Requests\Contact;

use App\Http\Requests\Concerns\NormalizesContactInfo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación para crear (POST) y actualizar (PUT / PATCH) contactos.
 *
 * - Correo y teléfono no se pueden repetir: son únicos en toda la tabla contacts,
 *   incluidos los contactos eliminados con soft delete (se podrían restaurar).
 * - Nombre y dirección sí se pueden repetir.
 */
class ContactRequest extends FormRequest
{
    use NormalizesContactInfo;

    public function authorize(): bool
    {
        // Que el contacto sea del usuario lo revisa ContactPolicy antes de validar.
        return true;
    }

    public function rules(): array
    {
        $contactId = $this->route('contact')?->id;

        // POST y PUT: los campos obligatorios deben venir. PATCH: solo se valida lo enviado.
        $obligatorio = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];
        $opcional = $this->isMethod('PATCH') ? ['sometimes', 'nullable'] : ['nullable'];

        return [
            'first_name' => [...$obligatorio, 'string', 'max:100'],
            'last_name' => [...$opcional, 'string', 'max:100'],
            'phone_number' => [...$obligatorio, 'string', $this->phoneFormat(), Rule::unique('contacts', 'phone_number')->ignore($contactId)],
            'email' => [...$obligatorio, 'email', 'max:255', Rule::unique('contacts', 'email')->ignore($contactId)],
            'address' => [...$obligatorio, 'string', 'max:255'],
            'avatar_url' => [...$opcional, 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.max' => 'El nombre admite máximo 100 caracteres.',
            'last_name.max' => 'El apellido admite máximo 100 caracteres.',
            'phone_number.required' => 'El teléfono es obligatorio.',
            'phone_number.regex' => 'El teléfono debe tener entre 7 y 15 dígitos.',
            'phone_number.unique' => 'Ese teléfono ya está registrado en otro contacto.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.max' => 'El correo admite máximo 255 caracteres.',
            'email.unique' => 'Ese correo ya está registrado en otro contacto.',
            'address.required' => 'La dirección es obligatoria.',
            'address.max' => 'La dirección admite máximo 255 caracteres.',
            'avatar_url.url' => 'La foto debe ser una URL válida.',
            'avatar_url.max' => 'La URL de la foto admite máximo 255 caracteres.',
        ];
    }
}
