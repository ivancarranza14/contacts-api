<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\Concerns\NormalizesContactInfo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    use NormalizesContactInfo;

    public function authorize(): bool
    {
        // Solo llega aquí un usuario autenticado y siempre edita su propio perfil.
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        // PUT reemplaza: los campos obligatorios deben venir.
        // PATCH modifica: solo se validan los campos enviados.
        $obligatorio = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];
        $opcional = $this->isMethod('PATCH') ? ['sometimes', 'nullable'] : ['nullable'];

        return [
            'first_name' => [...$obligatorio, 'string', 'max:100'],
            'last_name' => [...$opcional, 'string', 'max:100'],
            'email' => [...$obligatorio, 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => [...$opcional, 'string', $this->phoneFormat(), Rule::unique('users', 'phone_number')->ignore($userId)],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.max' => 'El nombre admite máximo 100 caracteres.',
            'last_name.max' => 'El apellido admite máximo 100 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.max' => 'El correo admite máximo 255 caracteres.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'phone_number.regex' => 'El teléfono debe tener entre 7 y 15 dígitos.',
            'phone_number.unique' => 'Ese teléfono ya está en uso por otro usuario.',
        ];
    }
}
