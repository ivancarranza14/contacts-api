<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * Limpia el correo y el teléfono antes de validar, para que la regla "unique"
 * no se pueda saltar escribiendo "ANA@correo.com" o "300 123-4567".
 */
trait NormalizesContactInfo
{
    protected function prepareForValidation(): void
    {
        $limpios = [];

        if (is_string($this->input('email'))) {
            $limpios['email'] = Str::lower(trim($this->input('email')));
        }

        if (is_string($this->input('phone_number'))) {
            $limpios['phone_number'] = preg_replace('/[\s\-\.\(\)]/', '', $this->input('phone_number'));
        }

        $this->merge($limpios);
    }

    /**
     * Teléfono: opcionalmente "+" y de 7 a 15 dígitos.
     */
    protected function phoneFormat(): string
    {
        return 'regex:/^\+?[0-9]{7,15}$/';
    }
}
