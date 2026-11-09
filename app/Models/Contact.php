<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, SoftDeletes;

    /**
     * user_id no está en la lista: el dueño siempre es el usuario autenticado
     * ($request->user()->contacts()->create(...)), nunca un dato de la petición.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'address',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim($this->first_name.' '.$this->last_name));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
